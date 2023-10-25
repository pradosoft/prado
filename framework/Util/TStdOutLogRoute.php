<?php
/**
 * TStdOutLogRoute class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TLogException;
use Prado\IO\TStdOutWriter;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Shell\Actions\TWebServerAction;
use Prado\Shell\TShellWriter;

/**
 * TStdOutLogRoute class.
 *
 * This sends the log to STDOUT and will corrupt web server log files.  This is useful
 * in presenting PRADO logs in the PHP (Test and Development) Web Server output.
 *
 * The TStdOutLogRoute can be turned off for all but the built-in Test web server
 * with the configuration property {@see \Prado\Util\TStdOutLogRoute::getOnlyDevServer}
 * set to true.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.0
 */
class TStdOutLogRoute extends TLogRoute
{
	/** @var bool Enable this log route only when running the built-in PHP web server, default false */
	private bool $_onlyDevServer = false;

	/**
	 * This returns if the route is enabled.  When OnlyDevServer is true, then this route
	 * will only enable when running a page in the PHP Web Server.
	 * @return bool Is the route enabled, default true.
	 */
	public function getEnabled(): bool
	{
		$enabled = parent::getEnabled();

		if ($this->getOnlyDevServer()) {
			$enabled &= (int) getenv(TWebServerAction::DEV_WEBSERVER_ENV);
		}

		return $enabled;
	}

	/**
	 * @param array $logs list of log messages
	 * @param bool $final is the final flush
	 * @param array $meta the meta data for the logs.
	 * @throws TLogException When failing to write to syslog.
	 */
	protected function processLogs(array $logs, bool $final, array $meta)
	{
		$writer = new TShellWriter(new TStdOutWriter());

		foreach ($logs as $log) {
			$writer->write('[' . static::getLevelName($log[TLogger::LOG_LEVEL]) . ']', $this->levelColor($log[TLogger::LOG_LEVEL]));
			$writer->writeLine($this->formatLogMessage($log));
		}
		$writer->flush();
	}

	/**
	 * Translates a PRADO log level attribute into one understood by syslog
	 * @param int $level prado log level
	 * @return int syslog priority
	 */
	protected static function levelColor($level)
	{
		switch ($level) {
			case TLogger::PROFILE:
			case TLogger::PROFILE_BEGIN:
			case TLogger::PROFILE_END:
				return TShellWriter::BG_LIGHT_GREEN;
			case TLogger::DEBUG:
				return TShellWriter::BG_GREEN;
			case TLogger::INFO:
				return null;
			case TLogger::NOTICE:
				return TShellWriter::BG_BLUE;
			case TLogger::WARNING:
				return TShellWriter::BG_CYAN;
			case TLogger::ERROR:
				return TShellWriter::BG_LIGHT_MAGENTA;
			case TLogger::ALERT:
				return TShellWriter::BG_MAGENTA;
			case TLogger::FATAL:
				return TShellWriter::BG_RED;
			default:
				return TShellWriter::BG_GREEN;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function formatLogMessage(array $log): string
	{
		if (!is_string($log[TLogger::LOG_MESSAGE])) {
			if ($log[TLogger::LOG_MESSAGE] instanceof \Exception || $log[TLogger::LOG_MESSAGE] instanceof \Throwable) {
				$log[TLogger::LOG_MESSAGE] = (string) $log[TLogger::LOG_MESSAGE];
			} else {
				$log[TLogger::LOG_MESSAGE] = \Prado\Util\TVarDumper::dump($log[TLogger::LOG_MESSAGE]);
			}
		}

		return '[' . $log[TLogger::LOG_CATEGORY] . '] ' . $log[TLogger::LOG_MESSAGE];
	}

	/**
	 * @return bool If the Route is only enabled when operating in the PHP Test Web
	 *   Server, default false.
	 */
	public function getOnlyDevServer(): bool
	{
		return $this->_onlyDevServer;
	}

	/**
	 *
	 * @param bool|string $value Only enable the route when running in the PHP Test Web Server.
	 * @return static The current object.
	 */
	public function setOnlyDevServer($value): static
	{
		$this->_onlyDevServer = TPropertyValue::ensureBoolean($value);

		return $this;
	}
}
