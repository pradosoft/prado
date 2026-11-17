<?php

/**
 * TStdOutLogRoute class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Log;

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
 * with the configuration property {@see \Prado\Util\Log\TStdOutLogRoute::getOnlyDevServer}
 * set to true.
 *
 * TStdOutLogRoute is configured as a `<route>` element inside a
 * {@see \Prado\Util\Log\TLogRouter} module in the application configuration.
 *
 * XML configuration style:
 * ```xml
 * <modules>
 *   <module id="log" class="Prado\Util\Log\TLogRouter">
 *     <route class="Prado\Util\Log\TStdOutLogRoute" Levels="Debug, Info, Warning, Error, Fatal"
 *         OnlyDevServer="true" />
 *   </module>
 * </modules>
 * ```
 *
 * PHP configuration style:
 * ```php
 * return [
 *     'modules' => [
 *         'log' => [
 *             'class' => 'Prado\Util\Log\TLogRouter',
 *             'routes' => [
 *                 [
 *                     'class' => 'Prado\Util\Log\TStdOutLogRoute',
 *                     'properties' => [
 *                         'Levels' => 'Debug, Info, Warning, Error, Fatal',
 *                         'OnlyDevServer' => 'true',
 *                     ],
 *                 ],
 *             ],
 *         ],
 *     ],
 * ];
 * ```
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
	 * Writes the logs to STDOUT, each with a colored level badge.
	 * @param array $logs list of log messages
	 * @param bool $final is the final flush
	 * @param array $meta the meta data for the logs.
	 */
	protected function processLogs(array $logs, bool $final, array $meta)
	{
		$writer = $this->createWriter();

		foreach ($logs as $log) {
			$writer->write('[' . static::getLevelName($log[TLogger::LOG_LEVEL]) . ']', $this->levelColor($log[TLogger::LOG_LEVEL]));
			$writer->writeLine($this->formatLogMessage($log));
		}
		$writer->flush();
	}

	/**
	 * Creates the shell writer that {@see processLogs()} writes to.
	 * @return TShellWriter a shell writer over STDOUT.
	 * @since 4.4.0
	 */
	protected function createWriter(): TShellWriter
	{
		return new TShellWriter(new TStdOutWriter());
	}

	/**
	 * Translates a PRADO log level into a {@see TShellWriter} background color.
	 * @param int $level prado log level
	 * @return ?int the background color; null for INFO.
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
		$log[TLogger::LOG_MESSAGE] = $this->messageToString($log[TLogger::LOG_MESSAGE]);

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
