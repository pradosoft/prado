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
 * @since 4.2.0
 * @method null|\Prado\Util\Cron\TDbCronModule getCronModule()
 */
class TShellDbCronAction extends TShellCronAction
{
	protected $methods = ['run', 'tasks', 'index', 'add', 'update', 'remove'];
	protected $parameters = [null, null, null, ['task-name', 'task-id', 'schedule'], ['task-name'], ['task-name']];
	protected $optional = [null, null, null, ['parameters...'], ['parameters...'], []];
	protected $description = ['Manages DbCron time-based services',
		'Runs the DbCron Pending Tasks.',
		'Displays the Cron tasks configured in the application and database.',
		'Displays the registered Cron tasks information.',
		'Adds a Cron task to the database.',
		'Updates a Cron task in the database.',
		'Removes a Cron task from the database.',
	];

	/**
	 * Overrides parent getModuleClass to return the TDbCronModule class.
	 * @return string the DbCron Class to find
	 */
	public function getModuleClass()
	{
		return \Prado\Util\Cron\TDbCronModule::class;
	}

	/**
	 * adds a task to the database with its name, task id, schedule, and other properties.
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
		$s = new TTimeScheduler();
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
	 * updates a task in the database by its name for its schedule, username, moduleid, and other properties.
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
					$s = new TTimeScheduler();
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
	 * removes a task in the database by its name.
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
}
