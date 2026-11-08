<?php

/**
 * TShellDbCronAction class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

use DateInterval;
use DateTime;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TApplicationException;
use Prado\Prado;
use Prado\Security\Permissions\TPermissionsManager;
use Prado\Shell\TShellAppAction;
use Prado\Shell\TShellWriter;

/**
 * TShellDbCronAction class.
 *
 * TShellDbCronAction extends {@see TShellCronAction} to implement
 * additional commands {@see addTask add}, {@see updateTask update},
 * and {@see removeTask}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 * @method ?\Prado\Util\Cron\TDbCronManager getCronModule()
 */
class TShellDbCronAction extends TShellCronAction
{
	protected $methods = ['tasks', 'run', 'index', 'add', 'update', 'remove', 'log', 'flush', 'daemon', 'retire', 'stop', 'migrate'];
	protected $parameters = [null, null, null, ['task-name', 'task-id', 'schedule'], ['task-name'], ['task-name'], null, null, null, null, null, null];
	protected $optional = [['task-name or @user'], null, null, ['parameters...'], ['parameters...'], [], ['task-name or @user'], ['task-name or @user or time (e.g. seconds or PHP DateInterval)'], null, ['seconds'], ['seconds'], null];
	protected $description = ['Manages DbCron time-based services',
		'Displays the Cron tasks configured in the application and database. Filter by @user and task name.',
		'Runs the DbCron Pending Tasks.',
		'Displays the registered Cron tasks information.',
		'Adds a Cron task to the database.',
		'Updates a Cron task in the database.',
		'Removes a Cron task from the database.',
		'Displays the Cron log with task name and @user filters; _ is wild card, % is wild string',
		'Flushes the Cron log with time period, task name and @user filters; _ is wild card, % is wild string',
		'Shows whether a cron daemon holds the lease, and whether it is retiring.',
		'Retires the running cron daemon: it hands the lease to the next cron/run tick with no gap.',
		'Stops the running cron daemon at once; the next cron/run tick starts a new one.',
		'Brings the cron table up to date: creates it when missing, adds the 4.4.0 nextexectime column to an older one.',
	];

	private $_user;

	public function __construct()
	{
		/** @var \Prado\Security\Permissions\TUserPermissionsBehavior $user */
		$user = Prado::getApplication()->getUser();
		if ($user && $user->asa(TPermissionsManager::USER_PERMISSIONS_BEHAVIOR)) {
			$this->_user = $user;
			if (!$user->can(TDbCronManager::PERM_CRON_ADD_TASK) && !$user->can(TDbCronManager::PERM_CRON_ADD_TASK_GLOBAL)) {
				$this->removeShellMethod('add');
				$this->removeShellMethod('migrate');
			}
		}
		parent::__construct();
	}

	/**
	 * Overrides parent getModuleClass to return the TDbCronManager class.
	 * @return string the DbCron module class name this shell action manages
	 */
	public function getModuleClass()
	{
		return \Prado\Util\Cron\TDbCronManager::class;
	}

	/**
	 * Brings the cron table up to date through {@see \Prado\Util\Cron\TDbCronManager::migrateTable}: a
	 * missing table is created, and a table created before 4.4.0 gains the `nextexectime` column with
	 * its index and values.  A deployment runs it once; the table is otherwise migrated on first use.
	 * @param string[] $args the arguments to the command line action
	 * @return bool whether the command ran
	 * @since 4.4.0
	 */
	public function actionMigrate($args)
	{
		$module = $this->getCronModule();
		if (!$module) {
			return true;
		}
		$result = $module->migrateTable();
		$table = $module->getTableName();
		$this->_outWriter->writeLine("\n" . match (true) {
			$result['created'] => "Created the cron table {$table}.",
			$result['column'] !== null => "Added the nextexectime column to {$table} and set it on {$result['column']} task row(s).",
			default => "The cron table {$table} is up to date.",
		} . "\n");
		return true;
	}

	/**
	 * Adds a task to the database with its name, task id, schedule, and other properties.
	 * @param array $args command arguments
	 */
	public function actionAdd($args)
	{
		$module = $this->getCronModule();
		$taskName = $args[1] ?? null;
		$id = $args[2] ?? null;
		$schedule = $args[3] ?? null;

		if (!$taskName) {
			$this->_outWriter->writeError("Cannot add a task without a name");
			return true;
		}
		if (!$id) {
			$this->_outWriter->writeError("Cannot add a task without a task id");
			return true;
		}
		if (!$schedule) {
			$this->_outWriter->writeError("Cannot add a task without a schedule");
			return true;
		}

		$exists = $module->taskExists($taskName);
		if ($exists) {
			$this->_outWriter->writeError("'{$taskName}' already exists in the database");
			return true;
		}
		$infos = $module->getTaskInfos();
		$info = null;
		foreach ($infos as $i) {
			if ($i->getName() == $id) {
				$info = $i;
				break;
			}
		}
		if (!$info) {
			$this->_outWriter->writeError("Task ID '{$id}' could not be found");
			return true;
		}
		$s = Prado::createComponent(TTimeScheduler::class);
		try {
			$s->setSchedule($schedule);
		} catch (TInvalidDataValueException $e) {
			$this->_outWriter->writeError("Schedule '{$schedule}' is not a valid schedule");
			return true;
		}

		$task = $module->instanceTask($info->getTask());
		$task->setName($taskName);
		$task->setSchedule($schedule);
		for ($i = 4; $i < count($args); $i++) {
			$parts = explode('=', $args[$i]);
			$parts[0] = trim($parts[0]);
			$property = strtolower($parts[0]);
			if ($task->canSetProperty($property)) {
				$property = 'set' . $property;
				$task->$property(trim($parts[1]));
			} else {
				$this->_outWriter->writeError("Task Property '{$parts[0]}' is not found");
				return true;
			}
		}
		$module->addTask($task);

		$this->_outWriter->writeLine("Task '{$taskName}' was added to the database\n", [TShellWriter::GREEN, TShellWriter::BOLD]);
		return true;
	}

	/**
	 * Updates a task in the database by its name, setting its schedule, username, moduleid, and other properties.
	 * @param array $args command arguments
	 */
	public function actionUpdate($args)
	{
		$module = $this->getCronModule();
		if (!($taskName = ($args[1] ?? null))) {
			$this->_outWriter->writeError("Cannot update a task without a name");
			return true;
		}

		$task = $module->getTask($taskName);
		if (!$task) {
			$this->_outWriter->writeError("Task '{$taskName}' is not found");
			return true;
		}
		if (count($args) < 3) {
			$this->_outWriter->writeError("No given properties to change");
			return true;
		}
		for ($i = 2; $i < count($args); $i++) {
			$parts = explode('=', $args[$i]);
			$parts[0] = trim($parts[0]);
			$property = strtolower($parts[0]);
			if ($task->canSetProperty($property)) {
				if ($property === 'schedule') {
					$s = Prado::createComponent(TTimeScheduler::class);
					try {
						$s->setSchedule($parts[1]);
					} catch (TInvalidDataValueException $e) {
						$this->_outWriter->writeError("Schedule '{$parts[1]}' is not a valid schedule");
						return true;
					}
				}
				$property = 'set' . $property;
				$task->$property(trim($parts[1]));
			} else {
				$this->_outWriter->writeError("Task Property '{$parts[0]}' is not found");
				return true;
			}
		}
		$module->updateTask($task);
		$this->_outWriter->writeLine("Task '{$taskName}' was updated in the database\n", [TShellWriter::GREEN, TShellWriter::BOLD]);
		return true;
	}

	/**
	 * Removes a task from the database by its name.
	 * @param array $args command arguments
	 */
	public function actionRemove($args)
	{
		$module = $this->getCronModule();
		if (!($taskName = $args[1] ?? null)) {
			$this->_outWriter->writeError("Cannot remove a task without a name");
			return true;
		}
		$exists = $module->taskExists($taskName);
		if (!$exists) {
			$this->_outWriter->writeError("'{$taskName}' does not exist in the database");
			return true;
		}
		$result = $module->removeTask($taskName);

		if ($result) {
			$this->_outWriter->writeLine("'{$taskName}' was successfully removed.\n", [TShellWriter::GREEN, TShellWriter::BOLD]);
		} else {
			$this->_outWriter->writeError("'{$taskName}' could not be removed.\n");
		}
		return true;
	}

	/**
	 * Displays the cron log stored in the database, optionally filtered by task name and user.
	 * @param array $args command arguments
	 */
	public function actionLog($args)
	{
		$module = $this->getCronModule();
		$taskName = null;
		$username = null;
		if ($this->_user && !$this->_user->can(TDbCronManager::PERM_CRON_LOG_READ)) {
			$username = $this->_user->getName();
		}
		$c = count($args) - 1;
		while ($c--) {
			if (($arg = $args[$c + 1] ?? false)) {
				if ($arg[0] == '@') {
					$username = substr($arg, 1);
				} else {
					$taskName = $arg;
				}
			}
		}
		if ($taskName && $username) {
			$filter = ", filtered by task name '{$taskName}' and @{$username}";
		} elseif ($taskName) {
			$filter = ", filtered by task name '{$taskName}'";
		} elseif ($username) {
			$filter = ", filtered by @{$username}";
		} else {
			$filter = '';
		}
		$count = $module->getCronLogCount($taskName, $username);
		if ($count === null) {
			return true;
		}
		$this->_outWriter->write("\n  Cron Log found {$count} records", [TShellWriter::BOLD]);
		$this->_outWriter->writeLine($filter . "\n");
		$result = $module->getCronLog($taskName, 0, 0, null, $username);

		$rows = [];
		foreach ($result as $row) {
			$rows[] = [date('Y-m-d H:i:s', $row['lastexectime']), $row['name'], $row['task'], '@' . $row['username'], $row['processcount']];
		}

		$this->_outWriter->write($this->_outWriter->tableWidget(['headers' => ['Exec Time', 'Name', 'Task', 'User', 'Run #'],
			'rows' => $rows]));
		if (!$result) {
			$this->_outWriter->writeLine("    ** Cron Logs are empty. **", [TShellWriter::BLUE, TShellWriter::BOLD]);
		}

		$this->_outWriter->writeLine("");
		return true;
	}

	/**
	 * Flushes the cron log before an optional time period, filtered by task name (with wild cards) and user.
	 * @param array $args command arguments
	 */
	public function actionFlush($args)
	{
		$module = $this->getCronModule();
		$taskName = null;
		$username = null;
		$period = null;
		if ($this->_user && !$this->_user->can(TDbCronManager::PERM_CRON_LOG_DELETE)) {
			$username = $this->_user->getName();
		}
		$c = count($args) - 1;
		while ($c--) {
			if (($arg = $args[$c + 1] ?? false)) {
				if (is_string($arg) && $arg[0] == '@') {
					$username = substr($arg, 1);
				} elseif ($period === null && ($di = $this->parsePeriod($arg)) !== null) {
					$period = $di;
				} else {
					$taskName = $arg;
				}
			}
		}

		$time = $this->time();
		if ($period) {
			$beforeTime = new \DateTime();
			$beforeTime->sub($period);
			$time = $time - abs($time - $beforeTime->getTimestamp());
		}
		$count = $module->clearCronLog($period, $taskName, $username);

		if ($count === null) {
			return true;
		}

		$this->_outWriter->write("\nRemoving Cron Tasks before " . date('Y-m-d H:i:s', $time) .
			($taskName !== null ? " with task name='{$taskName}'" : "") . ($username !== null ? " and user '@{$username}' " : "") . ": ");

		$this->_outWriter->writeLine($count . "\n");

		return true;
	}
	/**
	 * Parses a flush period argument: seconds, an ISO 8601 duration (`P7D`), or a relative date string (`1 week`).
	 * @param string $arg the argument
	 * @return ?DateInterval the period, or null when the argument is not a period
	 * @since 4.4.0
	 */
	protected function parsePeriod(string $arg): ?DateInterval
	{
		$arg = trim($arg);
		if (is_numeric($arg)) {
			return new DateInterval('PT' . ((int) $arg) . 'S');
		}
		try {
			return str_starts_with($arg, 'P') ? new DateInterval($arg) : (@DateInterval::createFromDateString($arg) ?: null);
		} catch (\Exception $e) {
			return null;
		}
	}
}
