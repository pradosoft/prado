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
use Prado\Shell\TShellAction;
use Prado\Shell\TShellWriter;
use Prado\Util\Cron\TCronModule;

/**
 * TShellCronAction class.
 *
 * Runs the TCronModule from the command line.   This will run all pending
 * tasks when the "cron" command is run.  Other sub-commands are "info" and
 * "tasks"; where "info" reports the possible cron tasks registered in the
 * application, and "tasks" reports the installed tasks being managed.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TShellCronAction extends TShellAction
{
	protected $action = 'cron';
	protected $methods = ['run', 'tasks', 'index'];
	protected $parameters = [null, null, null];
	protected $optional = [null, null, null];
	protected $description = ['Manages Cron time-based services',
		'Runs the Cron Pending Tasks.',
		'Displays the Cron tasks configured in the application.',
		'Displays the registered Cron tasks information.',
	];

	private $_cron = false;

	/**
	 * @return string the Cron Class to find
	 */
	public function getModuleClass()
	{
		return \Prado\Util\Cron\TCronModule::class;
	}

	/**
	 * @return null|\Prado\Util\Cron\TCronModule returns the Cron Module of the applications
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
			$this->_cron->attachBehavior(TCronModule::SHELL_LOG_BEHAVIOR, new TShellCronLogBehavior($this->getWriter()));
		}
		return $this->_cron;
	}

	/**
	 * @param null|\Prado\Util\Cron\TCronModule $cron sets the Cron Module
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
		$this->_outWriter->writeLine("\n Last cron run time was " . ($time == 0 ? 'never' : date('Y-m-d H:i:s TP', $time)) . '');
		$module->processPendingTasks();
		return true;
	}

	/**
	 * Shows configured tasks and their run status.  For TDbCronModule, this also shows the
	 * database tasks as well.
	 * @param mixed $args
	 */
	public function actionTasks($args)
	{
		$module = $this->getCronModule();

		$time = $module->getLastCronTime();
		$this->_outWriter->writeLine("\n Last cron run was " . ($time == 0 ? 'never' : date('Y-m-d H:i:s', (int) $time)) . "");
		$this->_outWriter->writeLine("The system time is " . date('Y-m-d H:i:s TP') . "\n");
		$tasks = $module->getTasks();

		$this->_outWriter->writeLine("  Application Cron Tasks: ", [TShellWriter::BOLD]);
		if (!count($tasks)) {
			$this->_outWriter->writeLine("     **  There are no configured Cron Tasks.  **\n");
			return true;
		}
		$rows = [];
		foreach ($tasks as $task) {
			$lastExecTime = $task->getLastExecTime();
			$count = $task->getProcessCount();
			if ($lastExecTime === null) {
				$lastrun = '-';
			} else {
				if (abs(time() - $lastExecTime) > 86400) {
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
				if (abs($trigger - time()) > 86400) {
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

			$rows[] = [$task->getName(), $task->getSchedule(), $task->getTask(), $lastrun, $nextrun, '@' . $user, $count];
		}
		$this->_outWriter->write($this->_outWriter->tableWidget(['headers' => ['Name', 'Schedule', 'Task', 'Last Run', 'Next Run', 'User', 'Run #'],
			'rows' => $rows]));
		$this->_outWriter->writeLine("\nAny 'next run' with a * means it is Pending\n");

		return true;
	}

	/**
	 * shows the registered tasks from the application for possible configuration
	 * or addition to TDbCronModule.
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
}
