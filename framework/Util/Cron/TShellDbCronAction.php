<?php

/**
 * TDbCLICronAction class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */
 
namespace Prado\Util\Cron;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TApplicationException;
use Prado\Prado;
use Prado\Shell\TShellAppAction;
use Prado\Shell\TShellWriter;

/**
 * TDbCLICronAction class.
 *
 * Runs the TDBCronModule from the command line
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Util\Cron
 * @since 4.2.0
 */
class TShellDbCronAction extends TShellCronAction
{
	protected $description = 'Runs the Application internal TDbCronModule Pending Tasks.
		commands are: tasks, list, help, add, update, remove';
	
	public function getModuleClass()
	{
		return 'Prado\\Util\\Cron\\TDbCronModule';
	}
	
	public function cronHelp($helpcmd)
	{
		if ($helpcmd == 'add') {
			$this->_outWriter->writeLine("\nhelp for cron add command");
			$this->_outWriter->write("usage: ");
			$this->_outWriter->writeLine("add (task name) (task id) (schedule) [property=value] [otherProperties=values]", [TShellWriter::BLUE, TShellWriter::BOLD]);
			$this->_outWriter->writeLine("example: php prado-cli.php app ./app_dir cron add newTaskName taskID '* * * * *' ModuleId=mymodule UserId=admin PropertyA=value333\n");
		} elseif ($helpcmd == 'update') {
			$this->_outWriter->writeLine("\nhelp for cron update command");
			$this->_outWriter->write("usage: ");
			$this->_outWriter->writeLine("update (task name) [schedule='_ _ _ _ _'] [property=value] [otherProperties=values]", [TShellWriter::BLUE, TShellWriter::BOLD]);
			$this->_outWriter->writeLine("example: php prado-cli.php app ./app_dir cron update aTaskName 'schedule=* * * * *' ModuleId=mymodule UserId=admin PropertyA=value333\n");
		} elseif ($helpcmd == 'remove') {
			$this->_outWriter->writeLine("\nhelp for cron remove command");
			$this->_outWriter->write("usage: ");
			$this->_outWriter->writeLine("remove (task name)", [TShellWriter::BLUE, TShellWriter::BOLD]);
			$this->_outWriter->writeLine("example: php prado-cli.php app ./app_dir cron remove aTaskName\n");
		} elseif (parent::cronHelp($helpcmd)) {
			$this->_outWriter->writeLine("	add - adds a task from the task infos.");
			$this->_outWriter->writeLine("	update - this updates a task.");
			$this->_outWriter->writeLine("	remove - removes a task.");
		}
	}
	
	public function cronCommand($module, $cmd, $args)
	{
		$handled = false;
		
		if ($cmd == 'add') {
			$this->addTask($module, $args);
			$handled = true;
		} elseif ($cmd == 'update') {
			$this->updateTask($module, $args);
			$handled = true;
		} elseif ($cmd == 'remove') {
			$this->removeTask($module, $args);
			$handled = true;
		}
		
		return $handled;
	}
	
	public function addTask($module, $args)
	{
		$taskName = $args[4] ?? null;
		$id = $args[5] ?? null;
		$schedule = $args[6] ?? null;
		
		if (!$taskName) {
			$this->_outWriter->writeLine("Cannot add a task without a name\n", [TShellWriter::RED, TShellWriter::BOLD]);
			return;
		}
		if (!$id) {
			$this->_outWriter->writeLine("Cannot add a task without a task id\n", [TShellWriter::RED, TShellWriter::BOLD]);
			return;
		}
		if (!$schedule) {
			$this->_outWriter->writeLine("Cannot add a task without a schedule\n", [TShellWriter::RED, TShellWriter::BOLD]);
			return;
		}
		
		$exists = $module->taskExists($taskName);
		if ($exists) {
			$this->_outWriter->writeLine("'{$taskName}' already exists in the database\n", [TShellWriter::RED, TShellWriter::BOLD]);
			return;
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
			$this->_outWriter->writeLine("Task ID '{$id}' could not be found\n", [TShellWriter::RED, TShellWriter::BOLD]);
			return;
		}
		$s = new TTimeScheduler();
		try {
			$s->setSchedule($schedule);
		} catch (TInvalidDataValueException $e) {
			$this->_outWriter->writeLine("Schedule '{$schedule}' is not a valid schedule\n", [TShellWriter::RED, TShellWriter::BOLD]);
			return;
		}
		
		$task = $module->instanceTask($info->getTask());
		$task->setName($taskName);
		$task->setSchedule($schedule);
		for ($i = 7; $i < count($args); $i++) {
			$parts = explode('=', $args[$i]);
			$parts[0] = trim($parts[0]);
			$property = strtolower($parts[0]);
			if ($task->canSetProperty($property)) {
				$property = 'set' . $property;
				$task->$property(trim($parts[1]));
			} else {
				$this->_outWriter->writeLine("Task Property '{$parts[0]}' is not found\n", [TShellWriter::RED, TShellWriter::BOLD]);
				return;
			}
		}
		$module->addTask($task);
		
		$this->_outWriter->writeLine("Task '{$taskName}' was added to the database\n", [TShellWriter::GREEN, TShellWriter::BOLD]);
	}
	
	public function updateTask($module, $args)
	{
		$taskName = $args[4] ?? null;
		
		if (!$taskName) {
			$this->_outWriter->writeLine("Cannot update a task without a name\n", [TShellWriter::RED, TShellWriter::BOLD]);
			return;
		}
		
		$task = $module->getTask($taskName);
		if (!$task) {
			$this->_outWriter->writeLine("Task '{$taskName}' is not found\n", [TShellWriter::RED, TShellWriter::BOLD]);
			return;
		}
		if (count($args) <= 5) {
			$this->_outWriter->writeLine("No given properties to change\n", [TShellWriter::RED, TShellWriter::BOLD]);
			return;
		}
		for ($i = 5; $i < count($args); $i++) {
			$parts = explode('=', $args[$i]);
			$parts[0] = trim($parts[0]);
			$property = strtolower($parts[0]);
			if ($task->canSetProperty($property)) {
				if ($property === 'schedule') {
					$s = new TTimeScheduler();
					try {
						$s->setSchedule($parts[1]);
					} catch (TInvalidDataValueException $e) {
						$this->_outWriter->writeLine("Schedule '{$parts[1]}' is not a valid schedule\n", [TShellWriter::RED, TShellWriter::BOLD]);
						return;
					}
				}
				$property = 'set' . $property;
				$task->$property(trim($parts[1]));
			} else {
				$this->_outWriter->writeLine("Task Property '{$parts[0]}' is not found\n", [TShellWriter::RED, TShellWriter::BOLD]);
				return;
			}
		}
		$module->updateTask($task);
		$this->_outWriter->writeLine("Task '{$taskName}' was updated in the database\n", [TShellWriter::GREEN, TShellWriter::BOLD]);
	}
	
	public function removeTask($module, $args)
	{
		if (!($taskName = $args[4] ?? null)) {
			$this->_outWriter->writeLine("Cannot remove a task without a name\n", [TShellWriter::RED, TShellWriter::BOLD]);
			return;
		}
		$exists = $module->taskExists($taskName);
		if (!$exists) {
			$this->_outWriter->writeLine("'{$taskName}' does not exist in the database and could not be removed\n", [TShellWriter::RED, TShellWriter::BOLD]);
			return;
		}
		$result = $module->removeTask($taskName);
		
		if ($result) {
			$this->_outWriter->writeLine("'{$taskName}' was successfully removed.\n", [TShellWriter::GREEN, TShellWriter::BOLD]);
		} else {
			$this->_outWriter->writeLine("'{$taskName}' could not be removed.\n", [TShellWriter::RED, TShellWriter::BOLD]);
		}
	}
}
