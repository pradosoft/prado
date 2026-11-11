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
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\Async\TCancellationToken;
use Prado\Prado;
use Prado\TApplicationComponent;
use Prado\TPropertyValue;

/**
 * TCronTask class.
 *
 * TCronTask is the base class for all cron tasks.  Subclasses need to implement the
 * abstract method {@see execute} to run tasks.
 * If a task is not run at the schedule time, it is run at the next available task sweep.
 *
 * {@see \Prado\Util\Cron\TCronModule::runTask()} runs the task in a fiber of its own as the task's
 * {@see getUserName() UserName}.  A {@see getTimeout() Timeout} bounds one run: the module cancels the
 * {@see getCancellationToken() CancellationToken} at the deadline, which a cooperative task passes to its waits
 * or checks with {@see \Prado\IO\Async\TCancellationToken::throwIfCancelled()}.
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

	/** @var ?bool remove the task after it runs; used by TDbCronManager */
	private $_autoRemove;

	/** @var TTimeScheduler The time scheduler */
	private $_scheduler;

	/** @var int The number of times which the cron task has run since the counter has been cleared */
	private $_processCount = 0;

	/** @var ?int the last time this task was run */
	private $_lastexectime;

	/** @var null|false|int the cached next trigger time after the lastexectime; false until computed */
	private $_nextexectime = false;

	/** @var ?float the seconds one run of the task may take, or null for the module's TaskTimeout */
	private ?float $_timeout = null;

	/** @var ?TCancellationToken the token of the current run, set by the module */
	private ?TCancellationToken $_cancellationToken = null;

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
		if ($this->_scheduler) {
			$this->_scheduler->setHashContext($this->_name);
			$this->_nextexectime = false;
		}
	}

	/**
	 * @return string the cron style schedule of the task
	 */
	public function getSchedule()
	{
		return $this->_schedule;
	}

	/**
	 * Sets the schedule and clears the cached next trigger time.
	 * @param string $schedule the cron style schedule of the task
	 */
	public function setSchedule($schedule)
	{
		$this->_schedule = TPropertyValue::ensureString($schedule);
		$this->_nextexectime = false;
		if ($this->_scheduler) {
			$this->_scheduler->setSchedule($this->_schedule);
		}
	}

	/**
	 * @return string the task being executed
	 */
	public function getTask()
	{
		return $this::class;
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
	 * @return ?string the utility module for the task
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
	 * TCronModule ignores this; TDbCronManager removes the task from its table after it runs.
	 * @return ?bool after running the task, should it remove itself.
	 * @since 4.4.0
	 */
	public function getAutoRemove()
	{
		return $this->_autoRemove;
	}

	/**
	 * TCronModule ignores this; TDbCronManager removes the task from its table after it runs.
	 * @param mixed $autoRemove after running the task, should it remove itself.
	 * @since 4.4.0
	 */
	public function setAutoRemove($autoRemove)
	{
		$this->_autoRemove = ($autoRemove !== null) ? TPropertyValue::ensureBoolean($autoRemove) : null;
	}

	/**
	 * @return ?\Prado\IModule returns the module from ModuleId
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
	 * Sets the last execution time and clears the cached next trigger time.
	 * sometimes floats don't output correctly to 6 significant figures (microtime).
	 * @param numeric $v the time of running this cron task
	 */
	public function setLastExecTime($v)
	{
		$this->_nextexectime = false;
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
		$now = $this->time();
		$schedule = $this->getScheduler();
		$nextTriggerTime = $schedule->getNextTriggerTime($now);
		$this->_nextexectime = false;
		$this->_lastexectime = ($nextTriggerTime === null) ? null :
			(($schedule->getNextTriggerTime(0) == $nextTriggerTime) ? null : $now);
	}

	/**
	 * Computes the time of the next trigger.  When $time is provided, the next
	 * trigger after $time is returned without caching; otherwise the value relative
	 * to the lastExecTime is computed once and cached.
	 * @param ?int $time the time to compute the next trigger after, default null for the lastExecTime.
	 * @return ?numeric time of the next trigger, null if none.
	 */
	public function getNextTriggerTime($time = null)
	{
		if ($time !== null) {
			return $this->getScheduler()->getNextTriggerTime($time);
		}
		if ($this->_nextexectime === false) {
			$s = $this->getScheduler();
			$this->_nextexectime = $s->getNextTriggerTime($this->_lastexectime ?? 0);
		}
		return $this->_nextexectime;
	}

	/**
	 * @param ?int $time the current time to check the trigger time against, default null for {@see time()}
	 * @return bool is the current time after the NextTriggerTime
	 */
	public function getIsPending($time = null)
	{
		if ($time === null) {
			$time = $this->time();
		}
		$trigger = $this->getNextTriggerTime();
		return $trigger !== null && $trigger <= $time;
	}

	/**
	 * @return ?float the seconds one run of the task may take, or null for the module's TaskTimeout
	 * @since 4.4.0
	 */
	public function getTimeout(): ?float
	{
		return $this->_timeout;
	}

	/**
	 * @param null|float|string $seconds the seconds one run of the task may take, or null for the module's TaskTimeout
	 * @throws TInvalidDataValueException when the seconds are not greater than zero
	 * @since 4.4.0
	 */
	public function setTimeout($seconds)
	{
		if ($seconds !== null && $seconds !== '') {
			$seconds = TPropertyValue::ensureFloat($seconds);
			if ($seconds <= 0) {
				throw new TInvalidDataValueException('crontask_timeout_invalid', $seconds);
			}
		} else {
			$seconds = null;
		}
		$this->_timeout = $seconds;
	}

	/**
	 * @return ?TCancellationToken the token of the current run, cancelled at the run's deadline; null outside a run or without a deadline
	 * @since 4.4.0
	 */
	public function getCancellationToken(): ?TCancellationToken
	{
		return $this->_cancellationToken;
	}

	/**
	 * Sets the token of the current run.  {@see \Prado\Util\Cron\TCronModule::runTask()} sets it before
	 * {@see execute()} and clears it after.
	 * @param ?TCancellationToken $token the token of the current run
	 * @since 4.4.0
	 */
	public function setCancellationToken(?TCancellationToken $token)
	{
		$this->_cancellationToken = $token;
	}

	/**
	 * The scheduler's {@see \Prado\Util\Cron\TTimeScheduler::setHashContext HashContext} is the task
	 * name, so a hashed schedule ('H', '#daily') spreads the tasks by name.
	 * @return \Prado\Util\Cron\TTimeScheduler the time scheduler for processing the schedule
	 */
	public function getScheduler()
	{
		if ($this->_scheduler === null) {
			$this->_scheduler = Prado::createComponent(TTimeScheduler::class);
			$this->_scheduler->setHashContext($this->_name);
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
		$exprops[] = "\0Prado\Util\Cron\TCronTask\0_nextexectime";
		if ($this->_userName === null) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_userName";
		}
		if ($this->_moduleId === null) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_moduleId";
		}
		if ($this->_autoRemove === null) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_autoRemove";
		}
		if ($this->_processCount == 0) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_processCount";
		}
		if ($this->_lastexectime == null) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_lastexectime";
		}
		if ($this->_timeout === null) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_timeout";
		}
		$exprops[] = "\0Prado\Util\Cron\TCronTask\0_cancellationToken";
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
