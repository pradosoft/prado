<?php

/**
 * TShellCronAction class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

use Prado\Prado;
use Prado\Security\Permissions\TPermissionsManager;
use Prado\Shell\TShellAction;
use Prado\Shell\TShellWriter;
use Prado\Util\Cron\TCronModule;

/**
 * TShellCronAction class.
 *
 * Runs the TCronModule from the command line. The "cron" command, and "cron/tasks",
 * report the installed tasks being managed; "cron/run" runs all pending tasks; and
 * "cron/index" reports the possible cron tasks registered in the application.  A
 * system crontab entry runs "cron/run".
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TShellCronAction extends TShellAction
{
	protected $action = 'cron';
	protected $methods = ['tasks', 'run', 'index'];
	protected $parameters = [null, null, null];
	protected $optional = [['task-name or @user'], null, null];
	protected $description = ['Manages Cron time-based services',
		'Displays the Cron tasks configured in the application. Filter by @user and task name.',
		'Runs the Cron Pending Tasks.',
		'Displays the registered Cron tasks information.',
	];

	private $_cron = false;
	private $_user;

	public function __construct()
	{
		/** @var \Prado\Security\Permissions\TUserPermissionsBehavior $user */
		$user = Prado::getApplication()->getUser();
		if ($user && $user->asa(TPermissionsManager::USER_PERMISSIONS_BEHAVIOR)) {
			$this->_user = $user;
			if (!$user->can(TCronModule::PERM_CRON_RUN_PENDING)) {
				unset($this->methods[1]);
				unset($this->parameters[1]);
				unset($this->optional[1]);
				unset($this->description[2]);
			}
			$this->methods = array_values($this->methods);
			$this->parameters = array_values($this->parameters);
			$this->optional = array_values($this->optional);
			$this->description = array_values($this->description);
		}
		parent::__construct();
	}

	/**
	 * @return string the Cron Class to find
	 */
	public function getModuleClass()
	{
		return \Prado\Util\Cron\TCronModule::class;
	}

	/**
	 * @return ?\Prado\Util\Cron\TCronModule returns the Cron Module of the applications
	 */
	public function getCronModule()
	{
		if ($this->_cron === false) {
			$app = Prado::getApplication();
			$moduleClass = $this->getModuleClass();
			$modules = $app->getModulesByType($moduleClass, false);
			$this->_cron = null;
			foreach ($modules as $id => $m) {
				if ($this->_cron = $app->getModule($id)) {
					break;
				}
			}
			if (!$this->_cron) {
				$this->_outWriter->writeError("A {$moduleClass} is not found");
				return null;
			}
		}
		if (!$this->_cron->asa(TCronModule::SHELL_LOG_BEHAVIOR)) {
			$this->_cron->attachBehavior(TCronModule::SHELL_LOG_BEHAVIOR, Prado::createComponent(TShellCronLogBehavior::class, $this->getWriter()));
		}
		return $this->_cron;
	}

	/**
	 * @param ?\Prado\Util\Cron\TCronModule $cron sets the Cron Module
	 */
	public function setCronModule($cron)
	{
		$this->_cron = $cron;
	}

	/**
	 * @param string[] $args the arguments to the command line action
	 */
	public function actionRun($args)
	{
		$module = $this->getCronModule();
		if (!$module) {
			return true;
		}
		$time = $module->getLastCronTime();
		$this->_outWriter->writeLine("\n Last cron run time was " . ($time == 0 ? 'never' : date('Y-m-d H:i:s TP', (int) $time)) . '');
		$module->processPendingTasks();
		return true;
	}

	/**
	 * Shows configured tasks and their run status.  For TDbCronManager, this also shows the
	 * database tasks as well.
	 * @param mixed $args
	 */
	public function actionTasks($args)
	{
		$module = $this->getCronModule();

		$time = $module->getLastCronTime();
		$this->_outWriter->writeLine("\nThe system time is " . date('Y-m-d H:i:s TP'));
		$this->_outWriter->writeLine(" Last cron run was " . ($time == 0 ? 'never' : date('Y-m-d H:i:s', (int) $time)) . "\n");

		$taskName = null;
		$username = null;
		if ($this->_user && !$this->_user->can(TCronModule::PERM_CRON_READ_TASKS)) {
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
		if ($username) {
			$tasks = $module->getUserTasks($username, $taskName);
		} else {
			$tasks = $module->getTasks($taskName);
		}
		if ($tasks === null) {
			return true;
		}
		$count = count($tasks);

		$this->_outWriter->write("  Application Cron Tasks: {$count} tasks found", [TShellWriter::BOLD]);
		$this->_outWriter->writeLine($filter . "\n");
		if (!$count) {
			$this->_outWriter->writeLine("     **  There are no configured Cron Tasks.  **\n");
			return true;
		}
		$rows = [];
		foreach ($tasks as $task) {
			$schedule = $task->getSchedule();
			if (strlen($schedule) > 1 && $schedule[0] == '@') {
				$trigger = substr($schedule, 1);
				if (is_numeric($trigger)) {
					if (abs($trigger - $this->time()) > 86400) {
						$f = 'Y-m-d H:i:s';
					} else {
						$f = 'H:i:s';
					}
					$schedule = '@(' . date($f, $trigger) . ')';
				}
			}

			$lastExecTime = $task->getLastExecTime();
			$count = $task->getProcessCount();
			if ($lastExecTime === null) {
				$lastrun = '-';
			} else {
				if (abs($this->time() - $lastExecTime) > 86400) {
					$f = 'Y-m-d H:i:s';
				} else {
					$f = 'H:i:s';
				}
				$lastrun = date($f, $lastExecTime);
				if (!$count) {
					$lastrun = '- (' . $lastrun . ')';
				}
			}

			$trigger = $task->getNextTriggerTime();
			if ($trigger === null) {
				$nextrun = '-';
			} else {
				if (abs($trigger - $this->time()) > 86400) {
					$f = 'Y-m-d H:i:s';
				} else {
					$f = 'H:i:s';
				}
				$nextrun = date($f, $trigger) . ($task->getIsPending() ? '*' : '');
			}

			if (($user = $task->getUserName()) == null) {
				$user = $module->getDefaultUserName();
			}

			if ($task->getIsPending()) {
				$nextrun = $this->_outWriter->format($nextrun, [TShellWriter::GREEN, TShellWriter::BOLD]);
			}

			$rows[] = [$task->getName(), $schedule, $task->getTask(), $lastrun, $nextrun, '@' . $user, $count];
		}
		$this->_outWriter->write($this->_outWriter->tableWidget(['headers' => ['Name', 'Schedule', 'Task', 'Last Run', 'Next Run', 'User', 'Run #'],
			'rows' => $rows]));
		$this->_outWriter->writeLine("\nAny 'next run' with a * means it is Pending\n");

		return true;
	}

	/**
	 * shows the registered tasks from the application for possible configuration
	 * or addition to TDbCronManager.
	 * @param mixed $args
	 */
	public function actionIndex($args)
	{
		$module = $this->getCronModule();

		$infos = $module->getTaskInfos(true);
		$this->_outWriter->writeLine();
		$this->_outWriter->writeLine("  Registered Cron Task Information: ", [TShellWriter::BOLD]);
		if (!count($infos)) {
			$this->_outWriter->writeLine("		(** No registered application tasks **)");
			return true;
		}
		$rows = [];
		foreach ($infos as $taskinfo) {
			$rows[] = [
					$this->_outWriter->format($taskinfo->getName(), [TShellWriter::BLUE, TShellWriter::BOLD]),
					$taskinfo->getTask(), $taskinfo->getModuleId(), $taskinfo->getTitle(),
				];
			$rows[] = ['span' => $this->_outWriter->format('      ' . $taskinfo->getDescription(), TShellWriter::DARK_GRAY)];
		}
		$this->_outWriter->write($this->_outWriter->tableWidget(['headers' => ['Task ID', 'Task', 'Module ID', 'Title'],
			'rows' => $rows]));
		$this->_outWriter->writeLine();
		return true;
	}

	/**
	 * Returns the current Unix timestamp. Wraps the global {@see \time()} as a single
	 * overridable seam so that subclasses and tests can control the clock.
	 * @return int the current Unix timestamp
	 * @since 4.4.0
	 */
	protected function time(): int
	{
		return \time();
	}
}
