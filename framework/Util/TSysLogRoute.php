<?php
/**
 * TSysLogRoute class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TLogException;
use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TSysLogRoute class.
 *
 * Sends the log to the syslog.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.0
 * @link https://www.php.net/manual/en/function.openlog.php
 * @link https://www.php.net/manual/en/function.syslog.php
 */
class TSysLogRoute extends TLogRoute
{
	/**
	 * @var false|string The Prefix for openlog()
	 */
	private string|false $_sysLogPrefix = false;

	/**
	 * @var ?int The flags for openlog(), default null for `LOG_ODELAY | LOG_PID`
	 */
	private ?int $_sysLogFlags = null;

	/**
	 * @var int The facility for openlog().
	 */
	private int $_facility = LOG_USER;

	/**
	 * @param array $logs list of log messages
	 * @param bool $final is the final flush
	 * @param array $meta the meta data for the logs.
	 * @throws TLogException When failing to write to syslog.
	 */
	protected function processLogs(array $logs, bool $final, array $meta)
	{
		openlog($this->getSysLogPrefix(), $this->getSysLogFlags(), $this->getFacility());
		foreach ($logs as $log) {
			if (syslog($this->translateLogLevel($log[TLogger::LOG_LEVEL]), $this->formatLogMessage($log)) === false) {
				throw new TLogException('syslogroute_log_failed');
			}
		}
		closelog();
	}

	/**
	 * Translates a PRADO log level attribute into one understood by syslog
	 * @param int $level prado log level
	 * @return int syslog priority
	 */
	protected static function translateLogLevel($level)
	{
		switch ($level) {
			case TLogger::PROFILE:
			case TLogger::PROFILE_BEGIN:
			case TLogger::PROFILE_END:
			case TLogger::DEBUG:
				return LOG_DEBUG;
			case TLogger::INFO:
				return LOG_INFO;
			case TLogger::NOTICE:
				return LOG_NOTICE;
			case TLogger::WARNING:
				return LOG_WARNING;
			case TLogger::ERROR:
				return LOG_ERR;
			case TLogger::ALERT:
				return LOG_ALERT;
			case TLogger::FATAL:
				return LOG_CRIT;
			default:
				return LOG_INFO;
		}
	}

	/**
	 * @return string The prefix for syslog. Defaults to false
	 */
	public function getSysLogPrefix(): string|false
	{
		return $this->_sysLogPrefix;
	}

	/**
	 * @param string $value the prefix for the syslog, via openlog
	 */
	public function setSysLogPrefix($value)
	{
		$value = TPropertyValue::ensureString($value);
		if ($value === '') {
			$value = false;
		}
		$this->_sysLogPrefix = $value;
	}

	/**
	 * @return int The options for syslog. Defaults to LOG_ODELAY | LOG_PID
	 */
	public function getSysLogFlags()
	{
		return ($this->_sysLogFlags !== null) ? $this->_sysLogFlags : LOG_ODELAY | LOG_PID;
	}

	/**
	 * This sets the `openlog` flags.  It can be an integer, a string or an array of strings.
	 * As a string, the delimiters are ',' and '|' acting identically.
	 *
	 * By setting to null, this will default to `LOG_ODELAY | LOG_PID`.
	 * @param null|int|string|string[] $value the options for syslog
	 * @return static The current object.
	 * @throw TConfigurationException When the Flags are not valid.
	 */
	public function setSysLogFlags($value): static
	{
		static $_flagsMap = [
			'LOG_CONS' => LOG_CONS, // Errors to console
			'LOG_NDELAY' => LOG_NDELAY, // open immediately
			'LOG_ODELAY' => LOG_ODELAY, // delay opening until a log
			'LOG_PERROR' => LOG_PERROR, // Print to STDERR as well.
			'LOG_PID' => LOG_PID, // include ProcessID
		];

		if ($value === null || is_int($value)) {
			$invalidFlags = ~array_reduce($_flagsMap, function ($flags, $flag) {
				return $flags | $flag;
			}, 0);
			if ($invalidFlags & ((int) $value)) {
				throw new TConfigurationException('syslogroute_bad_flags', '0x' . dechex($value));
			}
			$this->_sysLogFlags = $value;
		} else {
			if (!is_array($value)) {
				$value = preg_split('/[|,]/', strtoupper($value));
			} else {
				$value = array_map('strtoupper', $value);
			}
			$options = array_map('trim', $value);
			$this->_sysLogFlags = 0;
			while(count($options)) {
				$option = array_pop($options);
				if (isset($_flagsMap[$option])) {
					$this->_sysLogFlags |= $_flagsMap[$option];
				}
			}
		}
		return $this;
	}

	/**
	 * @return int The facility for syslog.
	 */
	public function getFacility(): int
	{
		return $this->_facility;
	}

	/**
	 * @param int|string $value the options for syslog
	 * @return static The current object.
	 */
	public function setFacility($value): static
	{
		static $_facilitiesMap = [
			'LOG_AUTH' => LOG_AUTH,		// 0x20
			'LOG_CRON' => LOG_CRON,		// 0x48
			'LOG_DAEMON' => LOG_DAEMON,	// 0x18
			'LOG_KERN' => LOG_KERN,		// 0x00
			'LOG_LOCAL0' => LOG_LOCAL0,	// 0x80
			'LOG_LOCAL1' => LOG_LOCAL1,	// 0x88
			'LOG_LOCAL2' => LOG_LOCAL2,	// 0x90
			'LOG_LOCAL3' => LOG_LOCAL3,	// 0x98
			'LOG_LOCAL4' => LOG_LOCAL4,	// 0xa0
			'LOG_LOCAL5' => LOG_LOCAL5,	// 0xa8
			'LOG_LOCAL6' => LOG_LOCAL6,	// 0xb0
			'LOG_LOCAL7' => LOG_LOCAL7,	// 0xb8
			'LOG_LPR' => LOG_LPR,		// 0x30
			'LOG_MAIL' => LOG_MAIL,		// 0x10
			'LOG_NEWS' => LOG_NEWS, 	// 0x38
			'LOG_SYSLOG' => LOG_SYSLOG, // 0x28
			'LOG_USER' => LOG_USER, 	// 0x08
			'LOG_UUCP' => LOG_UUCP, 	// 0x40
		];
		if (defined('LOG_AUTHPRIV')) {
			$_facilitiesMap['LOG_AUTH'] = LOG_AUTHPRIV;
		}

		if (is_int($value)) {
			if (defined('LOG_AUTHPRIV') && $value === LOG_AUTH) {
				$value = LOG_AUTHPRIV;
			}

			if (array_search($value, $_facilitiesMap) === false) {
				throw new TConfigurationException('syslogroute_bad_facility', '0x' . dechex($value));
			}
		} else {
			$value = trim(strtoupper($value));
			if (isset($_facilitiesMap[$value])) {
				$value = $_facilitiesMap[$value];
			} else {
				throw new TConfigurationException('syslogroute_bad_facility', '0x' . dechex($value));
			}
		}
		$this->_facility = $value;

		return $this;
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

		$prefix = $this->getLogPrefix($log);

		return $prefix . '[' . static::getLevelName($log[TLogger::LOG_LEVEL]) . '][' . $log[TLogger::LOG_CATEGORY] . '] ' . $log[TLogger::LOG_MESSAGE];
	}
}
