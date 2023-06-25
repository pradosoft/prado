<?php
/**
 * TLogger class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

use Prado\Prado;
use Prado\TEventParameter;
use Prado\Web\UI\TControl;

/**
 * TLogger class.
 *
 * TLogger records log messages in memory and implements the methods to
 * retrieve the messages with filter conditions, including log levels,
 * log categories, and by control.
 *
 * Filtering categories and controls can use a '!' or '~' prefix and the element
 * will be excluded rather than included.  Using a trailing '*' will include 
 * elements starting with the specified text.
 *
 * The log message, log level, log category (class), microtime Time stamp
 * memory used, control ID, traces, and process ID.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 3.0
 */
class TLogger extends \Prado\TComponent
{
	/**
	 * Log levels.
	 */
	public const DEBUG = 0x01;
	public const INFO = 0x02;
	public const NOTICE = 0x04;
	public const WARNING = 0x08;
	public const ERROR = 0x10;
	public const ALERT = 0x20;
	public const FATAL = 0x40;

	public const PROFILE = 0x100;
	public const PROFILE_BEGIN = 0x300;
	public const PROFILE_END = 0x500;

	/**
	 * These are for selecting just the PROFILE_BEGIN and PROFILE_END logs without
	 * the PROFILE bit.
	 */
	public const PROFILE_BEGIN_SELECT = 0x200;
	public const PROFILE_END_SELECT = 0x400;

	public const LOGGED = 0x8000;

	/**
	 * Log Information Index.
	 */
	public const LOG_MESSAGE = 0;
	public const LOG_LEVEL = 1;
	public const LOG_CATEGORY = 2;
	public const LOG_TIME = 3;
	public const LOG_MEMORY = 4;
	public const LOG_CONTROL = 5;
	public const LOG_TRACES = 6;
	public const LOG_PID = 7;

	/**
	 * @var array log messages
	 */
	private array $_logs = [];
	/**
	 * @var array unmatched PROFILE_BEGIN log messages
	 * @since 4.2.3
	 */
	private array $_profileLogs = [];
	/**
	 * @var int the profileLogs count that are not static::LOGGED.
	 * @since 4.2.3
	 */
	private int $_profileLogsCount = 0;
	/**
	 * @var array The maintained Profile Begin times
	 * @since 4.2.3
	 */
	private array $_profileBeginTimes = [];
	/**
	 * @var ?int log levels (bits) to be filtered
	 */
	private ?int $_levels = null;
	/**
	 * @var ?array list of categories to be filtered
	 */
	private ?array $_categories = null;
	/**
	 * @var array list of control client ids to be filtered
	 */
	private ?array $_controls = null;
	/**
	 * @var ?float timestamp used to filter
	 */
	private ?float $_timestamp;
	/**
	 * @var ?int process id used to filter
	 * @since 4.2.3
	 */
	private ?int $_pid;

	/**
	 * @var int the number of logs before flushing.
	 * @since 4.2.3
	 */
	private int $_flushCount = 1000;
	/**
	 * @var bool is the logger flushing.
	 * @since 4.2.3
	 */
	private bool $_flushing = false;
	/**
	 * @var ?array any logged messages during flushing so they aren't flushed.
	 * @since 4.2.3
	 */
	private ?array $_flushingLog = null;
	/**
	 * @var int The depth of a trace, default 0 for no trace.
	 * @since 4.2.3
	 */
	private int $_traceLevel = 0;
	/**
	 * @var bool Is the logger Registered with onEndRequest
	 * @since 4.2.3
	 */
	private bool $_registered = false;

	/**
	 * @param bool $flushShutdown Should onFlushLogs be a register_shutdown_function.
	 * @since 4.2.3
	 */
	public function __construct(bool $flushShutdown = true)
	{
		if($flushShutdown) {
			register_shutdown_function(function () {
				$this->onFlushLogs();
				register_shutdown_function([$this, 'onFlushLogs'], $this, true);
			});
		}
		parent::__construct();
	}

	/**
	 * @return int The number of logs before triggering {@see self::onFlushLogs()}
	 * @since 4.2.3
	 */
	public function getFlushCount(): int
	{
		return $this->_flushCount;
	}

	/**
	 * @param int $value the number of logs before triggering {@see self::onFlushLogs()}
	 * @return static $this
	 * @since 4.2.3
	 */
	public function setFlushCount(int $value): static
	{
		$this->_flushCount = $value;

		$this->checkProfileLogsSize();
		$this->checkLogsSize();

		return $this;
	}

	/**
	 * @return int How much debug trace stack information to include.
	 * @since 4.2.3
	 */
	public function getTraceLevel(): int
	{
		return $this->_traceLevel;
	}

	/**
	 * @param int $value How much debug trace stack information to include.
	 * @return static $this
	 * @since 4.2.3
	 */
	public function setTraceLevel(int $value): static
	{
		$this->_traceLevel = $value;

		return $this;
	}

	/**
	 * This returns the number of logs being held.  When the $selector the following
	 * is returned:
	 *   - null is the ~ number of logs a route will receive
	 *   - 0 is the ~ number of logs to be collected before removing static::LOGGGED logs
	 *   - true is the number of logs without profile begin
	 *   - false is the number of profile begin logs
	 *
	 * When a PROFILE_BEGIN is flushed without a paired PROFILE_END, then it is logged
	 * and flagged as static::LOGGED.  These log items will be resent until and with
	 * a paired PROFILE_END.  The PROFILE_BEGIN are relogged to computing timing but are
	 * removed before being processed by the log route.
	 *
	 * @param ?bool $selector Which list of logs to count. Default null for both.
	 * @return int The number of logs.
	 * @since 4.2.3
	 */
	public function getLogCount(null|bool|int $selector = null): int
	{
		if ($selector === null) {// logs @TLogRoute::processLogs after removing logged elements.
			return count($this->_logs) + $this->_profileLogsCount;
		} elseif ($selector === 0) {// logs @TLogRoute::collectLogs, from TLogger::getLogs
			return count($this->_logs) + count($this->_profileLogs);
		} elseif ($selector) {
			return count($this->_logs);
		} //elseif false
		return count($this->_profileLogs);
	}

	/**
	 * This is the number of Profile Begin Logs that have been logged.
	 * @return int The Profile Logs already logged.
	 * @since 4.2.3
	 */
	public function getLoggedProfileLogCount(): int
	{
		return count($this->_profileLogs) - $this->_profileLogsCount;
	}

	/**
	 * Ensures that the logger is registered with the Application onEndRequest.
	 * @since 4.2.3
	 */
	protected function ensureFlushing()
	{
		if ($this->_registered) {
			return;
		}

		if ($app = Prado::getApplication()) {
			$app->attachEventHandler('onEndRequest', [$this, 'onFlushLogs'], 20);
			//$app->attachEventHandler(TProcessHelper::FX_TERMINATE_PROCESS, [$this, 'onFlushLogs'], 20);
			$this->_registered = true;
		}
	}

	/**
	 * Adds a single log item
	 * @param array $log The log item to log.
	 * @param bool $flush Allow a flush on adding if the log size is FlushCount, default true.
	 * @return ?float The time delta for PROFILE_END; and null in other cases.
	 * @since 4.2.3
	 */
	protected function addLog(array $log, bool $flush = true): ?float
	{
		$return = null;
		if ($this->_flushing) {
			$this->_flushingLog[] = $log;
			return $return;
		}
		if ($log[static::LOG_LEVEL] & TLogger::PROFILE_BEGIN_SELECT) {
			$profileName = $this->dyProfilerRename('[' . $log[static::LOG_MESSAGE] . '][' . $log[static::LOG_PID] . ']');
			$add = !isset($this->_profileLogs[$profileName]);
			$this->_profileLogs[$profileName] = $log;
			$this->_profileBeginTimes[$profileName] = $log[static::LOG_TIME];
			if ($add) {
				$this->_profileLogsCount++;
				$this->checkProfileLogsSize($flush);
			}
		} else {
			if ($log[static::LOG_LEVEL] & TLogger::PROFILE_END_SELECT) { // Move ProfileBegin to the primary log.
				$profileName = $this->dyProfilerRename('[' . $log[static::LOG_MESSAGE] . '][' . $log[static::LOG_PID] . ']');
				if (isset($this->_profileLogs[$profileName])) {
					$this->_logs[] = $this->_profileLogs[$profileName];

					if (!($this->_profileLogs[$profileName][static::LOG_LEVEL] & static::LOGGED)) {
						$this->_profileLogsCount--;
					}

					unset($this->_profileLogs[$profileName]);
				}
				if (isset($this->_profileBeginTimes[$profileName])) {
					$return = $log[static::LOG_TIME] - $this->_profileBeginTimes[$profileName];
				}
			}
			$this->_logs[] = $log;
			$this->checkLogsSize($flush);
		}
		return $return;
	}

	/**
	 * When the ProfileLog Count is equal or more than the FlushCount, then the Retained
	 * profile logs are deleted and a WARNING is logged.
	 * @param bool $flush Is the log being flushing, default true.
	 * @since 4.2.3
	 */
	protected function checkProfileLogsSize(bool $flush = true)
	{
		if ($this->_flushCount && $this->getLogCount(false) >= $this->_flushCount) {
			$this->deleteProfileLogs();
			$saved = null;
			if (!$flush) {
				$saved = $this->_flushCount;
				$this->_flushCount = 0;
			}
			$this->log("Too many retained profiler logs", static::WARNING, self::class . '\Profiler');
			if (!$flush) {
				$this->_flushCount = $saved;
			}
		}
	}

	/**
	 * If we allow a flush (not a bulk logging), and the log count is equal to or more
	 * than the FlushCount then we flush the logs.
	 * @param bool $flush Is the log being flushing, default true.
	 * @since 4.2.3
	 */
	protected function checkLogsSize(bool $flush = true)
	{
		if ($flush && $this->_flushCount && $this->getLogCount() >= $this->_flushCount) {
			$this->onFlushLogs();
		}
	}

	/**
	 * Logs a message.
	 * Messages logged by this method may be retrieved via {@link getLogs}.
	 * @param mixed $token message to be logged
	 * @param int $level level of the message. Valid values include
	 * TLogger::DEBUG, TLogger::INFO, TLogger::NOTICE, TLogger::WARNING,
	 * TLogger::ERROR, TLogger::ALERT, TLogger::FATAL, TLogger::PROFILE,
	 * TLogger::PROFILE_BEGIN, TLogger::PROFILE_END
	 * @param ?string $category category of the message
	 * @param null|string|TControl $ctl control of the message
	 * @return ?float The time delta for PROFILE_END; and null in other cases.
	 */
	public function log(mixed $token, int $level, ?string $category = null, mixed $ctl = null): ?float
	{
		$this->ensureFlushing();

		if ($ctl instanceof TControl) {
			$ctl = $ctl->getClientID();
		} elseif (!is_string($ctl)) {
			$ctl = null;
		}

		if ($category === null) {
			$category = Prado::callingObject()::class;
		}

		$traces = null;
		if ($this->_traceLevel > 0) {

			$traces = [];
			$count = 0;
			$allTraces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			array_pop($allTraces); // remove the last trace since it would be the entry script, not very useful

			foreach ($allTraces as $trace) {
				if (isset($trace['file'], $trace['line']) && strpos($trace['file'], PRADO_DIR) !== 0) {

					unset($trace['object'], $trace['args']);
					$traces[] = $trace;

					if (++$count >= $this->_traceLevel) {
						break;
					}
				}
			}
		}

		return $this->addLog([$token, $level, $category, microtime(true), memory_get_usage(), $ctl, $traces, getmypid()]);
	}


	/**
	 * This is an Event and an event handler.  When {@see Application::onEndRequest()}
	 * is raised and as a `register_shutdown_function` function, this is called.
	 * On calling, this raises the event `onFlushLogs`.
	 *
	 * @param mixed $sender the sender of the raised event.
	 * @param mixed $final true on `register_shutdown_function`.
	 * @since 4.2.3
	 */
	public function onFlushLogs(mixed $sender = null, mixed $final = null)
	{
		if ($this->_flushing || !count($this->_logs)) {
			return;
		}

		if(is_bool($sender)) {
			$final = $sender;
			$sender = null;
		}
		//if (($final instanceof TEventParameter) && $final->getEventName() === TProcessHelper::FX_TERMINATE_PROCESS) {
		//	$final = true;
		//} else
		if (!is_bool($final)) {
			$final = false;
		}

		$this->_flushing = true;
		$this->_flushingLog = [];
		$this->raiseEvent('onFlushLogs', $this, $final);
		$this->deleteLogs();
		$this->_flushing = false;
		if (count($this->_flushingLog)) {
			foreach($this->_flushingLog as $log) {
				$this->addLog($log, false); // $final = false to stop any possible recursion w/ low flushCount.
			}
		}
		$this->_flushingLog = null;

		$this->_profileLogsCount = 0;
		foreach(array_keys($this->_profileLogs) as $key) {
			$this->_profileLogs[$key][static::LOG_LEVEL] |= static::LOGGED;
		}
	}

	/**
	 * Retrieves log messages.
	 * Messages may be filtered by log levels and/or categories and/or control client ids and/or timestamp.
	 * A level filter is specified by an integer, whose bits indicate the levels interested.
	 * For example, (TLogger::INFO | TLogger::WARNING) specifies INFO and WARNING levels.
	 * A category filter is specified by an array of categories to filter.
	 * A message whose category name starts with any filtering category
	 * will be returned. For example, a category filter array('Prado\Web','Prado\IO')
	 * will return messages under categories such as 'Prado\Web', 'Prado\IO',
	 * 'Prado\Web\UI', 'Prado\Web\UI\WebControls', etc.
	 * A control client id filter is specified by an array of control client id
	 * A message whose control client id starts with any filtering naming panels
	 * will be returned. For example, a category filter array('ctl0_body_header',
	 * 'ctl0_body_content_sidebar')
	 * will return messages under categories such as 'ctl0_body_header', 'ctl0_body_content_sidebar',
	 * 'ctl0_body_header_title', 'ctl0_body_content_sidebar_savebutton', etc.
	 * A timestamp filter is specified as an interger or float number.
	 * A message whose registered timestamp is less or equal the filter value will be returned.
	 * Level filter, category filter, control filter and timestamp filter are combinational, i.e., only messages
	 * satisfying all filter conditions will they be returned.
	 * @param ?int $levels level filter
	 * @param null|array|string $categories category filter
	 * @param null|array|string $controls control filter
	 * @param null|float|int $timestamp filter
	 * @param ?int $pid
	 * @return array list of messages. Each array elements represents one message
	 * with the following structure:
	 * array(
	 *   [0] => message
	 *   [1] => level
	 *   [2] => category
	 *   [3] => timestamp (by microtime(true), float number)
	 *   [4] => memory in bytes
	 *   [5] => control client id; null when absent
	 *      @ since 4.2.3:
	 *   [6] => traces, when configured; null when absent
	 *   [7] => process id)
	 */
	public function getLogs(?int $levels = null, null|array|string $categories = null, null|array|string $controls = null, null|int|float $timestamp = null, ?int $pid = null)
	{
		$this->_levels = $levels;
		if (!empty($categories) && !is_array($categories)) {
			$categories = [$categories];
		}
		$this->_categories = $categories;
		if (!empty($controls) && !is_array($controls)) {
			$controls = [$controls];
		}
		$this->_controls = $controls;
		$this->_timestamp = $timestamp;
		$this->_pid = $pid;

		$logs = array_merge($this->_profileLogs, $this->_logs);
		if (empty($levels) && empty($categories) && empty($controls) && $timestamp === null && $pid === null) {
			usort($logs, [$this, 'orderByTimeStamp']);
			return $logs;
		}

		if (!empty($levels)) {
			$logs = array_filter($logs, [$this, 'filterByLevels']);
		}
		if (!empty($categories)) {
			$logs = array_filter($logs, [$this, 'filterByCategories']);
		}
		if (!empty($controls)) {
			$logs = array_filter($logs, [$this, 'filterByControl']);
		}
		if ($timestamp !== null) {
			$logs = array_filter($logs, [$this, 'filterByTimeStamp']);
		}
		if ($pid !== null) {
			$logs = array_filter($logs, [$this, 'filterByPID']);
		}

		usort($logs, [$this, 'orderByTimeStamp']);

		return array_values($logs);
	}

	/**
	 * This merges a set of logs with the current running logs.
	 * @param array $logs the logs elements to insert.
	 * @since 4.2.3
	 */
	public function mergeLogs(array $logs)
	{
		$count = count($logs);
		foreach($logs as $log) {
			$log[static::LOG_LEVEL] &= ~TLogger::LOGGED;
			$this->addLog($log, !(--$count));
		}
	}

	/**
	 * Deletes log messages from the queue.
	 * Messages may be filtered by log levels and/or categories and/or control client ids and/or timestamp.
	 * A level filter is specified by an integer, whose bits indicate the levels interested.
	 * For example, (TLogger::INFO | TLogger::WARNING) specifies INFO and WARNING levels.
	 * A category filter is specified by an array of categories to filter.
	 * A message whose category name starts with any filtering category
	 * will be deleted. For example, a category filter array('Prado\Web','Prado\IO')
	 * will delete messages under categories such as 'Prado\Web', 'Prado\IO',
	 * 'Prado\Web\UI', 'Prado\Web\UI\WebControls', etc.
	 * A control client id filter is specified by an array of control client id
	 * A message whose control client id starts with any filtering naming panels
	 * will be deleted. For example, a category filter array('ctl0_body_header',
	 * 'ctl0_body_content_sidebar')
	 * will delete messages under categories such as 'ctl0_body_header', 'ctl0_body_content_sidebar',
	 * 'ctl0_body_header_title', 'ctl0_body_content_sidebar_savebutton', etc.
	 * A timestamp filter is specified as an interger or float number.
	 * A message whose registered timestamp is less or equal the filter value will be returned.
	 * Level filter, category filter, control filter and timestamp filter are combinational, i.e., only messages
	 * satisfying all filter conditions will they be returned.
	 * @param null|int $levels level filter
	 * @param null|array|string $categories category filter
	 * @param null|array|string $controls control filter
	 * @param null|float|int $timestamp timestamp filter
	 * @param ?int $pid
	 * @return array The logs being delete are returned.
	 */
	public function deleteLogs(?int $levels = null, null|string|array $categories = null, null|string|array $controls = null, null|int|float $timestamp = null, ?int $pid = null): array
	{
		$this->_levels = $levels;
		if (!empty($categories) && !is_array($categories)) {
			$categories = [$categories];
		}
		$this->_categories = $categories;
		if (!empty($controls) && !is_array($controls)) {
			$controls = [$controls];
		}
		$this->_controls = $controls;
		$this->_timestamp = $timestamp;
		$this->_pid = $pid;

		if (empty($levels) && empty($categories) && empty($controls) && $timestamp === null && $pid === null) {
			$logs = $this->_logs;
			$this->_logs = [];
			return $logs;
		}
		$logs = $this->_logs;
		if (!empty($levels)) {
			$logs = (array_filter($logs, [$this, 'filterByLevels']));
		}
		if (!empty($categories)) {
			$logs = (array_filter($logs, [$this, 'filterByCategories']));
		}
		if (!empty($controls)) {
			$logs = (array_filter($logs, [$this, 'filterByControl']));
		}
		if ($timestamp !== null) {
			$logs = (array_filter($logs, [$this, 'filterByTimeStamp']));
		}
		if ($pid !== null) {
			$logs = array_filter($logs, [$this, 'filterByPID']);
		}
		$this->_logs = array_values(array_diff_key($this->_logs, $logs));

		return array_values($logs);
	}

	/**
	 * Deletes the retained Profile Begin logs.
	 * @return array The deleted Profile Begin Logs.
	 * @since 4.2.3
	 */
	public function deleteProfileLogs(): array
	{
		$profileLogs = array_values($this->_profileLogs);
		$this->_profileLogs = [];
		$this->_profileLogsCount = 0;
		$this->_profileBeginTimes = [];

		return $profileLogs;
	}

	/**
	 * Order function used by {@see static::getLogs()}.
	 * @param mixed $a First element to compare.
	 * @param mixed $b Second element to compare.
	 * @return int The order between the two elements.
	 * @since 4.2.3
	 */
	private function orderByTimeStamp($a, $b): int
	{
		return ($a[static::LOG_TIME] != $b[static::LOG_TIME]) ? ($a[static::LOG_TIME] <= $b[static::LOG_TIME]) ? -1 : 1 : 0;
	}

	/**
	 * Filter function used by {@see static::getLogs()}.
	 * @param array $log element to be filtered
	 * @return bool retain the element.
	 */
	private function filterByCategories($log): bool
	{
		$open = true;
		$match = false;
		$exclude = false;

		foreach ($this->_categories as $category) {
			if (empty($category)) {
				$category = '';
			}
			$c = $category[0] ?? 0;
			if ($c === '!' || $c === '~') {
				if(!$exclude) {
					$category = substr($category, 1);
					if ($log[static::LOG_CATEGORY] === $category || str_ends_with($category, '*') && strpos($log[static::LOG_CATEGORY], rtrim($category, '*')) === 0) {
						$exclude = true;
					}
				}
			} elseif (!$match) {
				$open = false;
				if (($log[static::LOG_CATEGORY] === $category || str_ends_with($category, '*') && strpos($log[static::LOG_CATEGORY], rtrim($category, '*')) === 0)) {
					$match = true;
				}
			}

			if ($match && $exclude) {
				break;
			}
		}

		return ($open || $match) && !$exclude;
	}

	/**
	 * Filter function used by {@see static::getLogs()}
	 * @param array $log element to be filtered
	 * @return bool retain the element.
	 */
	private function filterByLevels($log): bool
	{
		// element 1 are the levels
		if ($log[static::LOG_LEVEL] & $this->_levels) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Filter function used by {@see static::getLogs()}
	 * @param array $log element to be filtered
	 * @return bool retain the element.
	 */
	private function filterByControl($log): bool
	{
		$open = true;
		$match = false;
		$exclude = false;

		if (empty($log[static::LOG_CONTROL])) {
			$log[static::LOG_CONTROL] = '';
		}

		foreach ($this->_controls as $control) {
			if (empty($control)) {
				$control = '';
			}
			$c = $control[0] ?? 0;
			if ($c === '!' || $c === '~') {
				if(!$exclude) {
					$control = substr($control, 1);
					if ($log[static::LOG_CONTROL] === $control || str_ends_with($control, '*') && strpos($log[static::LOG_CONTROL], rtrim($control, '*')) === 0) {
						$exclude = true;
					}
				}
			} elseif (!$match) {
				$open = false;
				if (($log[static::LOG_CONTROL] === $control || str_ends_with($control, '*') && strpos($log[static::LOG_CONTROL], rtrim($control, '*')) === 0)) {
					$match = true;
				}
			}
			if ($match && $exclude) {
				break;
			}
		}

		return ($open || $match) && !$exclude;
	}

	/**
	 * Filter function used by {@see static::getLogs()}
	 * @param array $log element to be filtered
	 * @return bool retain the element.
	 */
	private function filterByTimeStamp($log): bool
	{
		if ($log[static::LOG_TIME] <= $this->_timestamp) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Filter function used by {@see static::getLogs()}
	 * @param array $log element to be filtered
	 * @return bool retain the element.
	 * @since 4.2.3
	 */
	private function filterByPID($log): bool
	{
		if ($log[static::LOG_PID] === $this->_pid) {
			return true;
		} else {
			return false;
		}
	}
}
