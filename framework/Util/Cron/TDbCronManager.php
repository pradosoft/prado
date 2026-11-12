<?php

/**
 * TDbCronManager class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

use DateInterval;
use Exception;
use Prado\Security\Permissions\TPermissionEvent;
use Prado\Security\Permissions\TUserOwnerRule;
use Prado\Data\TDbDriver;
use Prado\Data\TDbDriverCapabilities;
use Prado\Data\TDbPropertiesTrait;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Util\IDbModule;

/**
 * TDbCronManager class.
 *
 * TDbCronManager does everything that TCronModule does but stores the tasks and
 * persistent data in its own database table.
 *
 * The TDbCronManager allows for adding, updating, and removing tasks from the
 * application and shell.  It can log executing tasks to the table as well.
 *
 * There are log maintenance methods and {@see \Prado\Util\Cron\TDbCronCleanLogTask} for cleaning
 * the cron logs.
 *
 * Runtime Tasks run at TApplication::onEndRequest, offloading work from the response (for example,
 * sending an email after the page renders). The guaranteed path is a persisted task: {@see addTask}
 * with `$runtime = true`, or {@see deferTask} for a closure, persists the task and queues it for
 * onEndRequest. When onEndRequest does not fire (the user presses stop, a CLI run, or a fatal) the
 * persisted task is run by the next shell cron, so it always runs. {@see addRuntimeTask} is the
 * lower-level in-memory queue step those methods build on; on its own it queues for onEndRequest
 * without persisting, so it has no backend-cron fallback.
 *
 * Each task row carries its next execution time, so {@see getPendingTasks} is one query.  A crontab
 * tick runs the tasks due within its minute at their trigger second, woken early by {@see signalWake}
 * when a task is added or updated.
 *
 * XML configuration style:
 * ```xml
 * <modules>
 *   <module id="db" class="Prado\Data\TDataSourceConfig">
 *     <database ConnectionString="mysql:host=localhost;dbname=mydb"
 *       Username="dbuser" Password="dbpass" />
 *   </module>
 *   <module id="cron" class="Prado\Util\Cron\TDbCronManager"
 *       ConnectionID="db" DefaultUserName="cron"
 *       EnableRequestCron="false" LogCronTasks="true">
 *     <job Name="dbcacheclean" Schedule="0 0 * * * *" Task="dbcache->flushCacheExpired(true)" />
 *   </module>
 * </modules>
 * ```
 * where {@see setConnectionID ConnectionID} refers to the ID of a
 * {@see \Prado\Data\TDataSourceConfig} module and tasks are specified as nested
 * `<job>` elements identical to those of {@see \Prado\Util\Cron\TCronModule}.
 *
 * PHP configuration style:
 * ```php
 * return [
 *     'modules' => [
 *         'db' => [
 *             'class' => 'Prado\Data\TDataSourceConfig',
 *             'database' => [
 *                 'ConnectionString' => 'mysql:host=localhost;dbname=mydb',
 *                 'Username' => 'dbuser',
 *                 'Password' => 'dbpass',
 *             ],
 *         ],
 *         'cron' => [
 *             'class' => 'Prado\Util\Cron\TDbCronManager',
 *             'properties' => [
 *                 'ConnectionID' => 'db',
 *                 'DefaultUserName' => 'cron',
 *                 'EnableRequestCron' => 'false',
 *                 'LogCronTasks' => 'true',
 *             ],
 *             'jobs' => [
 *                 [
 *                     'name' => 'dbcacheclean',
 *                     'schedule' => '0 0 * * * *',
 *                     'task' => 'dbcache->flushCacheExpired(true)',
 *                 ],
 *             ],
 *         ],
 *     ],
 * ];
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 * @method bool dyClearCronLog(bool $return, ?\DateInterval $period, array $extraData)
 * @method bool dyGetCronLog(bool $return, ?string $name, int $pageSize, int $offset, ?bool $sortingDesc, ?string $username, array $extraData)
 * @method bool dyGetCronLogCount(bool $return, ?string $name, ?string $username, array $extraData)
 * @method bool dyRemoveCronLogItem(bool $return, int $taskUID, array $extraData)
 * @method bool dyAddTask(bool $return, \Prado\Util\Cron\TCronTask $task, bool $runtime, array $extraData)
 * @method bool dyAddTaskGlobal(bool $return, \Prado\Util\Cron\TCronTask $task, bool $runtime, array $extraData)
 * @method bool dyUpdateTask(bool $return, \Prado\Util\Cron\TCronTask $task, array $extraData)
 * @method bool dyRemoveTask(bool $return, \Prado\Util\Cron\TCronTask|string $untask, array $extraData)
 */
class TDbCronManager extends TCronModule implements IDbModule
{
	use TDbPropertiesTrait {
		setConnectionID as setTraitConnectionID;
	}

	/** Name Validation Regular Expression: no spaces, backticks, single or double quotes, percent, and cannot start with an asterisk, less than, or greater than. These are naming requirements for ensuring easy operation at the cron shell.  */
	public const NAME_VALIDATOR_REGEX = '/^[^\s`\'\"\\*<>%][^\s`\'\"<>%]*$/i';

	public const PERM_CRON_ADD_TASK = 'cron_add_task';

	public const PERM_CRON_ADD_TASK_GLOBAL = 'cron_add_task_global';

	public const PERM_CRON_UPDATE_TASK = 'cron_update_task';

	public const PERM_CRON_REMOVE_TASK = 'cron_remove_task';

	public const PERM_CRON_LOG_READ = 'cron_log_read';

	public const PERM_CRON_LOG_DELETE = 'cron_log_delete';

	/** @var string name of the db table for cron tasks, default 'crontabs' */
	private $_tableName = 'crontabs';

	/** @var bool auto create the db table for cron, default true */
	private $_autoCreate = true;

	/** @var bool has the table been verified to be in the DB */
	private $_tableEnsured = false;

	/** @var bool log the cron tasks, in the table, as they run, default true  */
	private $_logCronTasks = true;

	/** @var array[]|TCronTask[] the tasks manually added to the database */
	private $_tasks = [];

	/** @var array[] the row data from the database */
	private $_taskRows;

	/** @var ?TCronTask[] */
	private $_runtimeTasks;

	/**
	 * constructs the instances, sets the _shellClass.
	 */
	public function __construct()
	{
		$this->_shellClass = \Prado\Util\Cron\TShellDbCronAction::class;
		parent::__construct();
	}

	/**
	 * the global event handling requests for cron task info
	 * @param TDbCronManager $cron
	 * @param null $param
	 */
	public function fxGetCronTaskInfos($cron, $param)
	{
		return Prado::createComponent(TCronTaskInfo::class, 'cronclean', \Prado\Util\Cron\TDbCronCleanLogTask::class, $this->getId(), Prado::localize('DbCron Clean Log Task'), Prado::localize('Clears the database of cron log items before the specified time period, task name, and/or user name.'));
	}

	/**
	 * Implements IPermissions for permissions on dyGetCronLog, dyGetCronLogCount, dyClearCronLog,
	 * dyRemoveCronLogItem, dyAddTask, dyAddTaskGlobal, dyUpdateTask, and dyRemoveTask.
	 * @param \Prado\Security\Permissions\TPermissionsManager $manager
	 * @return \Prado\Security\Permissions\TPermissionEvent[]
	 */
	public function getPermissions($manager)
	{
		$userIsOwnerAllowedRule = Prado::createComponent(TUserOwnerRule::class);
		return array_merge([
			Prado::createComponent(TPermissionEvent::class, static::PERM_CRON_ADD_TASK, 'Cron add own Db task.', ['dyAddTask'], $userIsOwnerAllowedRule),
			Prado::createComponent(TPermissionEvent::class, static::PERM_CRON_ADD_TASK_GLOBAL, 'Cron add Db task for any user.', ['dyAddTaskGlobal']),
			Prado::createComponent(TPermissionEvent::class, static::PERM_CRON_UPDATE_TASK, 'Cron update Db task.', ['dyUpdateTask'], $userIsOwnerAllowedRule),
			Prado::createComponent(TPermissionEvent::class, static::PERM_CRON_REMOVE_TASK, 'Cron remove Db task.', ['dyRemoveTask'], $userIsOwnerAllowedRule),
			Prado::createComponent(TPermissionEvent::class, static::PERM_CRON_LOG_READ, 'Cron read Db log.', ['dyGetCronLog', 'dyGetCronLogCount'], $userIsOwnerAllowedRule),
			Prado::createComponent(TPermissionEvent::class, static::PERM_CRON_LOG_DELETE, 'Cron delete Db log.', ['dyClearCronLog', 'dyRemoveCronLogItem'], $userIsOwnerAllowedRule),
		], parent::getPermissions($manager));
	}

	/**
	 * This validates the task name against {@see NAME_VALIDATOR_REGEX}.
	 * @param array $properties the task as an array of properties
	 * @throws TConfigurationException when the name is invalid.
	 */
	public function validateTask($properties)
	{
		$name = $properties[parent::NAME_KEY] ?? '';
		if (!preg_match(static::NAME_VALIDATOR_REGEX, $name)) {
			throw new TConfigurationException('dbcron_invalid_name', $name);
		}
		parent::validateTask($properties);
	}

	/**
	 * Sets the lastExecTime and processCount for configuration tasks from the DB.
	 * Also adds the task when not in the DB and updates when the cron task is not
	 * matched to the DB storage.  The first call loads every configuration task row
	 * and removes the rows of configuration tasks no longer configured.
	 * @param string $name name of the task to get Persistent Data.
	 * @param object $task to set the persistent data.
	 * @throws TConfigurationException when the configuration task names interfere with the db tasks names.
	 * @return bool is the persistent data set or not
	 */
	protected function setPersistentData($name, $task)
	{
		if (!($set = isset($this->_taskRows[$name]))) {
			$this->ensureTable();
			$configTasks = parent::getRawTasks();
			$db = $this->getDbConnection();

			$cmd = $db->createCommand("SELECT * FROM {$this->_tableName} WHERE active=0");

			$rmcmd = null;
			foreach ($cmd->query()->readAll() as $data) {
				$pname = $data['name'];
				if (!isset($configTasks[$pname])) {
					if (!$rmcmd) {
						$rmcmd = $db->createCommand("DELETE FROM {$this->_tableName} WHERE name=:name AND active IS NOT NULL");
					}
					$rmcmd->bindValue(":name", $pname, $db::PARAM_STR);
					$rmcmd->execute();
					continue;
				}
				$set |= ($pname == $name);
				$this->_taskRows[$pname] = $data;
			}
		}
		if ($set) {
			$task->setLastExecTime($this->_taskRows[$name]['lastexectime']);
			$task->setProcessCount((int) $this->_taskRows[$name]['processcount']);
			if (serialize($task) !== $this->_taskRows[$name]['options']) {
				$this->updateTaskInternal($task);
				return false;
			}
			return true;
		} else {
			if (!$this->addTaskInternal($task)) {
				throw new TConfigurationException('dbcron_conflicting_task_names', $name);
			}
		}
		return false;
	}

	/**
	 * Queries the database for any pending cron tasks.  Each task takes its row's LastExecTime and
	 * ProcessCount, so a run by another process since this one loaded the task is seen.
	 * @param bool $futureSeconds false gets only pending tasks , true gets tasks to the 59th second of
	 * the current minute too, ordered by their trigger time. default false.
	 * @throws TConfigurationException when the configuration task names interfere with the db tasks names.
	 * @return ?TCronTask[] the tasks that are pending, null when access is denied
	 */
	public function getPendingTasks($futureSeconds = false)
	{
		if ($this->dyGetPendingTasks(false) === true) {
			$this->dyLogPermissionFailed(static::PERM_CRON_RUN_PENDING, 'getting pending cron tasks');
			return null;
		}

		//all config tasks are properly stored in the db with this method
		parent::ensureTasks();

		$time = $this->time();
		$order = ' ORDER BY tabuid ASC';
		if ($futureSeconds) {
			$time = floor($time / 60) * 60 + 59;
			$order = ' ORDER BY nextexectime ASC';
		}

		$pendingtasks = [];
		$db = $this->getDbConnection();
		$cmd = $db->createCommand("SELECT * FROM {$this->_tableName} WHERE nextexectime<=:time AND active IS NOT NULL{$order}");
		$cmd->bindValue(":time", $time, $db::PARAM_STR);
		$results = $cmd->query()->readAll();

		foreach ($results as $properties) {
			$name = $properties['name'];
			$this->_taskRows[$name] = $properties;
			if (($task = $this->getTask($name)) instanceof TCronTask) {
				// Another process may have run the task since this process loaded it: the row is current.
				$task->setLastExecTime($properties['lastexectime']);
				$task->setProcessCount((int) $properties['processcount']);
			}
			$pendingtasks[$name] = $task;
		}
		return $pendingtasks;
	}

	/**
	 * Gets the tasks filtered by name, pageSize, offset, and sorting
	 * @param ?string $name task names where _ is wild card and % is wild string, default null.
	 * @param int $pageSize size of each page of data, default 0
	 * @param int $offset offset for the page of data, default 0
	 * @param ?bool $sortingAsc sort by ascending name, default true
	 * @throws TConfigurationException when the configuration task names interfere with the db tasks names.
	 * @return ?\Prado\Util\Cron\TCronTask[] combines the active configuration and db cron tasks, null when access is denied
	 */
	public function getTasks($name = null, $pageSize = 0, $offset = 0, $sortingAsc = true)
	{
		if ($this->dyGetTasks(false, $name) === true) {
			$this->dyLogPermissionFailed(static::PERM_CRON_READ_TASKS, 'getting cron tasks \'' . $name . '\'');
			return null;
		}

		$this->ensureTable();
		parent::ensureTasks();

		$limit = $this->limitClause($pageSize, $offset);
		$orderby = '';
		if ($sortingAsc !== null) {
			$sortingAsc = TPropertyValue::ensureBoolean($sortingAsc) ? "ASC" : "DESC";
			$orderby = " ORDER BY name $sortingAsc";
		}
		$nameLike = '';
		if (is_string($name)) {
			$nameLike = ' AND name LIKE :name';
		}

		$db = $this->getDbConnection();
		$cmd = $db->createCommand("SELECT * FROM {$this->_tableName} WHERE active IS NOT NULL{$nameLike}{$orderby}{$limit}");
		if ($nameLike) {
			$cmd->bindValue(":name", $name, $db::PARAM_STR);
		}
		$results = $cmd->query();

		$tasks = [];
		foreach ($results->readAll() as $data) {
			$name = $data['name'];
			if ($data['active'] && !isset($this->_tasks[$name])) {
				$this->_tasks[$name] = $this->unserializeTask($data['options']);
			}
			$tasks[$name] = $data['active'] ? $this->_tasks[$name] : parent::getTask($name);
			$this->_taskRows[$name] = $data;
		}
		return $tasks;
	}

	/**
	 * Gets the number of tasks filtered by $name
	 * @param ?string $name task names where _ is wild card and % is wild string, default null.
	 * @throws TConfigurationException when the configuration task names interfere with the db tasks names.
	 * @return ?int the number of active configuration and db cron tasks, null when access is denied
	 * @since 4.4.0
	 */
	public function getTasksCount($name = null)
	{
		if ($this->dyGetTasks(false, $name) === true) {
			$this->dyLogPermissionFailed(static::PERM_CRON_READ_TASKS, 'getting cron tasks count \'' . $name . '\'');
			return null;
		}

		$this->ensureTable();
		parent::ensureTasks();

		$nameLike = '';
		if (is_string($name)) {
			$nameLike = ' AND name LIKE :name';
		}

		$db = $this->getDbConnection();
		$cmd = $db->createCommand("SELECT COUNT(*) FROM {$this->_tableName} WHERE active IS NOT NULL{$nameLike}");
		if ($nameLike) {
			$cmd->bindValue(":name", $name, $db::PARAM_STR);
		}
		return (int) $cmd->queryScalar();
	}

	/**
	 * Gets the cron tasks for a specific username.
	 * @param string $username name of the user.
	 * @param ?string $name task name where _ is wild card and % is wild string, default null.
	 * @param int $pageSize size of each page of data, default 0
	 * @param int $offset offset for the page of data, default 0
	 * @param ?bool $sortingAsc sort by descending name, default true.
	 * @throws TConfigurationException when the configuration task names interfere with the db tasks names.
	 * @return ?TCronTask[] cron tasks for a specific user, null when access is denied
	 * @since 4.4.0
	 */
	public function getUserTasks($username, $name = null, $pageSize = 0, $offset = 0, $sortingAsc = true)
	{
		if ($this->dyGetUserTasks(false, $username, $name, ['extra' => ['username' => $username]]) === true) {
			$this->dyLogPermissionFailed(static::PERM_CRON_READ_TASKS, 'getting cron tasks \'' . $name . '\'@' . $username);
			return null;
		}

		$this->ensureTable();
		parent::ensureTasks();

		$limit = $this->limitClause($pageSize, $offset);
		$orderby = '';
		if ($sortingAsc !== null) {
			$sortingAsc = TPropertyValue::ensureBoolean($sortingAsc) ? "ASC" : "DESC";
			$orderby = " ORDER BY name $sortingAsc";
		}
		$nameLike = '';
		if (is_string($name)) {
			$nameLike = ' AND name LIKE :name';
		}
		$defaultuser = $this->matchesDefaultUserName($username) ? ' OR username IS NULL' : '';

		$db = $this->getDbConnection();
		$cmd = $db->createCommand(
			"SELECT * FROM {$this->_tableName} WHERE (username LIKE :uname{$defaultuser}){$nameLike} AND active IS NOT NULL{$orderby}{$limit}"
		);
		$cmd->bindValue(":uname", $username, $db::PARAM_STR);
		if ($nameLike) {
			$cmd->bindValue(":name", $name, $db::PARAM_STR);
		}

		$userTasks = [];
		foreach ($cmd->query()->readAll() as $data) {
			$name = $data['name'];
			if ($data['active'] && !isset($this->_tasks[$name])) {
				$this->_tasks[$name] = $this->unserializeTask($data['options']);
			}
			$userTasks[$name] = $data['active'] ? $this->_tasks[$name] : parent::getTask($name);
			$this->_taskRows[$name] = $data;
		}
		return $userTasks;
	}

	/**
	 * Gets the number of tasks for a specific user.
	 * @param string $username
	 * @param ?string $name task name filter with _ as a wild and and % as a wild string, default null.
	 * @throws TConfigurationException when the configuration task names interfere with the db tasks names.
	 * @return ?int number of tasks, null when access is denied
	 * @since 4.4.0
	 */
	public function getUserTasksCount($username, $name = null)
	{
		if ($this->dyGetUserTasks(false, $username, $name, ['extra' => ['username' => $username]]) === true) {
			$this->dyLogPermissionFailed(static::PERM_CRON_READ_TASKS, 'getting cron tasks count \'' . $name . '\'@' . $username);
			return null;
		}

		$this->ensureTable();
		parent::ensureTasks();
		$nameLike = '';
		if (is_string($name)) {
			$nameLike = ' AND name LIKE :name';
		}
		$defaultuser = $this->matchesDefaultUserName($username) ? ' OR username IS NULL' : '';

		$db = $this->getDbConnection();
		$cmd = $db->createCommand(
			"SELECT COUNT(*) FROM {$this->_tableName} WHERE (username LIKE :uname{$defaultuser}){$nameLike} AND active IS NOT NULL"
		);
		$cmd->bindValue(":uname", $username, $db::PARAM_STR);
		if ($nameLike) {
			$cmd->bindValue(":name", $name, $db::PARAM_STR);
		}
		return (int) $cmd->queryScalar();
	}

	/**
	 * Tells whether a user name filter, with `_` as a wild card and `%` as a wild string, matches
	 * {@see getDefaultUserName}.  Rows without a user name belong to the default user.
	 * @param ?string $username the user name filter
	 * @return bool whether the filter matches the default user name
	 * @since 4.4.0
	 */
	protected function matchesDefaultUserName($username): bool
	{
		if ($username === null || $username === '') {
			return false;
		}
		return (bool) preg_match('/^' . str_replace(['_', '%'], ['.', '.*'], preg_quote($username, '/')) . '$/', $this->getDefaultUserName());
	}

	/**
	 * Builds the SQL paging clause.
	 * @param int $pageSize the number of rows, 0 for no limit
	 * @param int $offset the number of rows to skip
	 * @return string the ` LIMIT n OFFSET m` clause, or '' for no limit
	 * @since 4.4.0
	 */
	protected function limitClause($pageSize, $offset): string
	{
		$pageSize = (int) $pageSize;
		$offset = (int) $offset;
		if ($pageSize === 0) {
			return '';
		}
		return " LIMIT {$pageSize}" . ($offset !== 0 ? " OFFSET {$offset}" : '');
	}

	/**
	 * Checks for the table once per request: a missing table is created when {@see getAutoCreateCronTable AutoCreateCronTable}
	 * allows, and a table created before 4.4.0 gains the `nextexectime`, `lastresult`, `lastduration`, and `paused` columns.
	 * @throws TConfigurationException if the table does not exist and cannot autoCreate
	 */
	protected function ensureTable()
	{
		if ($this->_tableEnsured) {
			return;
		}
		if (!$this->tableExists()) {
			if (!$this->_autoCreate) {
				throw new TConfigurationException('dbcron_table_nonexistent', $this->_tableName);
			}
			$this->createDbTable();
		}
		$this->ensureNextExecTimeColumn();
		$this->ensureRunColumns();
		$this->_tableEnsured = true;
	}

	/**
	 * Brings the cron table up to date for the running version: a missing table is created, whatever
	 * {@see getAutoCreateCronTable AutoCreateCronTable} says, and a table created before 4.4.0 gains the
	 * `nextexectime` column with its index and values, and the run columns `lastresult`, `lastduration`, and
	 * `paused`.  The `cron/migrate` shell action calls this as a deployment step; {@see ensureTable} does the
	 * same on first use.
	 * @return array{created: bool, column: ?int, run: bool} whether the table was created, the active rows
	 *   given a `nextexectime` when the column was added (null when the column existed), and whether a run
	 *   column was added
	 * @since 4.4.0
	 */
	public function migrateTable(): array
	{
		$created = !$this->tableExists();
		if ($created) {
			$this->createDbTable();
		}
		$column = $this->ensureNextExecTimeColumn();
		$run = $this->ensureRunColumns();
		$this->_tableEnsured = true;
		return ['created' => $created, 'column' => $column, 'run' => $run];
	}

	/**
	 * @return bool whether the cron table exists
	 * @since 4.4.0
	 */
	protected function tableExists(): bool
	{
		return $this->columnExists('*');
	}

	/**
	 * @param string $column the column name, or `*` for the table itself
	 * @return bool whether the cron table has the column
	 * @since 4.4.0
	 */
	protected function columnExists(string $column): bool
	{
		try {
			$this->getDbConnection()->createCommand('SELECT ' . $column . ' FROM ' . $this->_tableName . ' WHERE 0=1')->query()->close();
			return true;
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Adds the `nextexectime` column to a cron table created before 4.4.0, with its index, and fills
	 * it for the active task rows from each task's schedule.
	 * @return ?int the active rows given a `nextexectime`, null when the column already exists
	 * @since 4.4.0
	 */
	protected function ensureNextExecTimeColumn(): ?int
	{
		if ($this->columnExists('nextexectime')) {
			return null;
		}
		$db = $this->getDbConnection();
		$db->createCommand('ALTER TABLE ' . $this->_tableName . ' ADD COLUMN nextexectime INT NULL DEFAULT NULL')->execute();
		$db->createCommand('CREATE INDEX ' . $this->getIndexName('tnext') . ' ON ' . $this->_tableName . '(nextexectime)')->execute();

		$rows = $db->createCommand("SELECT name, options, lastexectime FROM {$this->_tableName} WHERE active IS NOT NULL")->query()->readAll();
		$cmd = $db->createCommand("UPDATE {$this->_tableName} SET nextexectime=:ntime WHERE name=:name AND active IS NOT NULL");
		$filled = 0;
		foreach ($rows as $row) {
			$task = $this->unserializeTask($row['options']);
			if (!($task instanceof TCronTask)) {
				continue;
			}
			$cmd->bindValue(":ntime", $task->getNextTriggerTime((int) $row['lastexectime']), $db::PARAM_STR);
			$cmd->bindValue(":name", $row['name'], $db::PARAM_STR);
			$filled += $cmd->execute();
		}
		return $filled;
	}

	/**
	 * Adds the run columns, `lastresult`, `lastduration`, and `paused`, that a cron table created before
	 * 4.4.0 lacks.
	 * @return bool whether a column was added
	 * @since 4.4.0
	 */
	protected function ensureRunColumns(): bool
	{
		$added = false;
		foreach (['lastresult' => 'VARCHAR (16)', 'lastduration' => 'VARCHAR (20)', 'paused' => 'INT'] as $column => $type) {
			if (!$this->columnExists($column)) {
				$this->getDbConnection()->createCommand('ALTER TABLE ' . $this->_tableName . ' ADD COLUMN ' . $column . ' ' . $type . ' NULL DEFAULT NULL')->execute();
				$added = true;
			}
		}
		return $added;
	}

	/**
	 * Names an index of the cron table after the table.  SQLite and PostgreSQL scope index names to
	 * the schema, so two cron tables in one database need distinct index names.
	 * @param string $suffix the index suffix: `tname`, `tclass`, `tnext`, `tuser`, or `tactive`
	 * @return string the index name
	 * @since 4.4.0
	 */
	protected function getIndexName(string $suffix): string
	{
		return preg_replace('/\W+/', '_', $this->_tableName) . '_' . $suffix;
	}

	/**
	 * creates the module table
	 */
	protected function createDbTable()
	{
		$db = $this->getDbConnection();
		$driver = TDbDriverCapabilities::normalizeDriver($db->getDriverName());
		$autotype = 'INTEGER';
		$autoidAttributes = '';
		if ($driver === TDbDriver::DRIVER_MYSQL) {
			$autoidAttributes = ' AUTO_INCREMENT';
		} elseif ($driver === TDbDriver::DRIVER_SQLITE) {
			$autoidAttributes = ' AUTOINCREMENT';
		} elseif ($driver === TDbDriver::DRIVER_PGSQL) {
			$autotype = 'SERIAL';
		}
		$postIndices = '; CREATE INDEX ' . $this->getIndexName('tname') . ' ON ' . $this->_tableName . '(`name`);' .
			'CREATE INDEX ' . $this->getIndexName('tclass') . ' ON ' . $this->_tableName . '(`task`);' .
			'CREATE INDEX ' . $this->getIndexName('tnext') . ' ON ' . $this->_tableName . '(`nextexectime`);' .
			'CREATE INDEX ' . $this->getIndexName('tuser') . ' ON ' . $this->_tableName . '(`username`);' .
			'CREATE INDEX ' . $this->getIndexName('tactive') . ' ON ' . $this->_tableName . '(`active`);';

		$sql = 'CREATE TABLE IF NOT EXISTS ' . $this->_tableName . ' (
			`tabuid` ' . $autotype . ' PRIMARY KEY' . $autoidAttributes . ',
			`name` VARCHAR (127) NOT NULL,
			`schedule` VARCHAR (127) NOT NULL,
			`task` VARCHAR (256) NOT NULL,
			`moduleid` VARCHAR (127) NULL,
			`username` VARCHAR (127) NULL,
			`options` MEDIUMTEXT NULL,
			`processcount` INT NOT NULL DEFAULT 0,
			`lastexectime` VARCHAR (20) NULL DEFAULT NULL,
			`nextexectime` INT NULL DEFAULT NULL,
			`lastresult` VARCHAR (16) NULL DEFAULT NULL,
			`lastduration` VARCHAR (20) NULL DEFAULT NULL,
			`paused` INT NULL DEFAULT NULL,
			`active` BOOLEAN NULL
			)' . $postIndices;

		$cmd = $db->createCommand($sql);

		$cmd->execute();
	}

	/**
	 * logCronTask adds a task log to the table, after {@see updateTaskInfo} claimed the run, so the
	 * row records the run's count and time.
	 * @param TCronTask $task
	 * @param string $username
	 */
	protected function logCronTask($task, $username)
	{
		parent::logCronTask($task, $username);

		$logid = null;
		if ($this->getLogCronTasks()) {
			$this->ensureTable();

			$db = $this->getDbConnection();
			$cmd = $db->createCommand(
				"INSERT INTO {$this->_tableName} " .
					"(name, schedule, task, moduleid, username, options, processcount, lastexectime, active)" .
					" VALUES (:name, :schedule, :task, :mid, :username, :options, :count, :time, NULL)"
			);
			$cmd->bindValue(":name", $task->getName(), $db::PARAM_STR);
			$cmd->bindValue(":task", $task->getTask(), $db::PARAM_STR);
			$cmd->bindValue(":schedule", $task->getSchedule(), $db::PARAM_STR);
			$cmd->bindValue(":mid", $task->getModuleId(), $db::PARAM_STR);
			$cmd->bindValue(":username", $username, $db::PARAM_STR);
			$cmd->bindValue(":options", serialize($task), $db::PARAM_STR);
			$cmd->bindValue(":count", $task->getProcessCount(), $db::PARAM_INT);
			$cmd->bindValue(":time", $task->getLastExecTime(), $db::PARAM_STR);
			$cmd->execute();
			$logid = $db->getLastInsertID();
		}
		return $logid;
	}

	/**
	 * Claims the run of a task: updates the LastExecTime, ProcessCount, and next execution time in the
	 * database on the condition that the row's next execution time is still the one this process read,
	 * so of several cron processes (hosts) that found the task pending only one runs it.  A skipped
	 * trigger claims without counting a run.  A task with {@see \Prado\Util\Cron\TCronTask::getAutoRemove AutoRemove}
	 * is removed after a run.  A task without a row, a runtime task that is not persisted, is always claimed.
	 * @param TCronTask $task
	 * @param bool $run whether the task runs; false for a skipped trigger
	 * @return bool whether this process claimed the run; false when another process did
	 */
	protected function updateTaskInfo($task, bool $run = true)
	{
		$name = $task->getName();
		$priorTime = $task->getLastExecTime();
		$priorCount = $task->getProcessCount();
		$task->setLastExecTime($time = $this->time());
		$task->setProcessCount($count = ($priorCount + ($run ? 1 : 0)));

		$row = $this->_taskRows[$name] ?? null;
		$seen = ($row === null) ? null : $row['nextexectime'];
		$claim = ($row === null) ? '' : (($seen === null) ? ' AND nextexectime IS NULL' : ' AND nextexectime=:seen');

		$db = $this->getDbConnection();
		$cmd = $db->createCommand(
			"UPDATE {$this->_tableName} SET processcount=:count, lastexectime=:time, nextexectime=:ntime, options=:task WHERE name=:name AND active IS NOT NULL{$claim}"
		);
		$cmd->bindValue(":count", $count, $db::PARAM_STR);
		$cmd->bindValue(":time", $time, $db::PARAM_STR);
		$cmd->bindValue(":ntime", $ntime = $task->getNextTriggerTime($time), $db::PARAM_STR);
		$cmd->bindValue(":task", $serial = serialize($task), $db::PARAM_STR);
		$cmd->bindValue(":name", $name, $db::PARAM_STR);
		if ($claim === ' AND nextexectime=:seen') {
			$cmd->bindValue(":seen", $seen, $db::PARAM_STR);
		}
		if ($cmd->execute() < 1 && $row !== null) {
			$task->setLastExecTime($priorTime);
			$task->setProcessCount($priorCount);
			unset($this->_taskRows[$name]);
			return false;
		}

		if ($row !== null) {
			$this->_taskRows[$name]['processcount'] = $count;
			$this->_taskRows[$name]['lastexectime'] = $time;
			$this->_taskRows[$name]['nextexectime'] = $ntime;
			$this->_taskRows[$name]['options'] = $serial;
		}

		if ($run && $count && $task->getAutoRemove()) {
			$this->removeTaskInternal($task);
		}
		return true;
	}

	/**
	 * Takes back the run count of a claimed trigger that is skipped because a previous run holds the run
	 * lock, in the task's row.
	 * @param TCronTask $task the task
	 * @since 4.4.0
	 */
	protected function uncountTaskRun($task)
	{
		$name = $task->getName();
		$task->setProcessCount(max(0, $task->getProcessCount() - 1));
		$db = $this->getDbConnection();
		$cmd = $db->createCommand("UPDATE {$this->_tableName} SET processcount=processcount-1 WHERE name=:name AND active IS NOT NULL AND processcount > 0");
		$cmd->bindValue(":name", $name, $db::PARAM_STR);
		$cmd->execute();
		if (isset($this->_taskRows[$name])) {
			$this->_taskRows[$name]['processcount'] = $task->getProcessCount();
		}
	}

	/**
	 * Records the result of a run and the seconds it took in the task's row, and in the run's log row.
	 * @param TCronTask $task the task
	 * @param string $result the run's result
	 * @param float $duration the seconds the run took
	 * @param mixed $logId the run's log row ({@see logCronTask}), or null
	 * @since 4.4.0
	 */
	protected function updateTaskResult($task, string $result, float $duration, mixed $logId = null)
	{
		$db = $this->getDbConnection();
		$cmd = $db->createCommand("UPDATE {$this->_tableName} SET lastresult=:result, lastduration=:duration WHERE name=:name AND active IS NOT NULL");
		$cmd->bindValue(":result", $result, $db::PARAM_STR);
		$cmd->bindValue(":duration", (string) round($duration, 6), $db::PARAM_STR);
		$cmd->bindValue(":name", $task->getName(), $db::PARAM_STR);
		$cmd->execute();
		if ($logId !== null && $logId !== false && $logId !== '') {
			$cmd = $db->createCommand("UPDATE {$this->_tableName} SET lastresult=:result, lastduration=:duration WHERE tabuid=:id AND active IS NULL");
			$cmd->bindValue(":result", $result, $db::PARAM_STR);
			$cmd->bindValue(":duration", (string) round($duration, 6), $db::PARAM_STR);
			$cmd->bindValue(":id", $logId, $db::PARAM_STR);
			$cmd->execute();
		}
	}

	/**
	 * Sets the task's LastExecTime to the end of its run, for a schedule 'every <interval> after end', and
	 * stores it with the next execution time in the task's row.
	 * @param TCronTask $task the task
	 * @since 4.4.0
	 */
	protected function updateTaskEnd($task)
	{
		$name = $task->getName();
		$task->setLastExecTime($time = $this->time());
		$db = $this->getDbConnection();
		$cmd = $db->createCommand("UPDATE {$this->_tableName} SET lastexectime=:time, nextexectime=:ntime, options=:task WHERE name=:name AND active IS NOT NULL");
		$cmd->bindValue(":time", $time, $db::PARAM_STR);
		$cmd->bindValue(":ntime", $ntime = $task->getNextTriggerTime($time), $db::PARAM_STR);
		$cmd->bindValue(":task", $serial = serialize($task), $db::PARAM_STR);
		$cmd->bindValue(":name", $name, $db::PARAM_STR);
		$cmd->execute();
		if (isset($this->_taskRows[$name])) {
			$this->_taskRows[$name]['lastexectime'] = $time;
			$this->_taskRows[$name]['nextexectime'] = $ntime;
			$this->_taskRows[$name]['options'] = $serial;
		}
	}

	/**
	 * Unserializes a task from its row and prepares it ({@see prepareTask}).
	 * @param ?string $options the serialized task
	 * @return mixed the task, or false when the value does not unserialize
	 * @since 4.4.0
	 */
	protected function unserializeTask(?string $options): mixed
	{
		$task = @unserialize((string) $options);
		if ($task instanceof TCronTask) {
			$this->prepareTask($task);
		}
		return $task;
	}

	/**
	 * @return string[] the names of the paused tasks, from their rows
	 * @since 4.4.0
	 */
	public function getPausedTasks(): array
	{
		$this->ensureTable();
		return array_map('strval', $this->getDbConnection()->createCommand("SELECT name FROM {$this->_tableName} WHERE paused=1 AND active IS NOT NULL ORDER BY name")->queryColumn());
	}

	/**
	 * Stores whether a task is paused in its row.
	 * @param string $name the task name
	 * @param bool $paused whether the task is paused
	 * @since 4.4.0
	 */
	protected function writeTaskPaused(string $name, bool $paused): void
	{
		$db = $this->getDbConnection();
		$cmd = $db->createCommand("UPDATE {$this->_tableName} SET paused=" . ($paused ? '1' : 'NULL') . " WHERE name=:name AND active IS NOT NULL");
		$cmd->bindValue(":name", $name, $db::PARAM_STR);
		$cmd->execute();
	}

	/**
	 * Returns the run information of each task from its row.
	 * @param bool $fresh unused; the rows are always read
	 * @return array<string, array{time: ?int, count: int, result: ?string, duration: ?float}> the run information, by task name
	 * @since 4.4.0
	 */
	public function getTaskResults(bool $fresh = false): array
	{
		$this->ensureTable();
		parent::ensureTasks();
		$results = [];
		$rows = $this->getDbConnection()->createCommand("SELECT name, lastexectime, processcount, lastresult, lastduration FROM {$this->_tableName} WHERE active IS NOT NULL")->query()->readAll();
		foreach ($rows as $row) {
			$results[$row['name']] = [
				'time' => ($row['lastexectime'] === null) ? null : (int) $row['lastexectime'],
				'count' => (int) $row['processcount'],
				'result' => $row['lastresult'],
				'duration' => ($row['lastduration'] === null) ? null : (float) $row['lastduration'],
			];
		}
		return $results;
	}

	/**
	 * Returns the soonest next triggers of the tasks from their rows.
	 * @param int $limit the number of triggers to return
	 * @param bool $fresh unused; the rows are always read
	 * @return array<string, int> the trigger times, by task name, soonest first
	 * @since 4.4.0
	 */
	public function getNextTriggers(int $limit = 10, bool $fresh = false): array
	{
		$this->ensureTable();
		parent::ensureTasks();
		$limit = max(0, $limit);
		if ($limit === 0) {
			return [];
		}
		$rows = $this->getDbConnection()->createCommand("SELECT name, nextexectime FROM {$this->_tableName} WHERE active IS NOT NULL AND nextexectime IS NOT NULL ORDER BY nextexectime ASC, name ASC" . $this->limitClause($limit, 0))->query()->readAll();
		$triggers = [];
		foreach ($rows as $row) {
			$triggers[$row['name']] = (int) $row['nextexectime'];
		}
		return $triggers;
	}

	/**
	 * Stale configuration task rows are removed by the {@see setPersistentData} data loader.
	 */
	protected function filterStaleTasks()
	{
	}

	/**
	 * This executes the Run Time Tasks, this method is automatically added
	 * to TApplication::onEndRequest when there are RuntimeTasks via {@see addRuntimeTask}.
	 * @param ?\Prado\TApplication $sender
	 * @param mixed $param
	 * @return int number of tasks run
	 */
	public function executeRuntimeTasks($sender = null, $param = null)
	{
		$runtimeTasks = $this->getRuntimeTasks();
		if (!$runtimeTasks) {
			return 0;
		}
		$numtasks = count($runtimeTasks);
		$cronlogger = $this->asa(TCronModule::SHELL_LOG_BEHAVIOR);
		$enabled = false;
		if ($cronlogger) {
			$enabled = $cronlogger->getEnabled();
			$this->disableBehavior(TCronModule::SHELL_LOG_BEHAVIOR);
		}
		foreach ($runtimeTasks as $key => $task) {
			$this->runTask($task);
		}
		if ($cronlogger && $enabled) {
			$this->enableBehavior(TCronModule::SHELL_LOG_BEHAVIOR);
		}
		return $numtasks;
	}

	/**
	 * Queues a task to run at TApplication::onEndRequest, attaching {@see executeRuntimeTasks} to the
	 * event on the first task. This is the in-memory fast path only: it does not persist the task, so a
	 * task added solely through this method has no backend-cron fallback if onEndRequest does not fire.
	 * For the guaranteed must-run behavior, persist as well: {@see addTask}`($task, true)`, or
	 * {@see deferTask} for a closure.
	 *
	 * Useful, for example, to send an email after a form submit without slowing the next page's render.
	 * Runtime tasks default {@see \Prado\Util\Cron\TCronTask::setAutoRemove AutoRemove} to true, so a
	 * persisted one is deleted from the table once it runs, preventing a double-run on the backend cron.
	 * @param TCronTask $task
	 */
	public function addRuntimeTask($task)
	{
		if ($this->_runtimeTasks === null) {
			$this->getApplication()->attachEventHandler('onEndRequest', [$this, 'executeRuntimeTasks']);
			$this->_runtimeTasks = [];
		}
		$this->_runtimeTasks[$task->getName()] = $task;
		if ($task->getAutoRemove() === null) {
			$task->setAutoRemove(true);
		}
	}

	/**
	 * Gets the runtime tasks.
	 * @return ?\Prado\Util\Cron\TCronTask[] the tasks to run on {@see executeRuntimeTasks}
	 */
	public function getRuntimeTasks()
	{
		return $this->_runtimeTasks;
	}

	/**
	 * Removes a task from the onEndRequest fast path, detaching {@see executeRuntimeTasks} when none
	 * remain. This only dequeues the inline run; a persisted task still runs on the backend cron. To
	 * cancel a task entirely, and stop the backend cron from running it, use {@see removeTask}.
	 * @param string|TCronTask $untask the task or task name to dequeue
	 */
	public function removeRuntimeTask($untask)
	{
		if ($this->_runtimeTasks === null) {
			return;
		}
		$name = is_string($untask) ? $untask : $untask->getName();
		unset($this->_runtimeTasks[$name]);
		if (!$this->_runtimeTasks) {
			$this->_runtimeTasks = null;
			$this->getApplication()->detachEventHandler('onEndRequest', [$this, 'executeRuntimeTasks']);
		}
	}

	/**
	 * Clears all tasks from being run time, and removes the handler from onEndRequest.
	 */
	public function clearRuntimeTasks()
	{
		if ($this->_runtimeTasks === null) {
			return;
		}
		$this->_runtimeTasks = null;
		$this->getApplication()->detachEventHandler('onEndRequest', [$this, 'executeRuntimeTasks']);
	}

	/**
	 * Gets a task of a specific name.
	 * @param string $taskName
	 * @param bool $checkExisting check the loaded tasks and rows before the database, default true.
	 * @param bool $asObject returns the database row if false, default true.
	 * @throws TConfigurationException when the configuration task names interfere with the db tasks names on $asObject == false.
	 * @return null|array|TCronTask the task object, database row, or null when absent or access is denied.
	 */
	public function getTask($taskName, $checkExisting = true, $asObject = true)
	{
		$configTasks = parent::getRawTasks();
		if ($checkExisting) {
			if ($asObject) {
				if (isset($this->_tasks[$taskName])) {
					return $this->_tasks[$taskName];
				} elseif (isset($configTasks[$taskName])) {
					return parent::getTask($taskName);
				} elseif (isset($this->_taskRows[$taskName])) {
					$this->_tasks[$taskName] = $this->unserializeTask($this->_taskRows[$taskName]['options']);
					return $this->_tasks[$taskName];
				}
			} else {
				parent::ensureTasks();
				if (isset($this->_taskRows[$taskName])) {
					return $this->_taskRows[$taskName];
				}
			}
		}
		$this->ensureTable();

		$db = $this->getDbConnection();
		$cmd = $db->createCommand(
			"SELECT * FROM {$this->_tableName} WHERE name=:name AND active IS NOT NULL LIMIT 1"
		);
		$cmd->bindValue(":name", $taskName, $db::PARAM_STR);

		$result = $cmd->queryRow();
		if (!$result) {
			return null;
		}

		$this->_taskRows[$taskName] = $result;
		if (!isset($this->_tasks[$taskName])) {
			$this->_tasks[$taskName] = $this->unserializeTask($result['options']);
		}
		if ($this->dyGetUserTasks(false, $username = $this->_tasks[$taskName]->getUserName(), $taskName, ['extra' => ['username' => $username]]) === true) {
			$this->dyLogPermissionFailed(static::PERM_CRON_READ_TASKS, 'getting cron task \'' . $taskName . '\'');
			return null;
		}
		if ($asObject) {
			return $this->_tasks[$taskName];
		}
		return $result;
	}

	/**
	 * Defers a closure to run at the end of the current request, with a cron fallback so it always runs.
	 * The closure is wrapped in an auto-removing {@see TClosureCronTask} due immediately, persisted as an
	 * active task, and queued as a runtime task. {@see executeRuntimeTasks} runs it on
	 * TApplication::onEndRequest, the fast path; when onEndRequest does not fire (CLI, a fatal, or no
	 * request) the persisted task is run by the next shell cron, so the work is guaranteed to run.
	 *
	 * This offloads work from the request, for example sending an email after the page is rendered, without
	 * slowing the response. The closure is stored encrypted by default; see {@see TClosureCronTask}.
	 * @param \Closure $closure the work to run, receiving the task and this module
	 * @param ?string $name the task name; an unused name is generated when null
	 * @return TClosureCronTask the deferred task
	 * @since 4.4.0
	 */
	public function deferTask($closure, $name = null)
	{
		$task = new TClosureCronTask($closure);
		$task->setName($name ?? $this->generateDeferredTaskName());
		$task->setSchedule('@' . $this->time());
		$task->setAutoRemove(true);
		$this->addTaskInternal($task, true);
		return $task;
	}

	/**
	 * Generates a task name not already in use, for an unnamed {@see deferTask} closure.
	 * @return string an unused deferred-task name
	 * @since 4.4.0
	 */
	protected function generateDeferredTaskName(): string
	{
		do {
			$name = 'deferred-' . bin2hex(random_bytes(8));
		} while ($this->taskExistsInternal($name));
		return $name;
	}

	/**
	 * Adds a task to the database.  Validates the name and cannot add a task with an existing name.
	 * This updates the table row data as well.  Adding a task for oneself needs {@see PERM_CRON_ADD_TASK};
	 * adding a task for another user needs {@see PERM_CRON_ADD_TASK_GLOBAL}.
	 * @param TCronTask $task
	 * @param bool $runtime should the task be added to the Run Time Task after being added, default false.
	 * @return ?bool was the task added, null when access is denied
	 */
	public function addTask($task, $runtime = false)
	{
		$extra = ['extra' => ['username' => $task->getUserName()]];
		// Adding own task (owner gated) or adding for any user (global) is allowed.
		if ($this->dyAddTask(false, $task, $runtime, $extra) === true
			&& $this->dyAddTaskGlobal(false, $task, $runtime, $extra) === true) {
			$this->dyLogPermissionFailed(static::PERM_CRON_ADD_TASK, 'add task \'' . $task->getName() . '\'');
			return null;
		}
		$result = $this->addTaskInternal($task, $runtime);
		if ($result) {
			$this->signalWakeIfEarliest($task);
		}
		return $result;
	}

	/**
	 * Wakes a sleeping second-resolution cron process when the given task becomes the earliest pending
	 * task due within the current minute, so it runs on time instead of after the remaining sleep. A
	 * task scheduled later than the current head, or beyond this minute, needs no kick. {@see signalWake}
	 * is a no-op when no cron is listening.
	 * @param \Prado\Util\Cron\TCronTask $task the task just added or updated
	 * @since 4.4.0
	 */
	protected function signalWakeIfEarliest($task)
	{
		$ntime = $this->_taskRows[$task->getName()]['nextexectime'] ?? null;
		if ($ntime === null) {
			return;
		}
		$ntime = (int) $ntime;
		$endOfMinute = (int) (floor($this->time() / 60) * 60 + 59);
		if ($ntime > $endOfMinute) {
			return;
		}
		$earliest = $this->getEarliestPendingTime();
		if ($earliest === null || $ntime <= $earliest) {
			$this->signalWake();
		}
	}

	/**
	 * Returns the soonest next execution time among active tasks, read from the database.
	 * @return ?int the Unix timestamp of the next trigger, or null when no task has one
	 * @since 4.4.0
	 */
	public function getNextPendingTime(): ?int
	{
		return $this->getEarliestPendingTime();
	}

	/**
	 * Closes the database connection while the daemon waits, so an idle connection does not time out,
	 * then raises the `OnDaemonIdle` event; {@see getDbConnection} reopens the connection on the next query.
	 * @param \Prado\Util\Cron\TCronDaemonEventParameter $param the idle parameter
	 * @since 4.4.0
	 */
	public function onDaemonIdle($param)
	{
		$this->deactivateDbConnection();
		parent::onDaemonIdle($param);
	}

	/**
	 * @return ?bool true: the connection is activated on every retrieval, so a connection closed while
	 * the daemon waited reopens on the next query
	 */
	protected function getDbConnectionActivationType(): ?bool
	{
		return true;
	}

	/**
	 * Returns the soonest next execution time among active tasks, the head of the schedule.
	 * @return ?int the minimum nextexectime over active tasks, or null when there are none
	 * @since 4.4.0
	 */
	protected function getEarliestPendingTime(): ?int
	{
		$this->ensureTable();
		$value = $this->getDbConnection()->createCommand(
			"SELECT MIN(nextexectime) FROM {$this->_tableName} WHERE active IS NOT NULL"
		)->queryScalar();
		return ($value === null || $value === false) ? null : (int) $value;
	}

	/**
	 * Adds a task to the database.  Validates the name. It cannot add a task with an existing name.
	 * This updates the table row data as well.
	 * @param \Prado\Util\Cron\TCronTask $task
	 * @param bool $runtime should the task be added to the Run Time Task after being added, default false.
	 * @return bool was the task added
	 */
	protected function addTaskInternal($task, $runtime = false)
	{
		$this->ensureTable();
		$name = $task->getName();
		if (!preg_match(static::NAME_VALIDATOR_REGEX, $name)) {
			return false;
		}

		if ($this->taskExistsInternal($name)) {
			return false;
		}
		$this->prepareTask($task);
		try {
			$task->getScheduler();
		} catch (TInvalidDataValueException $e) {
			return false;
		}
		$task->resetTaskLastExecTime();
		$configTasks = parent::getRawTasks();

		$db = $this->getDbConnection();
		$cmd = $db->createCommand(
			"INSERT INTO {$this->_tableName} " .
				"(name, schedule, task, moduleid, username, options, lastexectime, nextexectime, processcount, active)" .
				" VALUES (:name, :schedule, :task, :mid, :username, :options, :time, :ntime, :count, :active)"
		);
		$cmd->bindValue(":name", $name, $db::PARAM_STR);
		$cmd->bindValue(":schedule", $schedule = $task->getSchedule(), $db::PARAM_STR);
		$cmd->bindValue(":task", $taskExec = $task->getTask(), $db::PARAM_STR);
		$cmd->bindValue(":mid", $mid = $task->getModuleId(), $db::PARAM_STR);
		$cmd->bindValue(":username", $username = $task->getUserName(), $db::PARAM_STR);
		$cmd->bindValue(":options", $serial = serialize($task), $db::PARAM_STR);
		$cmd->bindValue(":time", $time = $task->getLastExecTime(), $db::PARAM_STR);
		$cmd->bindValue(":ntime", $ntime = $task->getNextTriggerTime($time), $db::PARAM_STR);
		$cmd->bindValue(":count", $count = $task->getProcessCount(), $db::PARAM_INT);
		$cmd->bindValue(":active", $active = (isset($configTasks[$name]) ? '0' : '1'), $db::PARAM_INT);
		$cmd->execute();

		if (!isset($configTasks[$name])) {
			$this->_tasks[$name] = $task;
		}
		$this->_taskRows[$name] = [];
		$this->_taskRows[$name]['tabuid'] = $db->getLastInsertID();
		$this->_taskRows[$name]['name'] = $name;
		$this->_taskRows[$name]['schedule'] = $schedule;
		$this->_taskRows[$name]['task'] = $taskExec;
		$this->_taskRows[$name]['moduleid'] = $mid;
		$this->_taskRows[$name]['username'] = $username;
		$this->_taskRows[$name]['options'] = $serial;
		$this->_taskRows[$name]['processcount'] = $count;
		$this->_taskRows[$name]['lastexectime'] = $time;
		$this->_taskRows[$name]['nextexectime'] = $ntime;
		$this->_taskRows[$name]['active'] = $active;

		if ($runtime) {
			$this->addRuntimeTask($task);
		}
		return true;
	}

	/**
	 * Updates a task from its unique name.  If the Task is not in the DB it returns false
	 * @param TCronTask $task
	 * @return ?bool was the task updated, null when access is denied
	 */
	public function updateTask($task)
	{
		if ($this->dyUpdateTask(false, $task, ['extra' => ['username' => $task->getUserName()]]) === true) {
			$this->dyLogPermissionFailed(static::PERM_CRON_UPDATE_TASK, 'update task \'' . $task->getName() . '\'');
			return null;
		}
		$result = $this->updateTaskInternal($task);
		if ($result) {
			$this->signalWakeIfEarliest($task);
		}
		return $result;
	}

	/**
	 * Updates a task from its unique name.  If the Task is not in the DB it returns false
	 * @param \Prado\Util\Cron\TCronTask $task
	 * @return bool was the task updated
	 */
	protected function updateTaskInternal($task)
	{
		$this->ensureTable();
		$name = $task->getName();
		if (!$this->taskExistsInternal($name)) {
			return false;
		}
		$this->prepareTask($task);
		try {
			$task->getScheduler();
		} catch (TInvalidDataValueException $e) {
			return false;
		}
		$schedule = $task->getSchedule();
		if ($schedule != ($this->_taskRows[$name]['schedule'] ?? null)) {
			$task->resetTaskLastExecTime();
		}

		$db = $this->getDbConnection();
		$cmd = $db->createCommand(
			"UPDATE {$this->_tableName} SET schedule=:schedule, task=:task, moduleid=:mid, username=:username, options=:options, processcount=:count, lastexectime=:time, nextexectime=:ntime WHERE name=:name AND active IS NOT NULL"
		);
		$cmd->bindValue(":schedule", $schedule, $db::PARAM_STR);
		$cmd->bindValue(":task", $taskExec = $task->getTask(), $db::PARAM_STR);
		$cmd->bindValue(":mid", $mid = $task->getModuleId(), $db::PARAM_STR);
		$cmd->bindValue(":username", $username = $task->getUserName(), $db::PARAM_STR);
		$cmd->bindValue(":options", $serial = serialize($task), $db::PARAM_STR);
		$cmd->bindValue(":count", $count = $task->getProcessCount(), $db::PARAM_STR);
		$cmd->bindValue(":time", $time = $task->getLastExecTime(), $db::PARAM_STR);
		$cmd->bindValue(":ntime", $ntime = $task->getNextTriggerTime($time), $db::PARAM_STR);
		$cmd->bindValue(":name", $name, $db::PARAM_STR);
		$cmd->execute();

		$configTasks = parent::getRawTasks();
		if (!isset($configTasks[$name])) {
			$this->_tasks[$name] = $task;
		}

		if (!isset($this->_taskRows[$name])) {
			$this->_taskRows[$name] = [];
		}
		$this->_taskRows[$name]['name'] = $name;
		$this->_taskRows[$name]['schedule'] = $schedule;
		$this->_taskRows[$name]['task'] = $taskExec;
		$this->_taskRows[$name]['moduleid'] = $mid;
		$this->_taskRows[$name]['username'] = $username;
		$this->_taskRows[$name]['options'] = $serial;
		$this->_taskRows[$name]['processcount'] = $count;
		$this->_taskRows[$name]['lastexectime'] = $time;
		$this->_taskRows[$name]['nextexectime'] = $ntime;

		return true;
	}

	/**
	 * Removes a task from the database table.
	 * This also removes the task from the current tasks, the taskRow, and runtime Tasks.
	 *
	 * This cannot remove tasks that are current configuration tasks.  Only tasks
	 * that exist can be removed.
	 * @param string|TCronTask $untask the task to remove from the DB
	 * @return ?bool was the task removed, null when access is denied
	 */
	public function removeTask($untask)
	{
		$task = null;
		if (is_string($untask)) {
			$task = $this->getTask($untask);
			if (!$task) {
				return false;
			}
		}
		if ($this->dyRemoveTask(false, $untask, ['extra' => ['username' => ($task ?? $untask)->getUserName()]]) === true) {
			$this->dyLogPermissionFailed(static::PERM_CRON_REMOVE_TASK, 'remove task \'' . ($task ?? $untask)->getName() . '\'');
			return null;
		}
		return $this->removeTaskInternal($untask);
	}

	/**
	 * Removes a task from the database table.
	 * This also removes the task from the current tasks, the taskRow, and runtime Tasks.
	 *
	 * This cannot remove tasks that are current configuration tasks.  Only tasks
	 * that exist can be removed.
	 * @param \Prado\Util\Cron\TCronTask|string $untask the task to remove from the DB
	 * @return bool was the task removed
	 */
	protected function removeTaskInternal($untask)
	{
		$this->ensureTable();
		$name = is_subclass_of($untask, \Prado\Util\Cron\TCronTask::class) ? $untask->getName() : $untask;
		$configTasks = parent::getRawTasks();
		if (isset($configTasks[$name])) {
			return false;
		}
		if (!$this->taskExistsInternal($name)) {
			return false;
		}

		$db = $this->getDbConnection();
		$cmd = $db->createCommand(
			"DELETE FROM {$this->_tableName} WHERE name=:name AND active IS NOT NULL"
		);
		$cmd->bindValue(":name", $name, $db::PARAM_STR);
		$cmd->execute();

		// Remove task to list of tasks
		unset($this->_tasks[$name]);
		unset($this->_taskRows[$name]);
		$this->removeRuntimeTask($name);
		return true;
	}

	/**
	 * taskExists checks for a task name in the database
	 * @param string $name task to check in the database
	 * @throws \Prado\Exceptions\TDbException if the Fields and table is not correct
	 * @return ?bool whether the task name exists in the database table, null when access is denied
	 */
	public function taskExists($name)
	{
		$this->ensureTable();

		$db = $this->getDbConnection();
		$cmd = $db->createCommand(
			"SELECT COUNT(*) AS count, username FROM {$this->_tableName} WHERE name=:name AND active IS NOT NULL"
		);
		$cmd->bindParameter(":name", $name, $db::PARAM_STR);
		$data = $cmd->queryRow();
		if ($this->dyGetUserTasks(false, $data['username'], $name, ['extra' => ['username' => $data['username']]]) === true) {
			$this->dyLogPermissionFailed(static::PERM_CRON_READ_TASKS, 'task exists \'' . $name . '\'');
			return null;
		}
		return $data['count'] > 0;
	}

	/**
	 * taskExistsInternal checks for a task name without permissions
	 * @param string $name task to check in the database
	 * @throws \Prado\Exceptions\TDbException if the Fields and table is not correct
	 * @return bool whether the task name exists in the database table
	 */
	protected function taskExistsInternal($name)
	{
		$this->ensureTable();

		$db = $this->getDbConnection();
		$cmd = $db->createCommand(
			"SELECT COUNT(*) AS count FROM {$this->_tableName} WHERE name=:name AND active IS NOT NULL"
		);
		$cmd->bindParameter(":name", $name, $db::PARAM_STR);
		return $cmd->queryScalar() > 0;
	}

	/**
	 * Normalizes a time period to a \DateInterval.  A numeric value is seconds; a string is an ISO 8601
	 * duration, eg. "P1Y6M3DT12H20M30S", or a relative date string, eg. "1 year 6 months 3 days".
	 * @param \DateInterval|int|string $period the period
	 * @throws TInvalidDataValueException when the string is not a period
	 * @return \DateInterval the period as an interval
	 * @since 4.4.0
	 */
	protected function toDateInterval($period): DateInterval
	{
		if ($period instanceof DateInterval) {
			return $period;
		}
		if (is_numeric($period)) {
			return new DateInterval('PT' . ((int) $period) . 'S');
		}
		$period = trim((string) $period);
		try {
			$di = str_starts_with($period, 'P') ? new DateInterval($period) : null;
		} catch (\Exception $e) {
			$di = null;
		}
		if ($di === null) {
			try {
				$di = @DateInterval::createFromDateString($period) ?: new DateInterval($period);
			} catch (\Exception $e) {
				throw new TInvalidDataValueException('dbcron_period_invalid', $period);
			}
		}
		return $di;
	}

	/**
	 * deletes the cron log items before time minus $period.  The $period can be null
	 * for all records regardless of time, an integer number of seconds, a string for
	 * \DateInterval in either short format, eg. "P1Y6M3DT12H20M30S", or text "1 year 6 months
	 * 3 days 12 hours 20 minutes 30 seconds", or a \DateInterval.
	 * @param null|\DateInterval|int|string $period the period before Now, default null
	 * @param ?string $name name of the tasks to clear, _ is wild card and % is wild string
	 * @param ?string $username name of the user of tasks to clear, _ is wild card and % is wild string
	 * @return ?int number of log records flushed, null when access is denied
	 */
	public function clearCronLog($period = null, $name = null, $username = null)
	{
		if ($period !== null) {
			$period = $this->toDateInterval($period);
		}
		if ($this->dyClearCronLog(false, $period, ['extra' => ['username' => $username]]) === true) {
			$this->dyLogPermissionFailed(static::PERM_CRON_LOG_DELETE, 'flushing cron log');
			return null;
		}
		$this->ensureTable();

		$where = '';
		$time = null;
		if ($period !== null) {
			$where = 'lastexectime <= :time AND ';
			$time = (new \DateTimeImmutable('@' . $this->time()))->sub($period)->getTimestamp();
		}
		if (is_string($username)) {
			$defaultuser = $this->matchesDefaultUserName($username) ? ' OR username IS NULL' : '';
			$where .= "(username LIKE :uname{$defaultuser}) AND ";
		}
		if (is_string($name)) {
			$where .= 'name LIKE :name AND ';
		}
		$db = $this->getDbConnection();
		$bind = static function ($cmd) use ($db, $time, $username, $name) {
			if ($time !== null) {
				$cmd->bindValue(":time", $time, $db::PARAM_STR);
			}
			if (is_string($username)) {
				$cmd->bindValue(":uname", $username, $db::PARAM_STR);
			}
			if (is_string($name)) {
				$cmd->bindValue(":name", $name, $db::PARAM_STR);
			}
		};
		$cmd = $db->createCommand(
			"SELECT COUNT(*) FROM {$this->_tableName} WHERE {$where}active IS NULL"
		);
		$bind($cmd);
		$count = (int) $cmd->queryScalar();
		$cmd = $db->createCommand(
			"DELETE FROM {$this->_tableName} WHERE {$where}active IS NULL"
		);
		$bind($cmd);
		$cmd->execute();

		return $count;
	}

	/**
	 * Deletes one cron log item from the database
	 * @param int $taskUID
	 * @return ?bool whether the item was deleted, null when access is denied
	 */
	public function removeCronLogItem($taskUID)
	{
		$this->ensureTable();
		$taskUID = (int) $taskUID;

		$db = $this->getDbConnection();
		$cmd = $db->createCommand(
			"SELECT username FROM {$this->_tableName} WHERE active IS NULL AND tabuid = :uid"
		);
		$cmd->bindParameter(":uid", $taskUID, $db::PARAM_INT);
		$username = $cmd->queryScalar();

		if ($username === null || $username === false) {
			$username = $this->getDefaultUserName();
		}

		if ($this->dyRemoveCronLogItem(false, $taskUID, ['extra' => ['username' => $username]]) === true) {
			$this->dyLogPermissionFailed(static::PERM_CRON_LOG_DELETE, 'deleting a cron log item');
			return null;
		}

		$cmd = $db->createCommand(
			"DELETE FROM {$this->_tableName} WHERE active IS NULL AND tabuid = :uid"
		);
		$cmd->bindParameter(":uid", $taskUID, $db::PARAM_INT);
		$cmd->execute();
		return true;
	}

	/**
	 * Both $name and $username can use _ for wild card and % for wild string.
	 * @param ?string $name filter the cron log by task name, null for all. Default null.
	 * @param ?string $username filter the cron log by user name, null for all. Default null.
	 * @return ?int the number of log items of all or of $name, null when access is denied
	 */
	public function getCronLogCount($name = null, $username = null)
	{
		if ($this->dyGetCronLogCount(false, $name, $username, ['extra' => ['username' => $username]]) === true) {
			$this->dyLogPermissionFailed(static::PERM_CRON_LOG_READ, 'reading cron log');
			return null;
		}
		$this->ensureTable();

		$db = $this->getDbConnection();
		$where = '';
		if (is_string($username)) {
			$defaultuser = $this->matchesDefaultUserName($username) ? ' OR username IS NULL' : '';
			$where .= "(username LIKE :uname{$defaultuser}) AND ";
		}
		if (is_string($name)) {
			$where .= 'name LIKE :name AND ';
		}
		$cmd = $db->createCommand(
			"SELECT COUNT(*) AS count FROM {$this->_tableName} WHERE {$where}active IS NULL"
		);
		if (is_string($username)) {
			$cmd->bindParameter(":uname", $username, $db::PARAM_STR);
		}
		if (is_string($name)) {
			$cmd->bindParameter(":name", $name, $db::PARAM_STR);
		}
		return (int) $cmd->queryScalar();
	}

	/**
	 * Gets the cron log table of specific named or all tasks.
	 * $name and $username can use _ as a wild card and % as wild string.
	 * @param ?string $name name of the tasks to get from the log, or null for
	 *    all, _ is wild card and % is wild string, default null.
	 * @param int $pageSize the number of log items, 0 for all, default 0
	 * @param int $offset the number of log items to skip, default 0
	 * @param ?bool $sortingDesc sort by descending execution time; null sorts descending
	 *    when paging and leaves the order unspecified otherwise.
	 * @param ?string $username username of the tasks to get from the log, or null
	 *    for all, _ is wild card and % is wild string, default null.
	 * @return ?array the log rows, null when access is denied
	 */
	public function getCronLog($name = null, $pageSize = 0, $offset = 0, $sortingDesc = null, $username = null)
	{
		if ($this->dyGetCronLog(false, $name, $pageSize, $offset, $sortingDesc, $username, ['extra' => ['username' => $username]]) === true) {
			$this->dyLogPermissionFailed(static::PERM_CRON_LOG_READ, 'reading cron log');
			return null;
		}
		$this->ensureTable();

		$db = $this->getDbConnection();

		$orderby = $where = '';
		if (is_string($username)) {
			$defaultuser = $this->matchesDefaultUserName($username) ? ' OR username IS NULL' : '';
			$where .= "(username LIKE :uname{$defaultuser}) AND ";
		}
		if (is_string($name)) {
			$where .= 'name LIKE :name AND ';
		}
		$limit = $this->limitClause($pageSize, $offset);
		if ($limit !== '') {
			$sortingDesc ??= true;
		}
		if ($sortingDesc !== null) {
			$sortingDesc = TPropertyValue::ensureBoolean($sortingDesc) ? 'DESC' : 'ASC';
			$orderby = " ORDER BY lastExecTime $sortingDesc, tabuid $sortingDesc, processCount $sortingDesc";
		}
		$cmd = $db->createCommand(
			"SELECT * FROM {$this->_tableName} WHERE {$where}active IS NULL{$orderby}{$limit}"
		);
		if (is_string($username)) {
			$cmd->bindParameter(":uname", $username, $db::PARAM_STR);
		}
		if (is_string($name)) {
			$cmd->bindParameter(":name", $name, $db::PARAM_STR);
		}
		$results = $cmd->query();
		return $results->readAll();
	}

	/**
	 * Sets the ID of a TDataSourceConfig module.
	 * The datasource module is used to establish the DB connection for this cron module.
	 * @param string $value ID of the {@see \Prado\Data\TDataSourceConfig} module
	 * @throws TInvalidOperationException when trying to set this property but the module is already initialized.
	 */
	public function setConnectionID($value)
	{
		$this->assertUninitialized('ConnectionID');
		$this->setTraitConnectionID($value);
	}

	/**
	 * @return string the error message key when createDbConnection could not find the ConnectionID.
	 */
	protected function getConnectionInvalidExceptionKey(): string
	{
		return 'dbcron_connectionid_invalid';
	}

	/**
	 * @return string the SQLite database filename within the PRADO runtime path.
	 */
	protected function getSqliteDatabaseName(): string
	{
		return 'cron.jobs';
	}

	/**
	 * @return bool should tasks that run be logged, default true
	 */
	public function getLogCronTasks()
	{
		return $this->_logCronTasks;
	}

	/**
	 * @param bool $log should tasks that run be logged
	 */
	public function setLogCronTasks($log)
	{
		$this->_logCronTasks = TPropertyValue::ensureBoolean($log);
	}

	/**
	 * @return string table in the database for cron tasks and logs. Defaults to 'crontabs'
	 */
	public function getTableName()
	{
		return $this->_tableName;
	}

	/**
	 * @param string $table table in the database for cron tasks and logs, an SQL identifier
	 * @throws TInvalidOperationException when trying to set this property but the module is already initialized.
	 * @throws \Prado\Exceptions\TInvalidDataValueException if the name is not an SQL identifier
	 */
	public function setTableName($table)
	{
		$this->assertUninitialized('TableName');
		$this->_tableName = $this->ensureSqlIdentifier(TPropertyValue::ensureString($table), 'TableName');
	}

	/**
	 * @return bool whether the cron DB table should be automatically created if not exists. Defaults to true.
	 * @see setTableName
	 */
	public function getAutoCreateCronTable()
	{
		return $this->_autoCreate;
	}

	/**
	 * @param bool $value whether the cron DB table should be automatically created if not exists.
	 * @throws TInvalidOperationException when trying to set this property but the module is already initialized.
	 * @see setTableName
	 */
	public function setAutoCreateCronTable($value)
	{
		$this->assertUninitialized('AutoCreateCronTable');
		$this->_autoCreate = TPropertyValue::ensureBoolean($value);
	}
}
