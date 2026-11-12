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
 * The {@see getMode() Mode} is how a sweep runs the task: Serial (the default) alone in the cron
 * process, Parallel in a fiber beside other Parallel tasks, or Process in a child `cron/exec`
 * process; see {@see \Prado\Util\Cron\TCronTaskMode}.  Without {@see getOverlap() Overlap}, a run
 * holds the task's run lock, and a trigger that comes while the previous run still holds it is
 * skipped.
 *
 * The schedule is in the task's {@see getTimeZone() TimeZone}, default the application time zone.  A
 * {@see getStartingDeadline() StartingDeadline} skips a run that would start more than that many seconds
 * after its trigger, such as after the cron process was down.  {@see getDescription() Description} is
 * shown by the shell commands.  The ping URLs ({@see getPingBeforeUrl() PingBeforeUrl},
 * {@see getPingAfterUrl() PingAfterUrl}, {@see getPingSuccessUrl() PingSuccessUrl}, and
 * {@see getPingFailureUrl() PingFailureUrl}) report runs to a monitor through
 * {@see \Prado\Util\Cron\TCronModule::onTaskPing()}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
abstract class TCronTask extends TApplicationComponent
{
	/** The ping before a run starts. */
	public const PING_BEFORE = 'before';

	/** The ping after every run. */
	public const PING_AFTER = 'after';

	/** The ping after a run without an exception. */
	public const PING_SUCCESS = 'success';

	/** The ping after a run with an exception, a failed child process, or a timeout. */
	public const PING_FAILURE = 'failure';

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

	/** @var string how a sweep runs the task, a {@see TCronTaskMode} value */
	private string $_mode = TCronTaskMode::Serial;

	/** @var bool whether a run may start while the previous run is still running */
	private bool $_overlap = false;

	/** @var ?string the time zone of the schedule, or null for the application time zone */
	private ?string $_timeZone = null;

	/** @var ?float the seconds after its trigger a run may start, or null for no deadline */
	private ?float $_startingDeadline = null;

	/** @var string what the task does, for display */
	private string $_description = '';

	/** @var array<string, string> the ping URLs, by {@see PING_BEFORE}, {@see PING_AFTER}, {@see PING_SUCCESS}, or {@see PING_FAILURE} */
	private array $_pingUrls = [];

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
	 * @return string how a sweep runs the task: Serial, Parallel, or Process; default Serial
	 * @since 4.4.0
	 */
	public function getMode(): string
	{
		return $this->_mode;
	}

	/**
	 * @param string $mode how a sweep runs the task, a {@see TCronTaskMode} value
	 * @throws TInvalidDataValueException when the mode is not a TCronTaskMode value
	 * @since 4.4.0
	 */
	public function setMode($mode)
	{
		$this->_mode = TPropertyValue::ensureEnum($mode, TCronTaskMode::class);
	}

	/**
	 * @return bool whether a run may start while the previous run is still running; default false
	 * @since 4.4.0
	 */
	public function getOverlap(): bool
	{
		return $this->_overlap;
	}

	/**
	 * @param bool|string $value whether a run may start while the previous run is still running
	 * @since 4.4.0
	 */
	public function setOverlap($value)
	{
		$this->_overlap = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return ?string the time zone of the schedule, or null for the application time zone
	 * @since 4.4.0
	 */
	public function getTimeZone(): ?string
	{
		return $this->_timeZone;
	}

	/**
	 * Sets the time zone of the schedule and clears the cached next trigger time.
	 * @param ?string $timeZone a time zone identifier such as 'America/New_York' or an offset such as '+02:00'; null or '' for the application time zone
	 * @throws TInvalidDataValueException when the time zone is not known
	 * @since 4.4.0
	 */
	public function setTimeZone($timeZone)
	{
		$timeZone = ($timeZone === null || $timeZone === '') ? null : TPropertyValue::ensureString($timeZone);
		if ($timeZone !== null) {
			try {
				new \DateTimeZone($timeZone);
			} catch (\Exception $e) {
				throw new TInvalidDataValueException('crontask_timezone_invalid', $timeZone);
			}
		}
		$this->_timeZone = $timeZone;
		$this->_scheduler?->setTimeZone($timeZone);
		$this->_nextexectime = false;
	}

	/**
	 * @return ?float the seconds after its trigger a run may start, or null for no deadline
	 * @since 4.4.0
	 */
	public function getStartingDeadline(): ?float
	{
		return $this->_startingDeadline;
	}

	/**
	 * @param null|float|string $seconds the seconds after its trigger a run may start, or null for no deadline
	 * @throws TInvalidDataValueException when the seconds are less than zero
	 * @since 4.4.0
	 */
	public function setStartingDeadline($seconds)
	{
		if ($seconds !== null && $seconds !== '') {
			$seconds = TPropertyValue::ensureFloat($seconds);
			if ($seconds < 0) {
				throw new TInvalidDataValueException('crontask_startingdeadline_invalid', $seconds);
			}
		} else {
			$seconds = null;
		}
		$this->_startingDeadline = $seconds;
	}

	/**
	 * @return string what the task does, for display; default ''
	 * @since 4.4.0
	 */
	public function getDescription(): string
	{
		return $this->_description;
	}

	/**
	 * @param ?string $description what the task does, for display
	 * @since 4.4.0
	 */
	public function setDescription($description)
	{
		$this->_description = ($description === null) ? '' : TPropertyValue::ensureString($description);
	}

	/**
	 * @return ?string the URL pinged before a run starts, or null
	 * @since 4.4.0
	 */
	public function getPingBeforeUrl(): ?string
	{
		return $this->_pingUrls[self::PING_BEFORE] ?? null;
	}

	/**
	 * @param ?string $url the URL pinged before a run starts, or null
	 * @since 4.4.0
	 */
	public function setPingBeforeUrl($url)
	{
		$this->setPingUrl(self::PING_BEFORE, $url);
	}

	/**
	 * @return ?string the URL pinged after every run, or null
	 * @since 4.4.0
	 */
	public function getPingAfterUrl(): ?string
	{
		return $this->_pingUrls[self::PING_AFTER] ?? null;
	}

	/**
	 * @param ?string $url the URL pinged after every run, or null
	 * @since 4.4.0
	 */
	public function setPingAfterUrl($url)
	{
		$this->setPingUrl(self::PING_AFTER, $url);
	}

	/**
	 * @return ?string the URL pinged after a run without an exception, or null
	 * @since 4.4.0
	 */
	public function getPingSuccessUrl(): ?string
	{
		return $this->_pingUrls[self::PING_SUCCESS] ?? null;
	}

	/**
	 * @param ?string $url the URL pinged after a run without an exception, or null
	 * @since 4.4.0
	 */
	public function setPingSuccessUrl($url)
	{
		$this->setPingUrl(self::PING_SUCCESS, $url);
	}

	/**
	 * @return ?string the URL pinged after a failed run, or null
	 * @since 4.4.0
	 */
	public function getPingFailureUrl(): ?string
	{
		return $this->_pingUrls[self::PING_FAILURE] ?? null;
	}

	/**
	 * @param ?string $url the URL pinged after a failed run, or null
	 * @since 4.4.0
	 */
	public function setPingFailureUrl($url)
	{
		$this->setPingUrl(self::PING_FAILURE, $url);
	}

	/**
	 * Returns the ping URL of a point in a run.
	 * @param string $ping {@see PING_BEFORE}, {@see PING_AFTER}, {@see PING_SUCCESS}, or {@see PING_FAILURE}
	 * @return ?string the URL, or null
	 * @since 4.4.0
	 */
	public function getPingUrl(string $ping): ?string
	{
		return $this->_pingUrls[$ping] ?? null;
	}

	/**
	 * @param string $ping {@see PING_BEFORE}, {@see PING_AFTER}, {@see PING_SUCCESS}, or {@see PING_FAILURE}
	 * @param ?string $url the URL, or null or '' for none
	 * @since 4.4.0
	 */
	protected function setPingUrl(string $ping, $url): void
	{
		if ($url === null || $url === '') {
			unset($this->_pingUrls[$ping]);
		} else {
			$this->_pingUrls[$ping] = TPropertyValue::ensureString($url);
		}
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
			$this->_scheduler->setTimeZone($this->_timeZone);
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
		if ($this->_mode === TCronTaskMode::Serial) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_mode";
		}
		if (!$this->_overlap) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_overlap";
		}
		if ($this->_timeZone === null) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_timeZone";
		}
		if ($this->_startingDeadline === null) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_startingDeadline";
		}
		if ($this->_description === '') {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_description";
		}
		if ($this->_pingUrls === []) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_pingUrls";
		}
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
