<?php
/**
 * TDbCronModule class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

use Exception;
use PDO;
use Prado\Security\Permissions\TPermissionEvent;
use Prado\Security\Permissions\TUserOwnerRule;
use Prado\Data\TDataSourceConfig;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Util\TLogger;

/**
 * TDbCronModule class.
 *
 * TDbCronModule does everything that TCronModule does but stores the tasks and
 * persistent data in its own database table.
 *
 * The TDbCronModule allows for adding, updating, and removing tasks from the
 * application and shell.  It can log executing tasks to the table as well.
 *
 * There are log maintenance methods and {@link TDbCronCleanLogTask} for cleaning
 * the cron logs.
 *
 * Runtime Tasks can be added for execution onEndRequest.  Singleton tasks can
 * be added to TDbCronModule, and scheduled to execute during Runtime at
 * onEndRequest.  Then if it does not execute onEndRequest, then the next
 * shell cron will execute the task.  This could occur if the user presses stop
 * before the page completes.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 * @method bool dyClearCronLog(bool $return, int $seconds)
 * @method bool dyGetCronLog(bool $return, string $name, int $pageSize, int $offset, string $sortingDesc)
 * @method bool dyGetCronLogCount(bool $return, string $name)
 * @method bool dyRemoveCronLogItem(bool $return, int $taskUID)
 * @method bool dyAddTask(bool $return,\Prado\Util\Cron\TCronTask $task, bool $runtime)
 * @method bool dyUpdateTask(bool $return, \Prado\Util\Cron\TCronTask $task, array $extraData)
 * @method bool dyRemoveTask(bool $return, \Prado\Util\Cron\TCronTask|string $untask, array $extraData)
 */
class TDbCronModule extends TCronModule implements \Prado\Util\IDbModule
{
	/** Name Regular Expression, no spaces, single or double quotes, less than or greater than, no percent, and cannot start with star */
	public const NAME_VALIDATOR_REGEX = '/^[^\s`\'\"\\*<>%][^\s`\'\"<>%]*$/i';

	public const PERM_CRON_LOG_READ = 'cron_log_read';

	public const PERM_CRON_LOG_DELETE = 'cron_log_delete';

	public const PERM_CRON_ADD_TASK = 'cron_add_task';

	public const PERM_CRON_UPDATE_TASK = 'cron_update_task';

	public const PERM_CRON_REMOVE_TASK = 'cron_remove_task';

	/** @var string name of the db table for cron tasks, default 'crontabs' */
	private $_tableName = 'crontabs';

	/** @var bool auto create the db table for cron, default true */
	private $_autoCreate = true;

	/** @var bool has the table been verified to be in the DB */
	private $_tableEnsured = false;

	/** @var bool log the cron tasks, in the table, as they run, default true  */
	private $_logCronTasks = true;

	/** @var array[]|TCronTask[] the tasks created from the (parent) application configuration */
	private $_configTasks;

	/** @var bool are the tasks Initialized */
	private $_tasksInitialized = false;

	/** @var array[]|TCronTask[] the tasks manually added to the database */
	private $_tasks = [];

	/** @var array[] the row data from the database */
	private $_taskRows;

	/** @var string the ID of TDataSourceConfig module  */
	private $_connID = '';

	/**  @var TDbConnection the DB connection instance  */
	private $_conn;

	/** @var TCronTask[] */
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
	 * Initializes the module. Keeps track of the configured tasks different than db tasks.
	 * @param array|\Prado\Xml\TXmlElement $config
	 */
	public function init($config)
	{
		parent::init($config);

		$this->_configTasks = parent::getRawTasks();
	}

	/**
	 * the global event handling requests for cron task info
	 * @param TDbCronModule $cron
	 * @param null $param
	 */
	public function fxGetCronTaskInfos($cron, $param)
	{
		return new TCronTaskInfo('cronclean', \Prado\Util\Cron\TDbCronCleanLogTask::class, $this->getId(), Prado::localize('DbCron Clean Log Task'), Prado::localize('Clears the database of cron log items before the specified time period.'));
	}

	/**
	 * @param \Prado\Security\Permissions\TPermissionsManager $manager
	 * @return \Prado\Security\Permissions\TPermissionEvent[]
	 */
	public function getPermissions($manager)
	{
		$userIsOwnerAllowedRule = new TUserOwnerRule();
		return array_merge([
			new TPermissionEvent(static::PERM_CRON_LOG_READ, 'Cron read Db log.', ['dyGetCronLog', 'dyGetCronLogCount']),
			new TPermissionEvent(static::PERM_CRON_LOG_DELETE, 'Cron delete Db log.', ['dyClearCronLog', 'dyRemoveCronLogItem']),
			new TPermissionEvent(static::PERM_CRON_ADD_TASK, 'Cron add Db Task.', ['dyAddTask']),
			new TPermissionEvent(static::PERM_CRON_UPDATE_TASK, 'Cron update Db task.', ['dyUpdateTask'], $userIsOwnerAllowedRule),
			new TPermissionEvent(static::PERM_CRON_REMOVE_TASK, 'Cron remove Db task.', ['dyRemoveTask'], $userIsOwnerAllowedRule),
		], parent::getPermissions($manager));
	}

	/**
	 * This checks for "name".  The name cannot by '*' or have spaces, `, ', ", <, or >t characters.
	 * @param array $properties the task as an array of properties
	 * @throws TConfigurationException when the name is invalid.
	 */
	public function validateTask($properties)
	{
		$name = $properties[parent::NAME_KEY] ?? '';
		if (!preg_match(TDbCronModule::NAME_VALIDATOR_REGEX, $name)) {
			throw new TConfigurationException('dbcron_invalid_name', $name);
		}
		parent::validateTask($properties);
	}

	/**
	 * Sets the lastExecTime and processCount for a task from the DB.
	 * Also resets the lastExecTime when not in the DB
	 * @param string $name name of the task to get Persistent Data from
	 * @param object $task
	 * @return bool is the persistent data set or not
	 */
	protected function setPersistentData($name, $task)
	{
		if (isset($this->_taskRows[$name])) {
			$task->setLastExecTime($this->_taskRows[$name]['lastexectime']);
			$task->setProcessCount((int) $this->_taskRows[$name]['processcount']);
			if (serialize($task) !== $this->_taskRows[$name]['options']) {
				$this->updateTaskInternal($task);
				return false;
			}
			return true;
		} else {
			$this->addTaskInternal($task);
		}
		return false;
	}

	/**
	 * reads in all the tasks from the db, instances them if they are active db tasks.
	 * otherwise the rows are kept for persistent data.
	 * @param bool $initConfigTasks initialize the configuration
	 * @return \Prado\Util\Cron\TCronTask[]
	 */
	protected function ensureTasks($initConfigTasks = true)
	{
		if ($this->_tasksInitialized !== true) {
			$this->ensureTable();
			$this->_taskRows = $this->_tasks = [];
			$cmd = $this->getDbConnection()->createCommand(
				"SELECT * FROM {$this->_tableName} WHERE active IS NOT NULL"
			);
			$results = $cmd->query();

			Prado::log('Reading DB Cron Configuration', TLogger::NOTICE, TDbCronModule::class);
			foreach ($results->readAll() as $data) {
				if ($data['active']) {
					$task = $this->_tasks[$data['name']] = @unserialize($data['options']);
				}
				$this->_taskRows[$data['name']] = $data;
			}
			$this->_tasksInitialized = true;
		}
		if ($initConfigTasks) {
			$this->_configTasks = parent::ensureTasks();
		}
		return array_merge($this->_tasks, $this->_configTasks ?? []);
	}

	/**
	 * @throws TConfigurationException when the configuration task names interfere with the db tasks names.
	 * @return array[TCronTask] combines the active configuration and db cron tasks
	 */
	public function getTasks()
	{
		$this->ensureTasks();
		if ($colliding = array_intersect_key($this->_tasks, $this->_configTasks)) {
			throw new TConfigurationException('dbcron_conflicting_task_names', implode(', ', array_keys($colliding)));
		}
		return array_merge($this->_tasks, $this->_configTasks);
	}

	/**
	 * checks for the table, and if not there and autoCreate, then creates the table else throw error.
	 * @throws TConfigurationException if the table does not exist and cannot autoCreate
	 */
	protected function ensureTable()
	{
		if ($this->_tableEnsured) {
			return;
		}
		$db = $this->getDbConnection();
		$sql = 'SELECT * FROM ' . $this->_tableName . ' WHERE 0=1';
		try {
			$db->createCommand($sql)->query()->close();
		} catch (Exception $e) {
			// DB table not exists
			if ($this->_autoCreate) {
				$this->createDbTable();
			} else {
				throw new TConfigurationException('dbcron_table_nonexistent', $this->_tableName);
			}
		}
		$this->_tableEnsured = true;
	}


	/**
	 * creates the module table
	 */
	protected function createDbTable()
	{
		$db = $this->getDbConnection();
		$driver = $db->getDriverName();
		$autotype = 'INTEGER';
		$autoidAttributes = '';
		if ($driver === 'mysql') {
			$autoidAttributes = ' AUTO_INCREMENT';
		} elseif ($driver === 'sqlite') {
			$autoidAttributes = ' AUTOINCREMENT';
		} elseif ($driver === 'postgresql') {
			$autotype = 'SERIAL';
		}
		$postIndices = '; CREATE INDEX tname ON ' . $this->_tableName . '(`name`);' .
			'CREATE INDEX tclass ON ' . $this->_tableName . '(`task`);' .
			'CREATE INDEX tactive ON ' . $this->_tableName . '(`active`);';

		$sql = 'CREATE TABLE IF NOT EXISTS ' . $this->_tableName . ' (
			`tabuid` ' . $autotype . ' PRIMARY KEY' . $autoidAttributes . ', 
			`name` VARCHAR (127) NOT NULL, 
			`schedule` VARCHAR (127) NOT NULL, 
			`task` VARCHAR (256) NOT NULL, 
			`moduleid` VARCHAR (127) NULL, 
			`username` VARCHAR (127) NULL, 
			`options` MEDIUMTEXT NULL, 
			`processcount` INT NOT NULL DEFAULT 0, 
			`lastexectime` VARCHAR (20) NULL DEFAULT `0`, 
			`active` BOOLEAN NULL
			)' . $postIndices;

		//`lastexectime` DECIMAL(12,8) NULL DEFAULT 0,

		$cmd = $this->getDbConnection()->createCommand($sql);

		$cmd->execute();
	}

	/**
	 * logCronTask adds a task log to the table.
	 * @param TCronTask $task
	 * @param string $username
	 */
	protected function logCronTask($task, $username)
	{
		parent::logCronTask($task, $username);

		$app = $this->getApplication();

		$logid = null;
		if ($this->getLogCronTasks()) {
			$this->ensureTable();

			$cmd = $this->getDbConnection()->createCommand(
				"INSERT INTO {$this->_tableName} " .
					"(name, schedule, task, moduleid, username, options, processcount, lastexectime, active)" .
					" VALUES (:name, :schedule, :task, :mid, :username, :options, :count, :time, NULL)"
			);
			$cmd->bindValue(":name", $task->getName(), PDO::PARAM_STR);
			$cmd->bindValue(":task", $task->getTask(), PDO::PARAM_STR);
			$cmd->bindValue(":schedule", $task->getSchedule(), PDO::PARAM_STR);
			$cmd->bindValue(":mid", $task->getModuleId(), PDO::PARAM_STR);
			$cmd->bindValue(":username", $username, PDO::PARAM_STR);
			$cmd->bindValue(":options", serialize($task), PDO::PARAM_STR);
			$cmd->bindValue(":count", $task->getProcessCount(), PDO::PARAM_INT);
			$cmd->bindValue(":time", (int) microtime(true), PDO::PARAM_STR);
			$cmd->execute();
			$logid = $this->getDbConnection()->getLastInsertID();
		}
		return $logid;
	}

	/**
	 * This updates the LastExecTime and ProcessCount in the database
	 * @param TCronTask $task
	 */
	protected function updateTaskInfo($task)
	{
		$task->setLastExecTime($time = (int) microtime(true));
		$task->setProcessCount($count = ($task->getProcessCount() + 1));

		$cmd = $this->getDbConnection()->createCommand(
			"UPDATE {$this->_tableName} SET processcount=:count, lastexectime=:time, options=:task WHERE name=:name AND active IS NOT NULL"
		);
		$cmd->bindValue(":count", $count, PDO::PARAM_STR);
		$cmd->bindValue(":time", $time, PDO::PARAM_STR);
		$cmd->bindValue(":task", serialize($task), PDO::PARAM_STR);
		$cmd->bindValue(":name", $task->getName(), PDO::PARAM_STR);
		$cmd->execute();
	}

	/**
	 * this removes any stale database rows from changing configTasks
	 */
	protected function filterStaleTasks()
	{
		$this->ensureTasks();
		$configTasks = $this->_taskRows;

		//remove non-configuration tasks
		foreach ($this->_taskRows as $name => $data) {
			if ($data['active']) {
				unset($configTasks[$name]);
			}
		}

		//remove configuration tasks
		foreach ($this->_configTasks as $name => $data) {
			unset($configTasks[$name]);
		}

		//remaining are stale
		if (count($configTasks)) {
			foreach ($configTasks as $name => $task) {
				$this->removeTaskInternal($name);
			}
		}
	}

	/**
	 * This executes the Run Time Tasks, this method is automatically added
	 * to TApplication::onEndRequest when there are RuntimeTasks via {@link addRuntimeTask}.
	 * @param null|\Prado\TApplication $sender
	 * @param null|mixed $param
	 * @return int number of tasks run
	 */
	public function executeRuntimeTasks($sender = null, $param = null)
	{
		$runtimeTasks = $this->getRuntimeTasks();
		if (!$runtimeTasks) {
			return;
		}
		$numtasks = count($runtimeTasks);
		$cronlogger = $this->asa(TCronModule::SHELL_LOG_BEHAVIOR);
		if ($cronlogger) {
			$enabled = $cronlogger->getEnabled();
			$cronlogger->setEnabled(false);
		}
		foreach ($runtimeTasks as $key => $task) {
			$this->runTask($task);
		}
		if ($cronlogger) {
			$cronlogger->setEnabled($enabled);
		}
		return $numtasks;
	}

	/**
	 * Adds a task to being run time.  If this is the first runtime task this
	 * method adds {@link executeRuntimeTasks} to TApplication::onEndRequest.
	 * @param TCronTask $task
	 */
	public function addRuntimeTask($task)
	{
		if ($this->_runtimeTasks === null) {
			Prado::getApplication()->attachEventHandler('onEndRequest', [$this, 'executeRuntimeTasks']);
			$this->_runtimeTasks = [];
		}
		$this->_runtimeTasks[$task->getName()] = $task;
	}

	/**
	 * Gets the runtime tasks.
	 * @return \Prado\Util\Cron\TCronTask the tasks to run on {@link executeRuntimeTasks}
	 */
	public function getRuntimeTasks()
	{
		return $this->_runtimeTasks;
	}

	/**
	 * Removes a task from being run time.  If there are no runtime tasks left
	 * then it removes {@link executeRuntimeTasks} from TApplication::onEndRequest.
	 * @param TCronTask $untask
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
			Prado::getApplication()->detachEventHandler('onEndRequest', [$this, 'executeRuntimeTasks']);
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
		Prado::getApplication()->detachEventHandler('onEndRequest', [$this, 'executeRuntimeTasks']);
	}

	/**
	 *
	 * @param string $taskName
	 * @param bool $checkExisting
	 * @param bool $asObject returns the database row if false.
	 */
	public function getTask($taskName, $checkExisting = true, $asObject = true)
	{
		$this->ensureTable();

		if ($checkExisting) {
			$this->ensureTasks();
			if ($asObject) {
				if (isset($this->_tasks[$taskName])) {
					return $this->_tasks[$taskName];
				}
				if (isset($this->_configTasks[$taskName])) {
					return $this->_configTasks[$taskName];
				}
			} else {
				if (isset($this->_taskRows[$taskName])) {
					return $this->_taskRows[$taskName];
				}
			}
		}


		$cmd = $this->getDbConnection()->createCommand(
			"SELECT * FROM {$this->_tableName} WHERE name=:name AND active IS NOT NULL LIMIT 1"
		);
		$cmd->bindValue(":name", $taskName, PDO::PARAM_STR);

		$result = $cmd->queryRow();

		if (!$result) {
			return null;
		}

		if ($asObject) {
			return @unserialize($result['options']);
		}

		return $result;
	}

	/**
	 * Adds a task to the database.  Validates the name and cannot add a task with an existing name.
	 * This updates the table row data as well.
	 * @param TCronTask $task
	 * @param bool $runtime should the task be added to the Run Time Task after being added
	 * @return bool was the task added
	 */
	public function addTask($task, $runtime = false)
	{
		if ($this->dyAddTask(false, $task, $runtime) === true) {
			return false;
		}
		return $this->addTaskInternal($task, $runtime);
	}

	/**
	 * Adds a task to the database.  Validates the name and cannot add a task with an existing name.
	 * This updates the table row data as well.
	 * @param \Prado\Util\Cron\TCronTask $task
	 * @param bool $runtime should the task be added to the Run Time Task after being added
	 * @return bool was the task added
	 */
	protected function addTaskInternal($task, $runtime = false)
	{
		$this->ensureTable();
		$this->ensureTasks(false);
		$name = $task->getName();
		if (!preg_match(TDbCronModule::NAME_VALIDATOR_REGEX, $name)) {
			return false;
		}
		if (isset($this->_tasks[$name])) {
			return false;
		}
		try {
			$task->getScheduler();
		} catch (TInvalidDataValueException $e) {
			return false;
		}
		$task->resetTaskLastExecTime();

		$cmd = $this->getDbConnection()->createCommand(
			"INSERT INTO {$this->_tableName} " .
				"(name, schedule, task, moduleid, username, options, lastexectime, processcount, active)" .
				" VALUES (:name, :schedule, :task, :mid, :username, :options, :time, :count, :active)"
		);
		$cmd->bindValue(":name", $name, PDO::PARAM_STR);
		$cmd->bindValue(":schedule", $schedule = $task->getSchedule(), PDO::PARAM_STR);
		$cmd->bindValue(":task", $taskExec = $task->getTask(), PDO::PARAM_STR);
		$cmd->bindValue(":mid", $mid = $task->getModuleId(), PDO::PARAM_STR);
		$cmd->bindValue(":username", $username = $task->getUserName(), PDO::PARAM_STR);
		$cmd->bindValue(":options", $serial = serialize($task), PDO::PARAM_STR);
		$cmd->bindValue(":time", $time = $task->getLastExecTime(), PDO::PARAM_STR);
		$cmd->bindValue(":count", $count = $task->getProcessCount(), PDO::PARAM_INT);
		$cmd->bindValue(":active", $active = (isset($this->_configTasks[$name]) ? '0' : '1'), PDO::PARAM_INT);
		$cmd->execute();

		if ($this->_tasks !== null && !isset($this->_configTasks[$name])) {
			$this->_tasks[$name] = $task;
			$this->_taskRows[$name] = [];
			$this->_taskRows[$name]['name'] = $name;
			$this->_taskRows[$name]['schedule'] = $schedule;
			$this->_taskRows[$name]['task'] = $taskExec;
			$this->_taskRows[$name]['moduleid'] = $mid;
			$this->_taskRows[$name]['username'] = $username;
			$this->_taskRows[$name]['options'] = $serial;
			$this->_taskRows[$name]['processcount'] = $count;
			$this->_taskRows[$name]['lastexectime'] = $time;
			$this->_taskRows[$name]['active'] = $active;
		}
		if ($runtime) {
			$this->addRuntimeTask($task);
		}
		return true;
	}

	/**
	 * Updates a task from its unique name.  If the Task is not in the DB it returns false
	 * @param TCronTask $task
	 * @return bool was the task updated
	 */
	public function updateTask($task)
	{
		if ($this->dyUpdateTask(false, $task, ['extra' => ['username' => $task->getUserName()]]) === true) {
			return false;
		}
		return $this->updateTaskInternal($task);
	}

	/**
	 * Updates a task from its unique name.  If the Task is not in the DB it returns false
	 * @param \Prado\Util\Cron\TCronTask $task
	 * @return bool was the task updated
	 */
	protected function updateTaskInternal($task)
	{
		$this->ensureTable();
		$this->ensureTasks(false);
		$name = $task->getName();
		if (!$this->taskExists($name)) {
			return false;
		}
		try {
			$task->getScheduler();
		} catch (TInvalidDataValueException $e) {
			return false;
		}
		$schedule = $task->getSchedule();
		if ($schedule != $this->_taskRows[$name]['schedule']) {
			$task->resetTaskLastExecTime();
		}

		$cmd = $this->getDbConnection()->createCommand(
			"UPDATE {$this->_tableName} SET schedule=:schedule, task=:task, moduleid=:mid, username=:username, options=:options, processcount=:count, lastexectime=:time WHERE name=:name AND active IS NOT NULL"
		);
		$cmd->bindValue(":schedule", $schedule, PDO::PARAM_STR);
		$cmd->bindValue(":task", $taskExec = $task->getTask(), PDO::PARAM_STR);
		$cmd->bindValue(":mid", $mid = $task->getModuleId(), PDO::PARAM_STR);
		$cmd->bindValue(":username", $username = $task->getUserName(), PDO::PARAM_STR);
		$cmd->bindValue(":options", $serial = serialize($task), PDO::PARAM_STR);
		$cmd->bindValue(":count", $count = $task->getProcessCount(), PDO::PARAM_STR);
		$cmd->bindValue(":time", $time = $task->getLastExecTime(), PDO::PARAM_STR);
		$cmd->bindValue(":name", $name, PDO::PARAM_STR);
		$cmd->execute();

		if ($this->_tasks !== null) {
			$this->_taskRows[$name]['schedule'] = $schedule;
			$this->_taskRows[$name]['task'] = $taskExec;
			$this->_taskRows[$name]['moduleid'] = $mid;
			$this->_taskRows[$name]['username'] = $username;
			$this->_taskRows[$name]['options'] = $serial;
			$this->_taskRows[$name]['processcount'] = $count;
			$this->_taskRows[$name]['lastexectime'] = $time;
		}
		return true;
	}

	/**
	 * Removes a task from the database table.
	 * This also removes the task from the current tasks, the taskRow, and runtime Tasks.
	 *
	 * This cannot remove tasks that are current configuration tasks.  Only tasks
	 * that exist can be removed.
	 * @param string|TCronTask $untask the task to remove from the DB
	 * @return bool was the task removed
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
			return false;
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
		$this->ensureTasks(false);
		$name = is_subclass_of($untask, \Prado\Util\Cron\TCronTask::class) ? $untask->getName() : $untask;
		if (isset($this->_configTasks[$name])) {
			return false;
		}
		if (!$this->taskExists($name)) {
			return false;
		}

		$cmd = $this->getDbConnection()->createCommand(
			"DELETE FROM {$this->_tableName} WHERE name=:name AND active IS NOT NULL"
		);
		$cmd->bindValue(":name", $name, PDO::PARAM_STR);
		$cmd->execute();

		// Remove task to list of tasks
		unset($this->_tasks[$name]);
		unset($this->_taskRows[$name]);
		$this->removeRuntimeTask($name);
		return true;
	}

	/**
	 * taskExists checks for a task or task name in the database
	 * @param string $name task to check in the database
	 * @throws \Prado\Exceptions\TDbException if the Fields and table is not correct
	 * @return bool whether the task name exists in the database table
	 */
	public function taskExists($name)
	{
		$this->ensureTable();

		$db = $this->getDbConnection();
		$cmd = $db->createCommand(
			"SELECT COUNT(*) AS count FROM {$this->_tableName} WHERE name=:name AND active IS NOT NULL"
		);
		$cmd->bindParameter(":name", $name, PDO::PARAM_STR);
		return $cmd->queryScalar() > 0;
	}

	/**
	 * deletes the cron log items before time minus $seconds.
	 * @param int $seconds the number of seconds before Now
	 */
	public function clearCronLog($seconds)
	{
		if ($this->dyClearCronLog(false, $seconds) === true) {
			return false;
		}
		$this->ensureTable();

		$seconds = (int) $seconds;
		$cmd = $this->getDbConnection()->createCommand(
			"SELECT COUNT(*) FROM {$this->_tableName} WHERE active IS NULL AND lastexectime <= :time"
		);
		$time = time() - $seconds;
		$cmd->bindParameter(":time", $time, PDO::PARAM_STR);
		$count = $cmd->queryScalar();
		$cmd = $this->getDbConnection()->createCommand(
			"DELETE FROM {$this->_tableName} WHERE active IS NULL AND lastexectime <= :time"
		);
		$cmd->bindParameter(":time", $time, PDO::PARAM_STR);
		$cmd->execute();

		return $count;
	}

	/**
	 * Deletes one cron log item from the database
	 * @param int $taskUID
	 */
	public function removeCronLogItem($taskUID)
	{
		if ($this->dyRemoveCronLogItem(false, $taskUID) === true) {
			return false;
		}
		$this->ensureTable();
		$taskUID = (int) $taskUID;

		$cmd = $this->getDbConnection()->createCommand(
			"DELETE FROM {$this->_tableName} WHERE active IS NULL AND tabuid = :uid"
		);
		$cmd->bindParameter(":uid", $taskUID, PDO::PARAM_INT);
		$cmd->execute();
	}

	/**
	 * @param null|string $name name of the logs to look for, or null for all
	 * @return int the number of log items of all or of $name
	 */
	public function getCronLogCount($name = null)
	{
		if ($this->dyGetCronLogCount(false, $name) === true) {
			return false;
		}
		$this->ensureTable();

		$db = $this->getDbConnection();
		$where = '';
		if (is_string($name)) {
			$where = 'name=:name AND ';
		}
		$cmd = $db->createCommand(
			"SELECT COUNT(*) AS count FROM {$this->_tableName} WHERE {$where}active IS NULL"
		);
		if (is_string($name)) {
			$cmd->bindParameter(":name", $name, PDO::PARAM_STR);
		}
		return (int) $cmd->queryScalar();
	}

	/**
	 * Gets the cron log table of specific named or all tasks.
	 * @param null|string $name name of the tasks to get from the log, or null for all
	 * @param int $pageSize
	 * @param int $offset
	 * @param null|bool $sortingDesc sort by descending execution time.
	 */
	public function getCronLog($name, $pageSize, $offset, $sortingDesc = null)
	{
		if ($this->dyGetCronLog(false, $name, $pageSize, $offset, $sortingDesc) === true) {
			return false;
		}
		$this->ensureTable();

		$db = $this->getDbConnection();
		$driver = $db->getDriverName();

		$limit = $orderby = $where = '';
		if (is_string($name)) {
			$where = 'name=:name AND ';
		}
		$pageSize = (int) $pageSize;
		$offset = (int) $offset;
		if ($pageSize !== 0) {
			if ($offset !== 0) {
				if ($driver === 'postgresql') {
					$limit = " LIMIT {$pageSize} OFFSET {$offset}";
				} else {
					$limit = " LIMIT {$offset}, {$pageSize}";
				}
			} else {
				$limit = " LIMIT {$pageSize}";
			}
			$sortingDesc ??= true;
		}
		if ($sortingDesc !== null) {
			$sortingDesc = TPropertyValue::ensureBoolean($sortingDesc) ? "DESC" : "ASC";
			$orderby = " ORDER BY lastExecTime $sortingDesc, processCount $sortingDesc";
		}
		$cmd = $db->createCommand(
			"SELECT * FROM {$this->_tableName} WHERE {$where}active IS NULL{$orderby}{$limit}"
		);
		if (is_string($name)) {
			$cmd->bindParameter(":name", $name, PDO::PARAM_STR);
		}
		$results = $cmd->query();
		return $results->readAll();
	}

	/**
	 * Creates the DB connection. If no ConnectionId is provided, then this
	 * creates a sqlite database in runtime named 'cron.jobs'.
	 * @throws TConfigurationException if module ID is invalid or empty
	 * @return \Prado\Data\TDbConnection the created DB connection
	 */
	protected function createDbConnection()
	{
		if ($this->_connID !== '') {
			$config = $this->getApplication()->getModule($this->_connID);
			if ($config instanceof TDataSourceConfig) {
				return $config->getDbConnection();
			} else {
				throw new TConfigurationException('dbcron_connectionid_invalid', $this->_connID);
			}
		} else {
			$db = new TDbConnection();
			// default to SQLite3 database
			$dbFile = $this->getApplication()->getRuntimePath() . DIRECTORY_SEPARATOR . 'cron.jobs';
			$db->setConnectionString('sqlite:' . $dbFile);
			return $db;
		}
	}

	/**
	 * @return \Prado\Data\TDbConnection the DB connection instance
	 */
	public function getDbConnection()
	{
		if ($this->_conn === null) {
			$this->_conn = $this->createDbConnection();
			$this->_conn->setActive(true);
		}
		return $this->_conn;
	}

	/**
	 * @return null|string the ID of a {@link TDataSourceConfig} module. Defaults to empty string, meaning not set.
	 */
	public function getConnectionID()
	{
		return $this->_connID;
	}

	/**
	 * Sets the ID of a TDataSourceConfig module.
	 * The datasource module will be used to establish the DB connection for this cron module.
	 * @param string $value ID of the {@link TDataSourceConfig} module
	 * @throws TInvalidOperationException when trying to set this property but the module is already initialized.
	 */
	public function setConnectionID($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('dbcron_property_unchangeable', 'ConnectionID');
		}
		$this->_connID = $value;
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
	 * @param string $table table in the database for cron tasks and logs
	 * @throws TInvalidOperationException when trying to set this property but the module is already initialized.
	 */
	public function setTableName($table)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('dbcron_property_unchangeable', 'TableName');
		}
		$this->_tableName = TPropertyValue::ensureString($table);
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
		if ($this->_initialized) {
			throw new TInvalidOperationException('dbcron_property_unchangeable', 'AutoCreateCronTable');
		}
		$this->_autoCreate = TPropertyValue::ensureBoolean($value);
	}
}
