<?php

/**
 * TShellDbCronAction class file.
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
 * TShellDbCronAction class.
 *
 * TShellDbCronAction extends {@link TShellCronAction} to implement
 * additional commands {@link addTask add}, {@link updateTask update},
 * and {@link removeTask}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Util\Cron
 * @since 4.2.0
 */
class TShellDbCronAction extends TShellCronAction
{
	protected $description = 'Runs the Application internal TDbCronModule Pending Tasks.
		commands are: tasks, list, help, add, update, remove';
	
	/**
	 * Overrides parent getModuleClass to return the TDbCronModule class.
	 * @return string the DbCron Class to find
	 */
	public function getModuleClass()
	{
		return 'Prado\\Util\\Cron\\TDbCronModule';
	}
	
	/**
	 * Displays the help for specific tasks, or in general
	 * @param string $helpcmd the module servicing the action
	 */
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
	
	/**
	 * Overrides parent cronCommand to handle "add", "update", and "remove" actions.
	 * @param \Prado\Util\Cron\TCronModule $module the module servicing the action
	 * @param string $cmd the command being executed
	 * @param array $args the arguments to the shell command
	 * @return string the DbCron Class to find
	 */
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
	
	/**
	 * adds a task to the database with its name, task id, schedule, and other properties.
	 * @param \Prado\Util\Cron\TDbCronModule $module the module servicing the action
	 * @param array $args command arguments
	 */
	public function addTask($module, $args)
	{
		$taskName = $args[4] ?? null;
		$id = $args[5] ?? null;
		$schedule = $args[6] ?? null;
		
		if (!$taskName) {
			$this->_outWriter->writeError("Cannot add a task without a name");
			return;
		}
		if (!$id) {
			$this->_outWriter->writeError("Cannot add a task without a task id");
			return;
		}
		if (!$schedule) {
			$this->_outWriter->writeError("Cannot add a task without a schedule");
			return;
		}
		
		$exists = $module->taskExists($taskName);
		if ($exists) {
			$this->_outWriter->writeError("'{$taskName}' already exists in the database");
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
			$this->_outWriter->writeError("Task ID '{$id}' could not be found");
			return;
		}
		$s = new TTimeScheduler();
		try {
			$s->setSchedule($schedule);
		} catch (TInvalidDataValueException $e) {
			$this->_outWriter->writeError("Schedule '{$schedule}' is not a valid schedule");
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
				$this->_outWriter->writeError("Task Property '{$parts[0]}' is not found");
				return;
			}
		}
		$module->addTask($task);
		
		$this->_outWriter->writeLine("Task '{$taskName}' was added to the database\n", [TShellWriter::GREEN, TShellWriter::BOLD]);
	}
	
	/**
	 * updates a task in the database by its name for its schedule, userid, moduleid, and other properties.
	 * @param \Prado\Util\Cron\TDbCronModule $module the module servicing the action
	 * @param array $args command arguments
	 */
	public function updateTask($module, $args)
	{
		$taskName = $args[4] ?? null;
		
		if (!$taskName) {
			$this->_outWriter->writeError("Cannot update a task without a name");
			return;
		}
		
		$task = $module->getTask($taskName);
		if (!$task) {
			$this->_outWriter->writeError("Task '{$taskName}' is not found");
			return;
		}
		if (count($args) <= 5) {
			$this->_outWriter->writeError("No given properties to change");
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
						$this->_outWriter->writeError("Schedule '{$parts[1]}' is not a valid schedule");
						return;
					}
				}
				$property = 'set' . $property;
				$task->$property(trim($parts[1]));
			} else {
				$this->_outWriter->writeError("Task Property '{$parts[0]}' is not found");
				return;
			}
		}
		$module->updateTask($task);
		$this->_outWriter->writeLine("Task '{$taskName}' was updated in the database\n", [TShellWriter::GREEN, TShellWriter::BOLD]);
	}
	
	/**
	 * rumoves a task in the database by its name.
	 * @param \Prado\Util\Cron\TDbCronModule $module the module servicing the action
	 * @param array $args command arguments
	 */
	public function removeTask($module, $args)
	{
		if (!($taskName = $args[4] ?? null)) {
			$this->_outWriter->writeError("Cannot remove a task without a name");
			return;
		}
		$exists = $module->taskExists($taskName);
		if (!$exists) {
			$this->_outWriter->writeError("'{$taskName}' does not exist in the database");
			return;
		}
		$result = $module->removeTask($taskName);
		
		if ($result) {
			$this->_outWriter->writeLine("'{$taskName}' was successfully removed.\n", [TShellWriter::GREEN, TShellWriter::BOLD]);
		} else {
			$this->_outWriter->writeError("'{$taskName}' could not be removed.\n");
		}
	}
}
