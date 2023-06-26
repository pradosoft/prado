<?php
/**
 * TLogRouter, TLogRoute, TFileLogRoute, TEmailLogRoute class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TLogRoute class.
 *
 * TLogRoute is the base class for all log route classes.
 * A log route object retrieves log messages from a logger and sends it
 * somewhere, such as files, emails.
 * The messages being retrieved may be filtered first before being sent
 * to the destination. The filters include log level filter and log category filter.
 *
 * To specify level filter, set {@link setLevels Levels} property,
 * which takes a string of comma-separated desired level names (e.g. 'Error, Debug').
 * To specify category filter, set {@link setCategories Categories} property,
 * which takes a string of comma-separated desired category names (e.g. 'Prado\Web, Prado\IO').
 *
 * The categories filter can use '!' or '~', e.g. '!Prado\Web\UI' or '~Prado\Web\UI',
 * to exclude categories.  Added 4.2.3.
 *
 * Level filter and category filter are combinational, i.e., only messages
 * satisfying both filter conditions will they be returned.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 3.0
 */
abstract class TLogRoute extends \Prado\TApplicationComponent
{
	/**
	 * @var array lookup table for level names
	 */
	protected static $_levelNames = [
		TLogger::DEBUG => 'Debug',
		TLogger::INFO => 'Info',
		TLogger::NOTICE => 'Notice',
		TLogger::WARNING => 'Warning',
		TLogger::ERROR => 'Error',
		TLogger::ALERT => 'Alert',
		TLogger::FATAL => 'Fatal',
		// @ since 4.2.3:
		TLogger::PROFILE => 'Profile',
		TLogger::PROFILE_BEGIN => 'Profile Begin',
		TLogger::PROFILE_END => 'Profile End',
	];
	/**
	 * @var array lookup table for level values
	 */
	protected static $_levelValues = [
		'debug' => TLogger::DEBUG,
		'info' => TLogger::INFO,
		'notice' => TLogger::NOTICE,
		'warning' => TLogger::WARNING,
		'error' => TLogger::ERROR,
		'alert' => TLogger::ALERT,
		'fatal' => TLogger::FATAL,
		// @ since 4.2.3:
		'profile' => TLogger::PROFILE,
		'profile begin' => TLogger::PROFILE_BEGIN_SELECT,
		'profile end' => TLogger::PROFILE_END_SELECT,
	];
	/**
	 * @var array of stored route logs
	 */
	protected array $_logs = [];
	/**
	 * @var int the number of logs to save before processing, default 1000
	 * @since 4.2.3
	 */
	private ?int $_processInterval = 1000;
	/**
	 * @var int log level filter (bits), default null.
	 */
	private ?int $_levels = null;
	/**
	 * @var ?array log category filter
	 */
	private ?array $_categories = [];
	/**
	 * @var bool|callable Whether the route is enabled, default true
	 * @since 4.2.3
	 */
	private mixed $_enabled = true;
	/**
	 * @var ?callable The prefix callable
	 * @since 4.2.3
	 */
	private mixed $_prefix = null;
	/**
	 * @var bool display the time with subseconds, default false.
	 * @since 4.2.3
	 */
	private bool $_displaySubSeconds = false;
	/**
	 * @var float the maximum delta for the log items, default 0.
	 * @since 4.2.3
	 */
	private float $_maxDelta = 0;
	/**
	 * @var float The computed total time of the logs, default 0.
	 * @since 4.2.3
	 */
	private float $_totalTime = 0;

	/**
	 * Initializes the route.
	 * @param \Prado\Xml\TXmlElement $config configurations specified in {@link TLogRouter}.
	 */
	public function init($config)
	{
	}

	/**
	 *
	 * @return int the stored logs for the route.
	 */
	public function getLogCount(): int
	{
		return count($this->_logs);
	}

	/**
	 * @return ?int log level filter
	 */
	public function getLevels(): ?int
	{
		return $this->_levels;
	}

	/**
	 * @param int|string $levels integer log level filter (in bits). If the value is
	 * a string, it is assumed to be comma-separated level names. Valid level names
	 * include 'Debug', 'Info', 'Notice', 'Warning', 'Error', 'Alert', 'Fatal', 'Profile,
	 * 'Profile Begin', and 'Profile End'.
	 * @return static The current object.
	 */
	public function setLevels($levels): static
	{
		if (is_int($levels)) {
			$invalidLevels = ~array_reduce(static::$_levelValues, function ($levels, $level) {
				return $levels | $level;
			}, 0);
			if ($invalidLevels & $levels) {
				throw new TConfigurationException('logroute_bad_level', '0x' . dechex($levels));
			}
			$this->_levels = $levels;
		} else {
			$this->_levels = null;
			if (!is_array($levels)) {
				$levels = preg_split('/[|,]/', $levels);
			}
			foreach ($levels as $level) {
				$level = str_replace('_', ' ', strtolower(trim($level)));
				if (isset(static::$_levelValues[$level])) {
					$this->_levels |= static::$_levelValues[$level];
				}
			}
		}

		return $this;
	}

	/**
	 * @return array list of categories to be looked for
	 */
	public function getCategories()
	{
		return $this->_categories;
	}

	/**
	 * @param array|string $categories list of categories to be looked for. If the value is a string,
	 * it is assumed to be comma-separated category names.
	 * @return static The current object.
	 */
	public function setCategories($categories): static
	{
		if (is_array($categories)) {
			$this->_categories = $categories;
		} else {
			$this->_categories = null;
			foreach (explode(',', $categories) as $category) {
				if (($category = trim($category)) !== '') {
					$this->_categories[] = $category;
				}
			}
		}

		return $this;
	}

	/**
	 * @param int $level level value
	 * @return string level name
	 */
	protected function getLevelName($level)
	{
		return self::$_levelNames[$level] ?? 'Unknown';
	}

	/**
	 * @param string $level level name
	 * @return int level value
	 */
	protected function getLevelValue($level)
	{
		return static::$_levelValues[$level] ?? 0;
	}

	/**
	 * @return bool Is the log enabled. Defaults is true.
	 * @since 4.2.3
	 */
	public function getEnabled(): bool
	{
		if (is_callable($this->_enabled)) {
			return call_user_func($this->_enabled, $this);
		}
		return $this->_enabled;
	}

	/**
	 * This can be a boolean or a callable in the format:
	 * ```php
	 *		$route->setEnabled(
	 *				function(TLogRoute $route):bool {
	 * 					return !Prado::getUser()?->getIsGuest()
	 *				}
	 *			);
	 * ```
	 *
	 * @param bool|callable $value Whether the route is enabled.
	 * @return static $this
	 * @since 4.2.3
	 */
	public function setEnabled($value): static
	{
		if (!is_callable($value)) {
			$value = TPropertyValue::ensureBoolean($value);
		}
		$this->_enabled = $value;

		return $this;
	}

	/**
	 * @return int The number of logs before they are processed by the route.
	 * @since 4.2.3
	 */
	public function getProcessInterval(): int
	{
		return $this->_processInterval;
	}

	/**
	 * @param int $value The number of logs before they are processed by the route.
	 * @since 4.2.3
	 */
	public function setProcessInterval($value): static
	{
		$this->_processInterval = TPropertyValue::ensureInteger($value);

		return $this;
	}

	/**
	 * @return callable Changes the prefix.
	 * @since 4.2.3
	 */
	public function getPrefixCallback(): mixed
	{
		return $this->_prefix;
	}

	/**
	 * This is the application callback for changing the prefix in the format of:
	 * ```php
	 *		$route->setPrefixCallback(function(array $log, string $prefix) {
	 * 				return $prefix . '[my data]';
	 *			});
	 * ```
	 * @param callable $value Changes the prefix.
	 * @return static The current object.
	 * @since 4.2.3
	 */
	public function setPrefixCallback(mixed $value): static
	{
		if (!is_callable($value)) {
			throw new TConfigurationException('logroute_bad_prefix_callback');
		}
		$this->_prefix = $value;

		return $this;
	}

	/**
	 * @return bool display the subseconds with the time during logging.
	 * @since 4.2.3
	 */
	public function getDisplaySubSeconds(): bool
	{
		return $this->_displaySubSeconds;
	}

	/**
	 * @param bool|string $value display the subseconds with the time during logging.
	 * @since 4.2.3
	 */
	public function setDisplaySubSeconds($value): static
	{
		$this->_displaySubSeconds = TPropertyValue::ensureBoolean($value);

		return $this;
	}

	/**
	 * Formats a log message given different fields.
	 * @param array $log The log to format
	 * @return string formatted message
	 */
	public function formatLogMessage(array $log): string
	{
		$prefix = $this->getLogPrefix($log);
		$traces = [];
		if (!is_string($log[TLogger::LOG_MESSAGE])) {
			if ($log[TLogger::LOG_MESSAGE] instanceof \Exception || $log[TLogger::LOG_MESSAGE] instanceof \Throwable) {
				$log[TLogger::LOG_MESSAGE] = (string) $log[TLogger::LOG_MESSAGE];
			} else {
				$log[TLogger::LOG_MESSAGE] = \Prado\Util\TVarDumper::dump($log[TLogger::LOG_MESSAGE]);
			}
		}
		if (!is_string($log[TLogger::LOG_MESSAGE])) {
			if ($log[TLogger::LOG_MESSAGE] instanceof \Exception || $log[TLogger::LOG_MESSAGE] instanceof \Throwable) {
				$log[TLogger::LOG_MESSAGE] = (string) $log[TLogger::LOG_MESSAGE];
			} else {
				$log[TLogger::LOG_MESSAGE] = \Prado\Util\TVarDumper::dump($log[TLogger::LOG_MESSAGE]);
			}
		}
		if (isset($log[TLogger::LOG_TRACES])) {
			$traces = array_map(fn ($trace) => "in {$trace['file']}:{$trace['line']}", $log[TLogger::LOG_TRACES]);
		}
		return $this->getTime($log[TLogger::LOG_TIME]) . ' ' . $prefix . '[' . $this->getLevelName($log[TLogger::LOG_LEVEL]) . '] [' . $log[TLogger::LOG_CATEGORY] . '] ' . $log[TLogger::LOG_MESSAGE]
			. (empty($traces) ? '' : "\n    " . implode("\n    ", $traces));
	}

	/**
	 * @param array $log
	 * @return string The prefix for the message
	 * @since 4.2.3
	 */
	public function getLogPrefix(array $log): string
	{
		if (($app = Prado::getApplication()) === null) {
			if ($ip = $_SERVER['REMOTE_ADDR'] ?? null) {
				$result = '[' . $ip . ']';
				if ($this->_prefix !== null) {
					return call_user_func($this->_prefix, $log, $result);
				}
				return $result;
			}
			return '';
		}

		$ip = $app->getRequest()->getUserHostAddress() ?? '-';

		$user = $app->getUser();
		if ($user && ($name = $user->getName())) {
			$userName = $name;
		} else {
			$userName = '-';
		}

		$session = $app->getSession();
		$sessionID = ($session && $session->getIsStarted()) ? $session->getSessionID() : '-';

		$result = "[{$ip}][{$userName}][{$sessionID}]";
		if ($log[TLogger::LOG_PID] !== getmypid()) {
			$result .= '[pid:' . $log[TLogger::LOG_PID] . ']';
		}
		if ($this->_prefix !== null) {
			return call_user_func($this->_prefix, $log, $result);
		}
		return $result;
	}

	/**
	 * @param float $timestamp The timestamp to format
	 * @return string The formatted time
	 * @since 4.2.3
	 */
	protected function getTime(float $timestamp): string
	{
		$parts = explode('.', sprintf('%F', $timestamp));

		return date('Y-m-d H:i:s', $parts[0]) . ($this->_displaySubSeconds ? ('.' . $parts[1]) : '');
	}

	/**
	 * Given $normalizedTime between 0 and 1 will produce an associated color.
	 * Lowest values (~0) are black, then blue, green, yellow, orange, and red at 1.
	 * @since 4.2.3
	 * @param float $normalizedTime
	 * @return array [red, green, blue] values for the color of the log.
	 */
	public static function getLogColor(float $normalizedTime): array
	{
		return [
			255 * exp(-pow(2.5 * ($normalizedTime - 1), 4)),
			204 * exp(-pow(3.5 * ($normalizedTime - 0.5), 4)),
			255 * exp(-pow(10 * ($normalizedTime - 0.13), 4)),
		];
	}

	/**
	 * This filters the logs to calculate the delta and total times.  It also calculates
	 * the Profile times based upon the begin and end logged profile logs
	 * @param array &$logs The logs to filter.
	 * array(
	 *   [0] => message
	 *   [1] => level
	 *   [2] => category
	 *   [3] => timestamp (by microtime(true), float number)
	 *   [4] => memory in bytes
	 *   [5] => control client id
	 * 		@ since 4.2.3:
	 *   [6] => traces, when configured
	 *   [7] => process id)
	 * @since 4.2.3
	 */
	public function filterLogs(&$logs)
	{
		$next = [];
		$nextNext = [];

		foreach(array_reverse(array_keys($logs)) as $key) {
			$next[$key] = $nextNext[$logs[$key][TLogger::LOG_PID]] ?? null;
			$nextNext[$logs[$key][TLogger::LOG_PID]] = $key;
		}
		unset($nextNext);

		$profile = [];
		$profileLast = [];
		$profileTotal = [];
		$startTime = $_SERVER["REQUEST_TIME_FLOAT"];
		foreach(array_keys($logs) as $key) {
			if (isset($next[$key])) {
				$logs[$key]['delta'] = $logs[$next[$key]][TLogger::LOG_TIME] - $logs[$key][TLogger::LOG_TIME];
				$total = $logs[$key]['total'] = $logs[$key][TLogger::LOG_TIME] - $startTime;
			} else {
				$logs[$key]['delta'] = '?';
				$total = $logs[$key]['total'] = $logs[$key][TLogger::LOG_TIME] - $startTime;
			}
			if ($total > $this->_totalTime) {
				$this->_totalTime = $total;
			}
			if(($logs[$key][TLogger::LOG_LEVEL] & TLogger::PROFILE_BEGIN) === TLogger::PROFILE_BEGIN) {
				$profileToken = $logs[$key][TLogger::LOG_MESSAGE] . $logs[$key][TLogger::LOG_PID];
				$profile[$profileToken] = $logs[$key];
				$profileLast[$profileToken] = false;
				$profileTotal[$profileToken] ??= 0;
				$logs[$key]['delta'] = 0;
				$logs[$key]['total'] = 0;
				$logs[$key][TLogger::LOG_MESSAGE] = 'Profile Begin: ' . $logs[$key][TLogger::LOG_MESSAGE];

			} elseif(($logs[$key][TLogger::LOG_LEVEL] & TLogger::PROFILE_END) === TLogger::PROFILE_END) {
				$profileToken = $logs[$key][TLogger::LOG_MESSAGE] . $logs[$key][TLogger::LOG_PID];
				if (isset($profile[$profileToken])) {
					if ($profileLast[$profileToken] !== false) {
						$delta = $logs[$key][TLogger::LOG_TIME] - $profileLast[$profileToken][TLogger::LOG_TIME];
					} else {
						$delta = $logs[$key][TLogger::LOG_TIME] - $profile[$profileToken][TLogger::LOG_TIME];
					}
					$profileTotal[$profileToken] += $delta;
					$logs[$key]['delta'] = $delta;
					$logs[$key]['total'] = $profileTotal[$profileToken];
				}
				$profileLast[$profileToken] = $logs[$key];
				$logs[$key][TLogger::LOG_MESSAGE] = 'Profile End: ' . $logs[$key][TLogger::LOG_MESSAGE];
			}
			if (is_numeric($logs[$key]['delta']) && ($this->_maxDelta === null || $logs[$key]['delta'] > $this->_maxDelta)) {
				$this->_maxDelta = $logs[$key]['delta'];
			}
		}
		$logs = array_values(array_filter($logs, fn ($l): bool => !($l[TLogger::LOG_LEVEL] & TLogger::LOGGED)));
	}

	/**
	 * Retrieves log messages from logger to log route specific destination.
	 * @param TLogger $logger logger instance
	 * @param bool $final is the final collection of logs
	 */
	public function collectLogs(null|bool|TLogger $logger = null, bool $final = false)
	{
		if (is_bool($logger)) {
			$final = $logger;
			$logger = null;
		}
		if ($logger) {
			$logs = $logger->getLogs($this->getLevels(), $this->getCategories());
			$this->filterLogs($logs);
			$this->_logs = array_merge($this->_logs, $logs);
		}
		$count = count($this->_logs);
		$final |= $this instanceof IOutputLogRoute;
		if ($count > 0 && ($final || $this->_processInterval > 0 && $count >= $this->_processInterval)) {
			$logs = $this->_logs;
			$meta = ['maxdelta' => $this->_maxDelta, 'total' => $this->_totalTime] ;
			$this->_logs = [];
			$this->_maxDelta = 0;
			$this->_totalTime = 0;
			$saved = $this->_processInterval;
			$this->_processInterval = 0;
			$this->processLogs($logs, $final, $meta);
			$this->_processInterval = $saved;
		}
	}

	/**
	 * Processes log messages and sends them to specific destination.
	 * Derived child classes must implement this method.
	 * @param array $logs list of messages.  Each array elements represents one message
	 * with the following structure:
	 * array(
	 *   [0] => message
	 *   [1] => level
	 *   [2] => category
	 *   [3] => timestamp (by microtime(time), float number)
	 *   [4] => memory in bytes
	 *   [5] => control client id
	 *     @ since 4.2.3:
	 *   [6] => traces, when configured
	 *   [7] => process id)
	 * @param bool $final
	 * @param array $meta
	 */
	abstract protected function processLogs(array $logs, bool $final, array $meta);
}
