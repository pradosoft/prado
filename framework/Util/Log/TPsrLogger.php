<?php

/**
 * TPsrLogger class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Log;

use Prado\ISingleton;
use Prado\Prado;
use Prado\Web\UI\TControl;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;

/**
 * TPsrLogger class.
 *
 * TPsrLogger adapts {@see TLogger} to the PSR-3 {@see LoggerInterface}. A library
 * that accepts a PSR-3 logger writes into the application log through this class,
 * and {@see TLogRouter} routes the entries with the rest of the application log.
 *
 * ```php
 * $logger = TPsrLogger::singleton();
 * $logger->warning('Disk {disk} is at {percent}% capacity', ['disk' => '/dev/sda1', 'percent' => 92]);
 * $library->setLogger($logger);
 * ```
 *
 * {@see singleton()} returns the shared adapter of the application logger, created
 * on first use, per {@see ISingleton}. Separate instances bind to a specific
 * {@see TLogger} or category.
 *
 * PSR-3 level → {@see TLogger} level:
 * - `emergency`, `critical` → `TLogger::FATAL`
 * - `alert` → `TLogger::ALERT`
 * - `error` → `TLogger::ERROR`
 * - `warning` → `TLogger::WARNING`
 * - `notice` → `TLogger::NOTICE`
 * - `info` → `TLogger::INFO`
 * - `debug` → `TLogger::DEBUG`
 *
 * {@see log()} also accepts a TLogger integer level, including the profile levels.
 * Any other level throws {@see InvalidArgumentException}, as PSR-3 requires.
 *
 * Message placeholders in the form `{key}` are replaced by the matching context value.
 * The `CONTEXT_*` keys are decoded into the {@see TLogger} entry:
 *
 * | Key | Decoded as |
 * |---|---|
 * | `category` | log category; default {@see getCategory} |
 * | `level` | TLogger level; overrides the PSR-3 level |
 * | `time` | timestamp from `microtime(true)`; default now |
 * | `memory` | memory usage in bytes; default `memory_get_usage()` |
 * | `pid` | process ID; default `getmypid()` |
 * | `control` | {@see TControl} or client ID of the message |
 * | `traces` | call traces array |
 * | `exception` | a {@see \Throwable}; logged as the message token when the message is its `getMessage()`, otherwise appended to the message |
 * | `prefix`, `delta`, `total` | route-computed values; not stored |
 *
 * A {@see TPsrLogRoute} context therefore round-trips into an equivalent entry.
 *
 * {@see TPsrLogRoute} is the reverse adapter. It forwards the application log to an
 * external PSR-3 logger.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @phpstan-consistent-constructor
 * @since 4.4.0
 */
class TPsrLogger extends \Prado\TComponent implements LoggerInterface, ISingleton
{
	use LoggerTrait;

	/** Context key of the log category. */
	public const CONTEXT_CATEGORY = 'category';

	/** Context key of the {@see TLogger} level. */
	public const CONTEXT_LEVEL = 'level';

	/** Context key of the timestamp from `microtime(true)`. */
	public const CONTEXT_TIME = 'time';

	/** Context key of the memory usage in bytes. */
	public const CONTEXT_MEMORY = 'memory';

	/** Context key of the process ID. */
	public const CONTEXT_PID = 'pid';

	/** Context key of the {@see TLogRoute::getLogPrefix()} value. */
	public const CONTEXT_PREFIX = 'prefix';

	/** Context key of the {@see TControl} or client ID. */
	public const CONTEXT_CONTROL = 'control';

	/** Context key of the call traces array. */
	public const CONTEXT_TRACES = 'traces';

	/** Context key of the time delta computed by {@see TLogRoute::filterLogs()}. */
	public const CONTEXT_DELTA = 'delta';

	/** Context key of the total time computed by {@see TLogRoute::filterLogs()}. */
	public const CONTEXT_TOTAL = 'total';

	/** Context key of the {@see \Throwable} of the message. */
	public const CONTEXT_EXCEPTION = 'exception';

	/**
	 * @var array<string, int> PSR-3 level name → TLogger level
	 */
	protected static array $_psrLevels = [
		LogLevel::EMERGENCY => TLogger::FATAL,
		LogLevel::ALERT => TLogger::ALERT,
		LogLevel::CRITICAL => TLogger::FATAL,
		LogLevel::ERROR => TLogger::ERROR,
		LogLevel::WARNING => TLogger::WARNING,
		LogLevel::NOTICE => TLogger::NOTICE,
		LogLevel::INFO => TLogger::INFO,
		LogLevel::DEBUG => TLogger::DEBUG,
	];

	/**
	 * @var array<int, string> TLogger level → PSR-3 level name
	 */
	protected static array $_pradoLevels = [
		TLogger::FATAL => LogLevel::CRITICAL,
		TLogger::ALERT => LogLevel::ALERT,
		TLogger::ERROR => LogLevel::ERROR,
		TLogger::WARNING => LogLevel::WARNING,
		TLogger::NOTICE => LogLevel::NOTICE,
		TLogger::INFO => LogLevel::INFO,
		TLogger::DEBUG => LogLevel::DEBUG,
	];

	/**
	 * @var ?TPsrLogger the singleton adapter of the application logger.
	 */
	private static ?TPsrLogger $_singleton = null;

	/**
	 * @var ?TLogger the receiving logger; null for the application logger.
	 */
	private ?TLogger $_logger = null;

	/**
	 * @var ?string the default category; null for the calling class.
	 */
	private ?string $_category = null;

	/**
	 * @param ?TLogger $logger the receiving logger; null for {@see Prado::getLogger()}.
	 * @param ?string $category the default category; null for the calling class.
	 */
	public function __construct(?TLogger $logger = null, ?string $category = null)
	{
		parent::__construct();
		$this->_logger = $logger;
		$this->_category = $category;
	}

	/**
	 * Returns the singleton adapter of the application logger.
	 * The adapter is created on first use as the called class, with a null {@see getLogger Logger}
	 * so it follows {@see Prado::getLogger()}, and a null {@see getCategory Category}.
	 * @param bool $create create the singleton when it does not exist; default true.
	 * @return ?TPsrLogger the singleton adapter; null when it does not exist and `$create` is false.
	 */
	public static function singleton(bool $create = true): ?TPsrLogger
	{
		if (self::$_singleton === null && $create) {
			self::$_singleton = new static();
		}
		return self::$_singleton;
	}

	/**
	 * Replaces the singleton adapter.
	 * @param ?TPsrLogger $instance the singleton adapter; null discards it so {@see singleton()} creates a new one.
	 */
	public static function setSingleton(?TPsrLogger $instance): void
	{
		self::$_singleton = $instance;
	}

	/**
	 * @return TLogger the receiving logger; default {@see Prado::getLogger()}.
	 */
	public function getLogger(): TLogger
	{
		return $this->_logger ?? Prado::getLogger();
	}

	/**
	 * @param ?TLogger $value the receiving logger; null for {@see Prado::getLogger()}.
	 * @return static The current object.
	 */
	public function setLogger(?TLogger $value): static
	{
		$this->_logger = $value;
		return $this;
	}

	/**
	 * @return ?string the default category; null for the calling class.
	 */
	public function getCategory(): ?string
	{
		return $this->_category;
	}

	/**
	 * @param ?string $value the default category; null for the calling class.
	 * @return static The current object.
	 */
	public function setCategory(?string $value): static
	{
		$this->_category = $value;
		return $this;
	}

	/**
	 * Logs a message at a PSR-3 or TLogger level.
	 * The `CONTEXT_*` keys are decoded into the entry. An entry carrying `time`,
	 * `memory`, `pid`, or `traces` is merged as is; otherwise {@see TLogger::log()}
	 * records the current time, memory, process, and traces.
	 * @param int|string $level a PSR-3 level name or a TLogger level.
	 * @param string|\Stringable $message the message with optional `{key}` placeholders.
	 * @param array $context the placeholder values and `CONTEXT_*` keys.
	 * @throws InvalidArgumentException when the level is unknown.
	 */
	public function log($level, string|\Stringable $message, array $context = []): void
	{
		$pradoLevel = static::toPradoLevel($context[self::CONTEXT_LEVEL] ?? $level);
		$category = (string) ($context[self::CONTEXT_CATEGORY] ?? $this->_category ?? $this->resolveCategory());
		$control = $this->decodeControl($context[self::CONTEXT_CONTROL] ?? null);
		$token = $this->decodeMessage(static::interpolate((string) $message, $context), $context[self::CONTEXT_EXCEPTION] ?? null);
		$logger = $this->getLogger();

		if (!$this->hasEntryContext($context)) {
			$logger->log($token, $pradoLevel, $category, $control);
			return;
		}
		$time = $context[self::CONTEXT_TIME] ?? null;
		$memory = $context[self::CONTEXT_MEMORY] ?? null;
		$pid = $context[self::CONTEXT_PID] ?? null;
		$traces = $context[self::CONTEXT_TRACES] ?? null;
		$logger->mergeLogs([[
			$token,
			$pradoLevel,
			$category,
			is_int($time) || is_float($time) ? (float) $time : microtime(true),
			is_int($memory) ? $memory : memory_get_usage(),
			$control,
			is_array($traces) ? $traces : null,
			is_int($pid) ? $pid : getmypid(),
		]]);
	}

	/**
	 * @param array $context the PSR-3 context.
	 * @return bool whether the context carries entry fields that {@see TLogger::log()} would otherwise generate.
	 */
	protected function hasEntryContext(array $context): bool
	{
		return isset($context[self::CONTEXT_TIME]) || isset($context[self::CONTEXT_MEMORY])
			|| isset($context[self::CONTEXT_PID]) || isset($context[self::CONTEXT_TRACES]);
	}

	/**
	 * @param mixed $control the `control` context value.
	 * @return ?string the control client ID; null for other types.
	 */
	protected function decodeControl(mixed $control): ?string
	{
		if ($control instanceof TControl) {
			return $control->getClientID();
		}
		return is_string($control) ? $control : null;
	}

	/**
	 * Combines the message and the `exception` context value into the log token.
	 * @param string $message the interpolated message.
	 * @param mixed $exception the `exception` context value.
	 * @return string|\Throwable the exception when the message is its `getMessage()`; otherwise the
	 *   message with the exception appended on a new line; the message when there is no exception.
	 */
	protected function decodeMessage(string $message, mixed $exception): string|\Throwable
	{
		if (!($exception instanceof \Throwable)) {
			return $message;
		}
		if ($message === $exception->getMessage()) {
			return $exception;
		}
		return $message . "\n" . $exception;
	}

	/**
	 * Resolves the default category to the class calling the logger.
	 * @return string the calling class; this class when there is no calling class.
	 */
	protected function resolveCategory(): string
	{
		foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 8) as $frame) {
			$class = $frame['class'] ?? null;
			if ($class === null) {
				return static::class;
			}
			if (!is_a($class, self::class, true)) {
				return $class;
			}
		}
		return static::class;
	}

	/**
	 * Converts a PSR-3 level name or TLogger level to a TLogger level.
	 * @param mixed $level a PSR-3 level name or a TLogger level.
	 * @throws InvalidArgumentException when the level is unknown.
	 * @return int the TLogger level.
	 */
	public static function toPradoLevel(mixed $level): int
	{
		if (is_int($level)) {
			if (isset(static::$_pradoLevels[$level]) || ($level & TLogger::PROFILE) === TLogger::PROFILE) {
				return $level;
			}
			throw new InvalidArgumentException("Unknown TLogger level '{$level}'.");
		}
		if (is_string($level) || $level instanceof \Stringable) {
			$name = strtolower(trim((string) $level));
			if (isset(static::$_psrLevels[$name])) {
				return static::$_psrLevels[$name];
			}
		}
		throw new InvalidArgumentException("Unknown PSR-3 level '" . (is_scalar($level) ? $level : get_debug_type($level)) . "'.");
	}

	/**
	 * Converts a TLogger level to a PSR-3 level name.
	 * @param int $level the TLogger level.
	 * @return string the PSR-3 level name; `debug` for the profile levels and unknown levels.
	 */
	public static function toPsrLevel(int $level): string
	{
		return static::$_pradoLevels[$level] ?? LogLevel::DEBUG;
	}

	/**
	 * Replaces `{key}` placeholders in a message with the matching context values.
	 * Null and scalar values are cast to string. {@see \DateTimeInterface} values use
	 * the ATOM format. Arrays are JSON encoded. Other objects are named by class.
	 * @param string $message the message with placeholders.
	 * @param array $context the placeholder values.
	 * @return string the message with placeholders replaced.
	 */
	public static function interpolate(string $message, array $context): string
	{
		if (!str_contains($message, '{')) {
			return $message;
		}
		$replace = [];
		foreach ($context as $key => $value) {
			if ($value === null || is_scalar($value) || $value instanceof \Stringable) {
				$replace['{' . $key . '}'] = (string) $value;
			} elseif ($value instanceof \DateTimeInterface) {
				$replace['{' . $key . '}'] = $value->format(\DateTimeInterface::ATOM);
			} elseif (is_array($value)) {
				$replace['{' . $key . '}'] = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
			} elseif (is_object($value)) {
				$replace['{' . $key . '}'] = '[object ' . $value::class . ']';
			}
		}
		return strtr($message, $replace);
	}
}
