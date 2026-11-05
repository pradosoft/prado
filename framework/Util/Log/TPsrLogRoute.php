<?php

/**
 * TPsrLogRoute class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Log;

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Util\TVarDumper;
use Psr\Log\LoggerInterface;

/**
 * TPsrLogRoute class.
 *
 * TPsrLogRoute forwards the application log to an external PSR-3 {@see LoggerInterface}
 * such as Monolog. Each entry is sent with {@see LoggerInterface::log()} at the PSR-3
 * level from {@see TPsrLogger::toPsrLevel()}.
 *
 * {@see setLogger Logger} accepts a logger instance, the ID of an application module
 * that implements `LoggerInterface`, or a class name with a no-argument constructor.
 * A module ID or class name is resolved on first use.
 *
 * Configuration as a route inside {@see TLogRouter}:
 * ```xml
 * <module id="psrlog" class="App\Logging\MonologModule" />
 * <module id="log" class="Prado\Util\Log\TLogRouter">
 *   <route class="Prado\Util\Log\TPsrLogRoute" Logger="psrlog" Levels="Warning, Error, Fatal" />
 * </module>
 * ```
 *
 * ```php
 * return [
 *     'modules' => [
 *         'log' => [
 *             'class' => 'Prado\Util\Log\TLogRouter',
 *             'routes' => [
 *                 [
 *                     'class' => 'Prado\Util\Log\TPsrLogRoute',
 *                     'properties' => ['Logger' => 'psrlog', 'Levels' => 'Warning, Error, Fatal'],
 *                 ],
 *             ],
 *         ],
 *     ],
 * ];
 * ```
 *
 * Programmatic use:
 * ```php
 * $route = new TPsrLogRoute();
 * $route->setLogger(new \Monolog\Logger('prado'));
 * $app->getModule('log')->addRoute($route);
 * ```
 *
 * The PSR-3 message is the raw log message. {@see setFormatMessage FormatMessage}
 * sends {@see TLogRoute::formatLogMessage()} output instead. The context carries the
 * remaining log fields:
 *
 * | Key | Value |
 * |---|---|
 * | `category` | log category |
 * | `level` | TLogger level |
 * | `time` | timestamp from `microtime(true)` |
 * | `memory` | memory usage in bytes |
 * | `pid` | process ID |
 * | `prefix` | {@see TLogRoute::getLogPrefix()} |
 * | `control` | control client ID, when set |
 * | `traces` | stack traces, when {@see TLogger::setTraceLevel} is set |
 * | `delta`, `total` | timing computed by {@see TLogRoute::filterLogs()} |
 * | `exception` | the {@see \Throwable} when the log message is an exception |
 *
 * {@see TPsrLogger} is the reverse adapter and is rejected as the `Logger` because it
 * would write the entries back into the same {@see TLogger}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TPsrLogRoute extends TLogRoute
{
	/**
	 * @var null|LoggerInterface|string the PSR-3 logger, or its module ID or class name.
	 */
	private LoggerInterface|string|null $_logger = null;

	/**
	 * @var bool send the formatted log message; default false.
	 */
	private bool $_formatMessage = false;

	/**
	 * Resolves a module ID or class name on first use.
	 * @throws TConfigurationException when the logger is missing or invalid.
	 * @return LoggerInterface the PSR-3 logger.
	 */
	public function getLogger(): LoggerInterface
	{
		if ($this->_logger === null) {
			throw new TConfigurationException('psrlogroute_logger_required');
		}
		if (is_string($this->_logger)) {
			$this->_logger = $this->resolveLogger($this->_logger);
		}
		return $this->_logger;
	}

	/**
	 * @param null|LoggerInterface|string $value the PSR-3 logger, or its module ID or class name.
	 * @throws TConfigurationException when the logger is a {@see TPsrLogger}.
	 * @return static The current object.
	 */
	public function setLogger(LoggerInterface|string|null $value): static
	{
		if ($value instanceof LoggerInterface) {
			$this->ensureNotRecursive($value);
		}
		$this->_logger = $value;
		return $this;
	}

	/**
	 * @return bool send the formatted log message; default false.
	 */
	public function getFormatMessage(): bool
	{
		return $this->_formatMessage;
	}

	/**
	 * @param mixed $value send {@see TLogRoute::formatLogMessage()} output as the PSR-3 message.
	 * @return static The current object.
	 */
	public function setFormatMessage(mixed $value): static
	{
		$this->_formatMessage = TPropertyValue::ensureBoolean($value);
		return $this;
	}

	/**
	 * Resolves a module ID, then a class name, to a PSR-3 logger.
	 * @param string $id the module ID or class name.
	 * @throws TConfigurationException when neither resolves to a {@see LoggerInterface}.
	 * @return LoggerInterface the PSR-3 logger.
	 */
	protected function resolveLogger(string $id): LoggerInterface
	{
		$logger = $this->getApplication()?->getModule($id);
		if ($logger === null) {
			$class = Prado::usingClass($id);
			if (is_string($class)) {
				$logger = new $class();
			}
		}
		if (!($logger instanceof LoggerInterface)) {
			throw new TConfigurationException('psrlogroute_logger_invalid', $id);
		}
		$this->ensureNotRecursive($logger);
		return $logger;
	}

	/**
	 * @param LoggerInterface $logger the PSR-3 logger.
	 * @throws TConfigurationException when the logger is a {@see TPsrLogger}.
	 */
	protected function ensureNotRecursive(LoggerInterface $logger): void
	{
		if ($logger instanceof TPsrLogger) {
			throw new TConfigurationException('psrlogroute_logger_recursive');
		}
	}

	/**
	 * Sends each log entry to the PSR-3 logger.
	 * @param array $logs list of log messages
	 * @param bool $final is the final flush
	 * @param array $meta the meta data for the logs.
	 */
	protected function processLogs(array $logs, bool $final, array $meta)
	{
		$logger = $this->getLogger();
		foreach ($logs as $log) {
			$logger->log(TPsrLogger::toPsrLevel($log[TLogger::LOG_LEVEL]), $this->getPsrMessage($log), $this->getPsrContext($log));
		}
	}

	/**
	 * @param array $log the log entry.
	 * @return string the PSR-3 message.
	 */
	protected function getPsrMessage(array $log): string
	{
		if ($this->_formatMessage) {
			return $this->formatLogMessage($log);
		}
		$message = $log[TLogger::LOG_MESSAGE];
		if ($message instanceof \Throwable) {
			return $message->getMessage();
		}
		if (is_string($message) || $message instanceof \Stringable) {
			return (string) $message;
		}
		return TVarDumper::dump($message);
	}

	/**
	 * @param array $log the log entry.
	 * @return array the PSR-3 context of the remaining log fields.
	 */
	protected function getPsrContext(array $log): array
	{
		$context = [
			TPsrLogger::CONTEXT_CATEGORY => $log[TLogger::LOG_CATEGORY],
			TPsrLogger::CONTEXT_LEVEL => $log[TLogger::LOG_LEVEL],
			TPsrLogger::CONTEXT_TIME => $log[TLogger::LOG_TIME],
			TPsrLogger::CONTEXT_MEMORY => $log[TLogger::LOG_MEMORY],
			TPsrLogger::CONTEXT_PID => $log[TLogger::LOG_PID],
			TPsrLogger::CONTEXT_PREFIX => $this->getLogPrefix($log),
		];
		if ($log[TLogger::LOG_CONTROL] !== null) {
			$context[TPsrLogger::CONTEXT_CONTROL] = $log[TLogger::LOG_CONTROL];
		}
		if (!empty($log[TLogger::LOG_TRACES])) {
			$context[TPsrLogger::CONTEXT_TRACES] = $log[TLogger::LOG_TRACES];
		}
		if (isset($log['delta'])) {
			$context[TPsrLogger::CONTEXT_DELTA] = $log['delta'];
			$context[TPsrLogger::CONTEXT_TOTAL] = $log['total'];
		}
		if ($log[TLogger::LOG_MESSAGE] instanceof \Throwable) {
			$context[TPsrLogger::CONTEXT_EXCEPTION] = $log[TLogger::LOG_MESSAGE];
		}
		return $context;
	}
}
