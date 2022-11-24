<?php

/**
 * TCronTask class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TApplicationComponent;
use Prado\TPropertyValue;

/**
 * TCronTask class.
 *
 * TCronTask is the base class for all cron tasks.  Subclasses need to implement the
 * abstract method {@link execute} to run tasks.
 * If a task is not run at the schedule time, it is run at the next available task sweep.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
abstract class TCronTask extends TApplicationComponent
{
	/** @var string The name of the task */
	private $_name;

	/** @var string The schedule */
	private $_schedule;

	/** @var string The user Id of the task */
	private $_userName;

	/** @var string The module Id */
	private $_moduleId;

	/** @var TTimeScheduler The time scheduler */
	private $_scheduler;

	/** @var int The number of times which the cron task has run since the counter has been cleared */
	private $_processCount = 0;

	/** @var int the last time this task was run */
	private $_lastexectime;

	/**
	 * This is the abstract method for running a task.
	 * @param TCronModule $cronModule the module calling the task
	 */
	abstract public function execute($cronModule);

	/**
	 * @return string the unique name of the Task
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * @param string $name the unique name of the Task
	 */
	public function setName($name)
	{
		$this->_name = TPropertyValue::ensureString($name);
	}

	/**
	 * @return string the cron style schedule of the task
	 */
	public function getSchedule()
	{
		return $this->_schedule;
	}

	/**
	 *
	 * @param string $schedule the cron style schedule of the task
	 */
	public function setSchedule($schedule)
	{
		$this->_schedule = TPropertyValue::ensureString($schedule);
		if ($this->_scheduler) {
			$this->_scheduler->setSchedule($this->_schedule);
		}
	}

	/**
	 * @return string the task being executed
	 */
	public function getTask()
	{
		return get_class($this);
	}

	/**
	 * @return string the user id executing the Task
	 */
	public function getUserName()
	{
		return $this->_userName;
	}

	/**
	 * @param string $username the user id executing the Task
	 */
	public function setUserName($username)
	{
		$this->_userName = TPropertyValue::ensureString($username);
	}

	/**
	 * @return null|string the utility module for the task
	 */
	public function getModuleId()
	{
		return $this->_moduleId;
	}

	/**
	 *
	 * @param string $moduleId the utility module for the task.
	 */
	public function setModuleId($moduleId)
	{
		$this->_moduleId = ($moduleId !== null) ? TPropertyValue::ensureString($moduleId) : null;
	}

	/**
	 * @return null|\Prado\IModule returns the module from ModuleId
	 */
	public function getModule()
	{
		$app = $this->getApplication();
		$module = $app->getModule($this->_moduleId);
		if ($module === null && $this->_moduleId !== null) {
			throw new TConfigurationException('crontask_no_module', $this->_moduleId);
		}
		return $module;
	}

	/**
	 * @return int the number of times the task has run
	 */
	public function getProcessCount()
	{
		return $this->_processCount;
	}

	/**
	 *
	 * @param int $count the number of times the task has run
	 */
	public function setProcessCount($count)
	{
		return $this->_processCount = $count;
	}

	/**
	 * @return numeric the time of running this cron task
	 */
	public function getLastExecTime()
	{
		return $this->_lastexectime;
	}

	/**
	 * sometimes floats don't output correctly to 6 significant figures (microtime).
	 * @param numeric $v the time of running this cron task
	 */
	public function setLastExecTime($v)
	{
		if ($v !== null) {
			$this->_lastexectime = TPropertyValue::ensureInteger($v);
		} else {
			$this->_lastexectime = null;
		}
	}

	/**
	 * Resets the lastExecTime to either null or, if there is a next trigger
	 * time from time(), sets lastExecTime to now.  This prevents erroneously
	 * triggering repeating tasks on first cron from existing prior triggers
	 * from time=0.
	 */
	public function resetTaskLastExecTime()
	{
		$now = time();
		$schedule = $this->getScheduler();
		$nextTriggerTime = $schedule->getNextTriggerTime($now);
		$this->_lastexectime = ($nextTriggerTime === null) ? null :
			(($schedule->getNextTriggerTime(0) == $nextTriggerTime) ? null : $now);
	}

	/**
	 * @return null|numeric time of the next trigger after the lastExecTime, null if none.
	 */
	public function getNextTriggerTime()
	{
		$s = $this->getScheduler();
		return $s->getNextTriggerTime($this->_lastexectime ?? 0);
	}

	/**
	 * @return bool is the current time after the NextTriggerTime
	 */
	public function getIsPending()
	{
		$trigger = $this->getNextTriggerTime();
		return $trigger !== null && time() >= $trigger;
	}

	/**
	 * @return \Prado\Util\Cron\TTimeScheduler the time scheduler for processing the schedule
	 */
	public function getScheduler()
	{
		if ($this->_scheduler === null) {
			$this->_scheduler = Prado::createComponent('Prado\\Util\\Cron\\TTimeScheduler');
			$this->_scheduler->setSchedule($this->_schedule);
		}
		return $this->_scheduler;
	}

	/**
	 * Returns an array with the names of all variables of this object that should NOT be serialized
	 * because their value is the default one or useless to be cached for the next load.
	 * Reimplement in derived classes to add new variables, but remember to also to call the parent
	 * implementation first.
	 * @param array $exprops by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);

		$exprops[] = "\0Prado\Util\Cron\TCronTask\0_scheduler";
		if ($this->_userName === null) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_userName";
		}
		if ($this->_moduleId === null) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_moduleId";
		}
		if ($this->_processCount == 0) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_processCount";
		}
		if ($this->_lastexectime == null) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_lastexectime";
		}
	}
}
