<?php

/**
 * TCLICronAction class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */
 
namespace Prado\Util\Cron;

use Prado\Prado;
use Prado\Shell\TShellAppAction;
use Prado\Shell\TShellWriter;

/**
 * TCLICronAction class.
 *
 * Runs the TCronModule from the command line
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Util\Cron
 * @since 4.2.0
 */
class TShellCronAction extends TShellAppAction
{
	protected $action = 'cron';
	protected $parameters = [];
	protected $optional = ['command'];
	protected $description = 'Runs the Application internal TCronModule Pending Tasks.
		commands are: tasks, list, help';
	
	public function getModuleClass()
	{
		return 'Prado\\Util\\Cron\\TCronModule';
	}
	
	public function performAction($args)
	{
		$app = Prado::getApplication();
		$moduleClass = $this->getModuleClass();
		$modules = $app->getModulesByType($moduleClass, false, true);
		$module = null;
		foreach ($modules as $id => $m) {
			if ($module = $app->getModule($id)) {
				break;
			}
		}
		if (!$module) {
			$this->_outWriter->writeLine("A {$moduleClass} is not found");
			return;
		}
		$module->attachBehavior('shellLog', new TShellCronLogBehavior($this->getWriter()));
		
		$cmd = $args[3] ?? null;
		if ($cmd === null) {
			$this->processPendingTasks($module);
		} elseif ($cmd == 'tasks') {
			$this->showTasks($module);
		} elseif ($cmd == 'info') {
			$this->listTaskInfos($module);
		} else {
			if (!$this->cronCommand($module, $cmd, $args)) {
				$this->cronHelp($args[4] ?? null);
			}
		}
		
		return true;
	}
	
	public function cronCommand($module, $cmd, $args)
	{
		return false;
	}
	
	public function processPendingTasks($module)
	{
		$this->_outWriter->writeLine();
		$this->_outWriter->writeLine("\nLast Task time was " . date('Y-m-d H:i:s', $module->getLastCronTime()) . '');
		//$this->_outWriter->writeLine("Running Cron Module Tasks...");
		$module->processPendingTasks(true);
	}
	
	public function showTasks($module)
	{
		$this->_outWriter->writeLine("\nLast cron run was " . date('Y-m-d H:i:s', $module->getLastCronTime()) . "\n");
		$this->_outWriter->writeLine("The system time is " . date('Y-m-d H:i:s') . "\n");
		$tasks = $module->getTasks();
		
		if (!count($tasks)) {
			$this->_outWriter->writeLine("     **  There are no pending Cron Tasks.  **\n");
			return;
		}
		
		$this->_outWriter->write($this->pad('name', 12));
		$this->_outWriter->write($this->pad('Class', 50));
		$this->_outWriter->write($this->pad('Last Run', 22));
		//$this->_outWriter->write($this->pad('Prev Run', 22));
		$this->_outWriter->write($this->pad('Next Run', 22));
		$this->_outWriter->write($this->pad('User', 15));
		$this->_outWriter->write($this->pad('Run Count', 10));
		$this->_outWriter->writeLine("");
			
		foreach ($tasks as $task) {
			if (($name = $task->getName()) == null) {
				$name = '(anonymous)';
			}
			$this->_outWriter->write($this->pad($name, 12));
			$this->_outWriter->write($this->pad($task->getTask(), 50));
			$f = 'H:i:s';
			if (time() - $task->getLastExecTime() > 86400) {
				$f = 'Y-m-d H:i:s';
			}
			$this->_outWriter->write($this->pad(date($f, $task->getLastExecTime()), 22));
			//$f = 'H:i:s';
			//if (time() - $task->getPreviousTriggerTime() < 86400) {
			//	$f = 'Y-m-d H:i:s';
			//}
			//$this->_outWriter->write($this->pad(date($f, $task->getPreviousTriggerTime()), 22));
			$f = 'H:i:s';
			$trigger = $task->getNextTriggerTime();
			if ($trigger - time() > 86400) {
				$f = 'Y-m-d H:i:s';
			}
			$this->_outWriter->write($this->pad(date($f, $trigger) . ($task->getIsPending() ? '*' : ' '), 22));
			$this->_outWriter->write($this->pad($task->getUserId(), 15));
			$this->_outWriter->write($this->pad($task->getProcessCount(), 10));
			$this->_outWriter->writeLine("");
		}
		$this->_outWriter->writeLine("\nAny 'next run' with a * means it is Pending\n");
		
		return true;
	}
	
	public function listTaskInfos($module)
	{
		$this->_outWriter->writeLine("");
		$this->_outWriter->write($this->pad('Task ID', 22));
		$this->_outWriter->write($this->pad('Task', 50));
		$this->_outWriter->write($this->pad('Module ID', 16));
		$this->_outWriter->write($this->pad('Title', 30));
		$this->_outWriter->writeLine("");
		$infos = $module->getTaskInfos();
		if (!count($infos)) {
			$this->_outWriter->writeLine("		(** No registered application tasks **)");
		}
		foreach ($infos as $taskinfo) {
			$this->_outWriter->write($this->pad($taskinfo->getName(), 22));
			$this->_outWriter->write($this->pad($taskinfo->getTask(), 50));
			$this->_outWriter->write($this->pad($taskinfo->getModuleId(), 16));
			$this->_outWriter->write($this->pad($taskinfo->getTitle(), 30));
			$this->_outWriter->writeLine("");
			$this->_outWriter->write($this->pad('  ' . $taskinfo->getDescription(), 112));
			$this->_outWriter->writeLine("");
		}
		$this->_outWriter->writeLine("");
	}
	
	protected function pad($s, $n, $c = ' ')
	{
		$l = strlen($s);
		if ($l > $n) {
			return substr($s, 0, $n - 1) . '*';
		}
		if ($l < $n) {
			$m = $n - $l;
			$lc = strlen($c);
			for ($i = 0; $i < $m; $i += $lc) {
				$s .= $c;
			}
		}
		return $s;
	}
	
	public function cronHelp($helpcmd)
	{
		if ($helpcmd == 'tasks') {
			$this->_outWriter->writeLine("help for the tasks command");
			$this->_outWriter->writeLine("there are no parameters for the 'tasks' command.");
		} elseif ($helpcmd == 'info') {
			$this->_outWriter->writeLine("help for the info command");
			$this->_outWriter->writeLine("there are no parameters for the 'info' command.");
		} elseif ($helpcmd == 'help') {
			$this->_outWriter->writeLine("help for the help command");
			$this->_outWriter->writeLine("You have summoned the help for the help.");
			$this->_outWriter->writeLine("How much help can help be? that is the question.");
		} else {
			$this->_outWriter->write("\nusage: ");
			$this->_outWriter->writeLine("php prado-cli.php app <app_dir> cron [command]", [TShellWriter::BLUE, TShellWriter::BOLD]);
			$this->_outWriter->writeLine("\nexample: php prado-cli.php app ./app_dir cron help\n");
			$this->_outWriter->writeLine("optional (actions):");
			$this->_outWriter->writeLine("	(blank) - if no parameter is given, cron will run pending tasks.");
			$this->_outWriter->writeLine("		this should be run from system cron '* * * * *'.");
			$this->_outWriter->writeLine("	tasks - this lists the manual tasks entered via TCronModule configuration.");
			$this->_outWriter->writeLine("	info - this command lists the available tasks of the application.");
			$this->_outWriter->writeLine("	help [command] - displays this help text for PRADO cli cron or an individual command.");
			return true;
		}
		return false;
	}
}
