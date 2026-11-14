<?php

/**
 * TDbCronCleanLogTask class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

use DateInterval;
use Prado\Shell\TShellWriter;
use Prado\TPropertyValue;
use Prado\Exceptions\TInvalidDataValueException;

/**
 * TDbCronCleanLogTask class.
 *
 * TDbCronCleanLogTask Cleans the cron log of old entries older than the
 * age of {@see getTimePeriod}.  The {@see setTaskName TaskName} and
 * {@see setUsername Username} filters limit the cleared entries, with `_` as a
 * wild card and `%` as a wild string.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TDbCronCleanLogTask extends TCronTask
{
	/**
	 * @var int|string the time period of valid log items: seconds or a \DateInterval
	 * specification, default 28 days.
	 */
	private $_timeperiod = 2419200; //86400 seconds/day * 28 days

	/**
	 * @var ?string the task name of cron logs to delete, _ is wild card, % is wild string.
	 */
	private $_taskname;

	/**
	 * @var ?string the username of cron logs to delete, _ is wild card, % is wild string.
	 */
	private $_username;

	/**
	 * This clears the log of the TDbCronManager specified by the ModuleId,
	 * or if none specified, then the cron executing this task.
	 * @param \Prado\Util\Cron\TCronModule|\Prado\Util\Cron\TDbCronManager $cron
	 * @throws TInvalidDataValueException when the ModuleId has no module
	 */
	public function execute($cron)
	{
		if ($mid = $this->getModuleId()) {
			$cron = $this->getApplication()->getModule($mid);
			if ($cron === null) {
				throw new TInvalidDataValueException('dbcronclean_moduleid_is_null', $mid);
			}
		}
		if ($cron instanceof \Prado\Util\Cron\TDbCronManager) {
			$count = $cron->clearCronLog($this->getTimePeriod(), $this->getTaskName(), $this->getUsername());

			if ($cron->asa(TCronModule::SHELL_LOG_BEHAVIOR)) {
				$cron->getOutputWriter()->writeLine("Cleared {$count} Cron Task Logs", TShellWriter::GREEN);
			}
		} elseif ($cron->asa(TCronModule::SHELL_LOG_BEHAVIOR)) {
			/** @var TCronModule $cron */
			$cron->getOutputWriter()->writeLine("No DB Cron Module to clean", TShellWriter::RED);
		}
	}

	/**
	 * @return int|string the seconds, or \DateInterval specification, before which cron logs are to be deleted
	 */
	public function getTimePeriod()
	{
		return $this->_timeperiod;
	}

	/**
	 * Numeric values are seconds.  Other strings are an ISO 8601 duration, eg. "P1Y6M3DT12H20M30S",
	 * or a relative date string, eg. "1 year 6 months".
	 * @param int|string $timeperiod the period before which cron logs are to be deleted
	 * @throws TInvalidDataValueException when the string is not a \DateInterval specification
	 */
	public function setTimePeriod($timeperiod)
	{
		$timeperiod = trim((string) $timeperiod);
		if ($timeperiod === '' || is_numeric($timeperiod)) {
			$this->_timeperiod = TPropertyValue::ensureInteger($timeperiod);
			return;
		}
		try {
			$valid = str_starts_with($timeperiod, 'P') ? new DateInterval($timeperiod) : null;
		} catch (\Exception $e) {
			$valid = null;
		}
		if ($valid === null) {
			try {
				@DateInterval::createFromDateString($timeperiod) ?: new DateInterval($timeperiod);
			} catch (\Exception $e) {
				throw new TInvalidDataValueException('dbcronclean_timeperiod_invalid', $timeperiod);
			}
		}
		$this->_timeperiod = $timeperiod;
	}

	/**
	 * _ is a wild card and a % is a wild string.
	 * @return ?string the name of tasks in the log to be cleared.
	 * @since 4.4.0
	 */
	public function getTaskName()
	{
		return $this->_taskname;
	}

	/**
	 * _ is a wild card and a % is a wild string.
	 * @param ?string $name the name of tasks in the log to be cleared
	 * @since 4.4.0
	 */
	public function setTaskName($name)
	{
		$this->_taskname = !$name ? null : TPropertyValue::ensureString($name);
	}

	/**
	 * _ is a wild card and a % is a wild string.
	 * @return ?string the username of tasks in the log to be cleared.
	 * @since 4.4.0
	 */
	public function getUsername()
	{
		return $this->_username;
	}

	/**
	 * _ is a wild card and a % is a wild string.
	 * @param ?string $username the username of tasks in the log to be cleared
	 * @since 4.4.0
	 */
	public function setUsername($username)
	{
		$this->_username = !$username ? null : TPropertyValue::ensureString($username);
	}
}
