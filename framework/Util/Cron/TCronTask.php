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
 * process; see {@see \Prado\Util\Cron\TCronTaskMode}.  Unless {@see getOverlap() Overlap} is Allow, a
 * run holds the task's run lock; a trigger that comes while the previous run still holds it is skipped,
 * replaces the previous run, or waits for it; see {@see \Prado\Util\Cron\TCronTaskOverlap}.  A Process
 * task's child takes {@see getProcessOutputFile() ProcessOutputFile}, {@see getProcessPriority() ProcessPriority},
 * and {@see getProcessMemoryLimit() ProcessMemoryLimit}.  A run longer than {@see getWarnDuration() WarnDuration}
 * raises {@see \Prado\Util\Cron\TCronModule::onTaskOverdue()}.  {@see getPriority() Priority} orders the tasks
 * due in the same sweep, lower first.  {@see getThen() Then} and {@see getThenOnFailure() ThenOnFailure} name
 * the tasks the cron module runs after a run succeeds or fails; '@never' is the schedule of a task
 * that only runs from such a chain.
 *
 * The schedule is in the task's {@see getTimeZone() TimeZone}, else the cron module's
 * {@see \Prado\Util\Cron\TCronModule::getTimeZone() TimeZone} ({@see getDefaultTimeZone() DefaultTimeZone}),
 * else the application time zone.  {@see getJitter() Jitter} delays each trigger by a stable number of
 * seconds from the task name and the trigger.  A
 * {@see getStartingDeadline() StartingDeadline} skips a run that would start more than that many seconds
 * after its trigger, such as after the cron process was down; {@see getCatchUp() CatchUp} runs up to
 * that many of the triggers missed while it was down, one run each, instead of one run for all.
 * {@see getDescription() Description} is
 * shown by the shell commands.  {@see getTags() Tags} group tasks: the shell commands filter by `#tag`,
 * and the cron module pauses and resumes a tag's tasks together.  The ping URLs ({@see getPingBeforeUrl() PingBeforeUrl},
 * {@see getPingAfterUrl() PingAfterUrl}, {@see getPingSuccessUrl() PingSuccessUrl}, and
 * {@see getPingFailureUrl() PingFailureUrl}) report runs to a monitor through
 * {@see \Prado\Util\Cron\TCronModule::onTaskPing()}.  A failed run is tried again up to
 * {@see getRetries() Retries} times after {@see getRetryDelay() RetryDelay} seconds, multiplied by
 * {@see getRetryBackoff() RetryBackoff} each time.  {@see getBetween() Between} and
 * {@see getUnlessBetween() UnlessBetween} limit the runs to, or keep them out of, a time of day.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
abstract class TCronTask extends TApplicationComponent
{
	/** The default {@see getPriority() Priority}, as of other PRADO priorities. */
	public const DEFAULT_PRIORITY = 10;

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

	/** @var string what a trigger does while the previous run holds the run lock, a {@see TCronTaskOverlap} value */
	private string $_overlap = TCronTaskOverlap::Skip;

	/** @var ?string the file of a Process task child's output, or null for the cron process's output */
	private ?string $_processOutputFile = null;

	/** @var ?int the scheduling priority (nice) of a Process task's child, or null to keep the cron process's */
	private ?int $_processPriority = null;

	/** @var ?string the memory_limit of a Process task's child, or null for the php.ini value */
	private ?string $_processMemoryLimit = null;

	/** @var ?float the seconds after which a run is overdue, or null */
	private ?float $_warnDuration = null;

	/** @var float the order of the task among the tasks due in a sweep; lower runs first */
	private float $_priority = self::DEFAULT_PRIORITY;

	/** @var string[] the tasks run after a run without an exception */
	private array $_then = [];

	/** @var string[] the tasks run after a failed run */
	private array $_thenOnFailure = [];

	/** @var ?string the time zone of the schedule, or null for the application time zone */
	private ?string $_timeZone = null;

	/** @var ?float the seconds after its trigger a run may start, or null for no deadline */
	private ?float $_startingDeadline = null;

	/** @var int the missed triggers a late sweep runs, one run each; 1 runs them as one */
	private int $_catchUp = 1;

	/** @var ?int the trigger the current run serves, or null for a manual run; not serialized */
	private ?int $_runTrigger = null;

	/** @var string what the task does, for display */
	private string $_description = '';

	/** @var string[] the lowercase tags that group the task */
	private array $_tags = [];

	/** @var array<string, string> the ping URLs, by {@see PING_BEFORE}, {@see PING_AFTER}, {@see PING_SUCCESS}, or {@see PING_FAILURE} */
	private array $_pingUrls = [];

	/** @var ?string the time zone the cron module gives a task without its own; not serialized */
	private ?string $_defaultTimeZone = null;

	/** @var int the number of times a failed run is tried again */
	private int $_retries = 0;

	/** @var float the seconds before the first retry */
	private float $_retryDelay = 0.0;

	/** @var float the factor of the delay before each further retry */
	private float $_retryBackoff = 2.0;

	/** @var ?array{0: int, 1: int} the seconds of the day the runs are limited to, [start, end] */
	private ?array $_between = null;

	/** @var ?array{0: int, 1: int} the seconds of the day the runs are kept out of, [start, end] */
	private ?array $_unlessBetween = null;

	/** @var int the most seconds a trigger is delayed */
	private int $_jitter = 0;

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
			return $this->getJitteredTrigger($this->getScheduler()->getNextTriggerTime($time));
		}
		if ($this->_nextexectime === false) {
			$this->_nextexectime = $this->getJitteredTrigger($this->getScheduler()->getNextTriggerTime($this->_lastexectime ?? 0));
		}
		return $this->_nextexectime;
	}

	/**
	 * Returns the next trigger times after a time, each delayed by the {@see getJitter() Jitter}.
	 * @param int $count the number of times
	 * @param int $time the time to start after
	 * @return int[] the trigger times, ascending; fewer when the schedule ends
	 * @since 4.4.0
	 */
	public function getNextTriggerTimes(int $count, int $time): array
	{
		return array_map(fn (int $trigger): int => (int) $this->getJitteredTrigger($trigger), $this->getScheduler()->getNextTriggerTimes($count, $time));
	}

	/**
	 * Delays a trigger of the schedule by the {@see getJitter() Jitter}: a stable number of seconds from
	 * the task name and the trigger, so every process computes the same time.
	 * @param ?int $trigger the trigger time of the schedule
	 * @return ?int the delayed trigger time
	 * @since 4.4.0
	 */
	public function getJitteredTrigger(?int $trigger): ?int
	{
		if ($trigger === null || $this->_jitter === 0) {
			return $trigger;
		}
		return $trigger + crc32($this->_name . "\0" . $trigger) % ($this->_jitter + 1);
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
	 * @return string what a trigger does while the previous run holds the run lock: Skip, Allow, Replace, or Queue; default Skip
	 * @since 4.4.0
	 */
	public function getOverlap(): string
	{
		return $this->_overlap;
	}

	/**
	 * @param bool|string $value a {@see TCronTaskOverlap} value; true is Allow and false is Skip
	 * @throws TInvalidDataValueException when the value is not a TCronTaskOverlap value or a boolean
	 * @since 4.4.0
	 */
	public function setOverlap($value)
	{
		if (is_bool($value) || (is_string($value) && in_array(strtolower($value), ['true', 'false', '1', '0', ''], true))) {
			$this->_overlap = TPropertyValue::ensureBoolean($value) ? TCronTaskOverlap::Allow : TCronTaskOverlap::Skip;
			return;
		}
		$this->_overlap = TPropertyValue::ensureEnum($value, TCronTaskOverlap::class);
	}

	/**
	 * @return ?string the file a Process task's child appends its output and errors to, or null for the cron process's output
	 * @since 4.4.0
	 */
	public function getProcessOutputFile(): ?string
	{
		return $this->_processOutputFile;
	}

	/**
	 * @param ?string $file the file a Process task's child appends its output and errors to, or null
	 * @since 4.4.0
	 */
	public function setProcessOutputFile($file)
	{
		$this->_processOutputFile = ($file === null || $file === '') ? null : TPropertyValue::ensureString($file);
	}

	/**
	 * @return ?int the scheduling priority of a Process task's child, -20 (highest) to 19 (lowest), or null
	 * @since 4.4.0
	 */
	public function getProcessPriority(): ?int
	{
		return $this->_processPriority;
	}

	/**
	 * Sets the scheduling priority (POSIX nice) of a Process task's child; Windows maps it to a priority class.
	 * Raising the priority above the cron process's needs the privilege.
	 * @param null|int|string $priority -20 (highest) to 19 (lowest), or null to keep the cron process's
	 * @throws TInvalidDataValueException when the priority is outside -20 to 19
	 * @since 4.4.0
	 */
	public function setProcessPriority($priority)
	{
		if ($priority === null || $priority === '') {
			$this->_processPriority = null;
			return;
		}
		$priority = TPropertyValue::ensureInteger($priority);
		if ($priority < -20 || $priority > 19) {
			throw new TInvalidDataValueException('crontask_processpriority_invalid', $priority);
		}
		$this->_processPriority = $priority;
	}

	/**
	 * @return ?string the memory_limit of a Process task's child, such as '512M', or null for the php.ini value
	 * @since 4.4.0
	 */
	public function getProcessMemoryLimit(): ?string
	{
		return $this->_processMemoryLimit;
	}

	/**
	 * @param ?string $limit the memory_limit of a Process task's child: bytes with an optional K, M, or G, or -1 for none; null for the php.ini value
	 * @throws TInvalidDataValueException when the limit is not a memory size
	 * @since 4.4.0
	 */
	public function setProcessMemoryLimit($limit)
	{
		if ($limit === null || $limit === '') {
			$this->_processMemoryLimit = null;
			return;
		}
		$limit = TPropertyValue::ensureString($limit);
		if (!preg_match('/^(?:-1|\d+[KMG]?)$/i', $limit)) {
			throw new TInvalidDataValueException('crontask_processmemorylimit_invalid', $limit);
		}
		$this->_processMemoryLimit = $limit;
	}

	/**
	 * @return ?float the seconds after which a run is overdue, or null
	 * @since 4.4.0
	 */
	public function getWarnDuration(): ?float
	{
		return $this->_warnDuration;
	}

	/**
	 * @param null|float|string $seconds the seconds after which a run is overdue and the cron module warns, or null
	 * @throws TInvalidDataValueException when the seconds are not greater than zero
	 * @since 4.4.0
	 */
	public function setWarnDuration($seconds)
	{
		if ($seconds === null || $seconds === '') {
			$this->_warnDuration = null;
			return;
		}
		$seconds = TPropertyValue::ensureFloat($seconds);
		if ($seconds <= 0) {
			throw new TInvalidDataValueException('crontask_warnduration_invalid', $seconds);
		}
		$this->_warnDuration = $seconds;
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
		$this->_scheduler?->setTimeZone($this->getScheduleTimeZone());
		$this->_nextexectime = false;
	}

	/**
	 * @return ?string the time zone the cron module gives a task without its own TimeZone
	 * @since 4.4.0
	 */
	public function getDefaultTimeZone(): ?string
	{
		return $this->_defaultTimeZone;
	}

	/**
	 * Sets the time zone of the schedule when the task has no TimeZone; the cron module sets it from its
	 * own TimeZone.  It is not serialized.
	 * @param ?string $timeZone the time zone, or null for the application time zone
	 * @since 4.4.0
	 */
	public function setDefaultTimeZone(?string $timeZone)
	{
		$this->_defaultTimeZone = ($timeZone === '') ? null : $timeZone;
		$this->_scheduler?->setTimeZone($this->getScheduleTimeZone());
		$this->_nextexectime = false;
	}

	/**
	 * @return ?string the time zone the schedule is in: the TimeZone, else the DefaultTimeZone; null for the application time zone
	 * @since 4.4.0
	 */
	public function getScheduleTimeZone(): ?string
	{
		return $this->_timeZone ?? $this->_defaultTimeZone;
	}

	/**
	 * @return int the most seconds a trigger is delayed; default 0
	 * @since 4.4.0
	 */
	public function getJitter(): int
	{
		return $this->_jitter;
	}

	/**
	 * Sets the most seconds a trigger is delayed, and clears the cached next trigger time.  Keep it below
	 * the time between triggers.
	 * @param int|string $seconds the most seconds a trigger is delayed
	 * @throws TInvalidDataValueException when the seconds are less than zero
	 * @since 4.4.0
	 */
	public function setJitter($seconds)
	{
		$seconds = TPropertyValue::ensureInteger($seconds);
		if ($seconds < 0) {
			throw new TInvalidDataValueException('crontask_jitter_invalid', $seconds);
		}
		$this->_jitter = $seconds;
		$this->_nextexectime = false;
	}

	/**
	 * @return int the number of times a failed run is tried again; default 0
	 * @since 4.4.0
	 */
	public function getRetries(): int
	{
		return $this->_retries;
	}

	/**
	 * @param int|string $count the number of times a failed run is tried again
	 * @throws TInvalidDataValueException when the count is less than zero
	 * @since 4.4.0
	 */
	public function setRetries($count)
	{
		$count = TPropertyValue::ensureInteger($count);
		if ($count < 0) {
			throw new TInvalidDataValueException('crontask_retries_invalid', $count);
		}
		$this->_retries = $count;
	}

	/**
	 * @return float the seconds before the first retry; default 0
	 * @since 4.4.0
	 */
	public function getRetryDelay(): float
	{
		return $this->_retryDelay;
	}

	/**
	 * @param float|string $seconds the seconds before the first retry
	 * @throws TInvalidDataValueException when the seconds are less than zero
	 * @since 4.4.0
	 */
	public function setRetryDelay($seconds)
	{
		$seconds = TPropertyValue::ensureFloat($seconds);
		if ($seconds < 0) {
			throw new TInvalidDataValueException('crontask_retrydelay_invalid', $seconds);
		}
		$this->_retryDelay = $seconds;
	}

	/**
	 * @return float the factor of the delay before each further retry; default 2
	 * @since 4.4.0
	 */
	public function getRetryBackoff(): float
	{
		return $this->_retryBackoff;
	}

	/**
	 * @param float|string $factor the factor of the delay before each further retry; 1 keeps the delay
	 * @throws TInvalidDataValueException when the factor is less than 1
	 * @since 4.4.0
	 */
	public function setRetryBackoff($factor)
	{
		$factor = TPropertyValue::ensureFloat($factor);
		if ($factor < 1) {
			throw new TInvalidDataValueException('crontask_retrybackoff_invalid', $factor);
		}
		$this->_retryBackoff = $factor;
	}

	/**
	 * @param int $retry the retry, from 1
	 * @return float the seconds before the retry: RetryDelay times RetryBackoff to the power of the retry less one
	 * @since 4.4.0
	 */
	public function getRetryWait(int $retry): float
	{
		return $this->_retryDelay * ($this->_retryBackoff ** max(0, $retry - 1));
	}

	/**
	 * @return ?string the time of day the runs are limited to, 'HH:MM-HH:MM' in the schedule time zone, or null
	 * @since 4.4.0
	 */
	public function getBetween(): ?string
	{
		return static::formatWindow($this->_between);
	}

	/**
	 * @param ?string $window the time of day the runs are limited to, 'HH:MM-HH:MM' or 'HH:MM:SS-HH:MM:SS'; a
	 *   start after the end spans midnight; null or '' for none
	 * @throws TInvalidDataValueException when the window does not parse
	 * @since 4.4.0
	 */
	public function setBetween($window)
	{
		$this->_between = static::parseWindow($window);
	}

	/**
	 * @return ?string the time of day the runs are kept out of, 'HH:MM-HH:MM' in the schedule time zone, or null
	 * @since 4.4.0
	 */
	public function getUnlessBetween(): ?string
	{
		return static::formatWindow($this->_unlessBetween);
	}

	/**
	 * @param ?string $window the time of day the runs are kept out of, as {@see setBetween}
	 * @throws TInvalidDataValueException when the window does not parse
	 * @since 4.4.0
	 */
	public function setUnlessBetween($window)
	{
		$this->_unlessBetween = static::parseWindow($window);
	}

	/**
	 * Tells whether a time is in the task's time of day: inside {@see getBetween() Between} and outside
	 * {@see getUnlessBetween() UnlessBetween}, in the schedule time zone.  The end of a window is inside it.
	 * @param ?int $time the time, default null for now
	 * @return bool whether a run may start at the time
	 * @since 4.4.0
	 */
	public function getIsInWindow(?int $time = null): bool
	{
		if ($this->_between === null && $this->_unlessBetween === null) {
			return true;
		}
		$local = (new \DateTimeImmutable('@' . ($time ?? $this->time())))->setTimezone(new \DateTimeZone($this->getScheduleTimeZone() ?? date_default_timezone_get()));
		$second = (int) $local->format('G') * 3600 + (int) $local->format('i') * 60 + (int) $local->format('s');
		$inside = static fn (array $w): bool => ($w[0] <= $w[1]) ? ($second >= $w[0] && $second <= $w[1]) : ($second >= $w[0] || $second <= $w[1]);
		return ($this->_between === null || $inside($this->_between)) && ($this->_unlessBetween === null || !$inside($this->_unlessBetween));
	}

	/**
	 * @param mixed $window 'HH:MM[:SS]-HH:MM[:SS]', null, or ''
	 * @throws TInvalidDataValueException when the window does not parse
	 * @return ?array{0: int, 1: int} the start and end second of the day, or null
	 * @since 4.4.0
	 */
	protected static function parseWindow($window): ?array
	{
		if ($window === null || $window === '') {
			return null;
		}
		$window = TPropertyValue::ensureString($window);
		if (!preg_match('/^\s*(\d{1,2}):(\d{2})(?::(\d{2}))?\s*-\s*(\d{1,2}):(\d{2})(?::(\d{2}))?\s*$/', $window, $m)
				|| $m[1] > 23 || $m[2] > 59 || ($m[3] !== '' && $m[3] > 59) || $m[4] > 23 || $m[5] > 59 || (($m[6] ?? '') !== '' && $m[6] > 59)) {
			throw new TInvalidDataValueException('crontask_window_invalid', $window);
		}
		return [(int) $m[1] * 3600 + (int) $m[2] * 60 + (int) $m[3], (int) $m[4] * 3600 + (int) $m[5] * 60 + (int) ($m[6] ?? 0)];
	}

	/**
	 * @param ?array{0: int, 1: int} $window the start and end second of the day
	 * @return ?string the window as 'HH:MM-HH:MM', with seconds when either has them
	 * @since 4.4.0
	 */
	protected static function formatWindow(?array $window): ?string
	{
		if ($window === null) {
			return null;
		}
		$seconds = ($window[0] % 60) || ($window[1] % 60);
		$format = static fn (int $s): string => sprintf('%02d:%02d', intdiv($s, 3600), intdiv($s % 3600, 60)) . ($seconds ? sprintf(':%02d', $s % 60) : '');
		return $format($window[0]) . '-' . $format($window[1]);
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
	 * @return int the most missed triggers a late sweep runs, one run each, the latest ones oldest first;
	 *   default 1, one run for all the missed triggers
	 * @since 4.4.0
	 */
	public function getCatchUp(): int
	{
		return $this->_catchUp;
	}

	/**
	 * @param int|string $value the most missed triggers a late sweep runs, one run each; 1 runs them as one
	 * @throws TInvalidDataValueException when the value is less than 1
	 * @since 4.4.0
	 */
	public function setCatchUp($value)
	{
		$value = TPropertyValue::ensureInteger($value);
		if ($value < 1) {
			throw new TInvalidDataValueException('crontask_catchup_invalid', $value);
		}
		$this->_catchUp = $value;
	}

	/**
	 * @return ?int the trigger the current or last run in this process served, after the Jitter; null before
	 *   a run and for a manual run.  A caught-up run ({@see getCatchUp CatchUp}) serves a missed trigger.
	 * @since 4.4.0
	 */
	public function getRunTrigger(): ?int
	{
		return $this->_runTrigger;
	}

	/**
	 * @param ?int $value the trigger the run serves, or null for a manual run; set by the cron module
	 * @since 4.4.0
	 */
	public function setRunTrigger(?int $value): void
	{
		$this->_runTrigger = $value;
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
	 * @return string[] the lowercase tags that group the task, for filtering and pausing together; default none
	 * @since 4.4.0
	 */
	public function getTags(): array
	{
		return $this->_tags;
	}

	/**
	 * Sets the tags.  A tag is trimmed, lowercased, and stripped of a leading `#`; tags are distinct.
	 * @param null|string|string[] $tags a list separated by commas or spaces, or an array
	 * @since 4.4.0
	 */
	public function setTags($tags)
	{
		$tags = ($tags === null || $tags === '') ? [] : (is_array($tags) ? $tags : preg_split('/[\s,]+/', TPropertyValue::ensureString($tags)));
		$this->_tags = array_values(array_unique(array_filter(array_map(static fn ($tag) => static::normalizeTag((string) $tag), $tags), static fn ($tag) => $tag !== '')));
	}

	/**
	 * @param string $tag the tag, with or without a leading `#`, in any case
	 * @return bool whether the task has the tag
	 * @since 4.4.0
	 */
	public function hasTag(string $tag): bool
	{
		return in_array(static::normalizeTag($tag), $this->_tags, true);
	}

	/**
	 * @param string $tag a tag
	 * @return string the tag trimmed, lowercased, and without a leading `#`
	 * @since 4.4.0
	 */
	public static function normalizeTag(string $tag): string
	{
		return strtolower(ltrim(trim($tag), '#'));
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
	 * @return float the order of the task among the tasks due in a sweep, lower first; default {@see DEFAULT_PRIORITY}
	 * @since 4.4.0
	 */
	public function getPriority(): float
	{
		return $this->_priority;
	}

	/**
	 * @param float|int|string $priority the order of the task among the tasks due in a sweep, lower first
	 * @since 4.4.0
	 */
	public function setPriority($priority)
	{
		$this->_priority = ($priority === null || $priority === '') ? (float) self::DEFAULT_PRIORITY : TPropertyValue::ensureFloat($priority);
	}

	/**
	 * @return string[] the names of the tasks the cron module runs after a run of this task without an exception
	 * @since 4.4.0
	 */
	public function getThen(): array
	{
		return $this->_then;
	}

	/**
	 * @param null|string|string[] $names the names of the tasks run after a successful run: a comma list or an array
	 * @since 4.4.0
	 */
	public function setThen($names)
	{
		$this->_then = static::parseTaskNames($names);
	}

	/**
	 * @return string[] the names of the tasks the cron module runs after a failed run of this task
	 * @since 4.4.0
	 */
	public function getThenOnFailure(): array
	{
		return $this->_thenOnFailure;
	}

	/**
	 * @param null|string|string[] $names the names of the tasks run after a failed run: a comma list or an array
	 * @since 4.4.0
	 */
	public function setThenOnFailure($names)
	{
		$this->_thenOnFailure = static::parseTaskNames($names);
	}

	/**
	 * @param null|string|string[] $names a comma list or an array of task names
	 * @return string[] the trimmed, distinct, non-empty names
	 * @since 4.4.0
	 */
	protected static function parseTaskNames($names): array
	{
		if ($names === null || $names === '') {
			return [];
		}
		$names = is_array($names) ? $names : explode(',', TPropertyValue::ensureString($names));
		return array_values(array_unique(array_filter(array_map(static fn ($name) => trim((string) $name), $names), static fn ($name) => $name !== '')));
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
			$this->_scheduler->setTimeZone($this->getScheduleTimeZone());
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
		if ($this->_overlap === TCronTaskOverlap::Skip) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_overlap";
		}
		foreach (['_processOutputFile', '_processPriority', '_processMemoryLimit', '_warnDuration'] as $property) {
			if ($this->$property === null) {
				$exprops[] = "\0Prado\Util\Cron\TCronTask\0" . $property;
			}
		}
		if ($this->_timeZone === null) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_timeZone";
		}
		if ($this->_startingDeadline === null) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_startingDeadline";
		}
		if ($this->_catchUp === 1) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_catchUp";
		}
		$exprops[] = "\0Prado\Util\Cron\TCronTask\0_runTrigger";
		if ($this->_description === '') {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_description";
		}
		if ($this->_tags === []) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_tags";
		}
		if ($this->_pingUrls === []) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_pingUrls";
		}
		$exprops[] = "\0Prado\Util\Cron\TCronTask\0_defaultTimeZone";
		if ($this->_priority === (float) self::DEFAULT_PRIORITY) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_priority";
		}
		if ($this->_then === []) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_then";
		}
		if ($this->_thenOnFailure === []) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_thenOnFailure";
		}
		if ($this->_retries === 0) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_retries";
		}
		if ($this->_retryDelay === 0.0) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_retryDelay";
		}
		if ($this->_retryBackoff === 2.0) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_retryBackoff";
		}
		if ($this->_between === null) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_between";
		}
		if ($this->_unlessBetween === null) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_unlessBetween";
		}
		if ($this->_jitter === 0) {
			$exprops[] = "\0Prado\Util\Cron\TCronTask\0_jitter";
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
