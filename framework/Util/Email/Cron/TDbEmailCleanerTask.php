<?php

/**
 * TDbEmailCleanerTask class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Email\Cron;

use Prado\Shell\TShellWriter;
use Prado\TPropertyValue;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Util\Cron\TCronModule;

/**
 * TDbCronCleanLogTask class.
 *
 * TDbCronCleanLogTask Cleans the cron log of old entries older than the
 * age of {@link getTimePeriod} seconds.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TDbEmailCleanerTask extends TCronTask
{
	/**
	 * @var int the time period in seconds of valid log items, default 28 days.
	 */
	private $_timeperiod = 2419200; //86400 seconds/day * 28 days

	/**
	 * @var null|string the task name of cron logs to delete, _ is wild card, % is wild string.
	 */
	private $_taskname;

	/**
	 * @var null|string the username of cron logs to delete, _ is wild card, % is wild string.
	 */
	private $_username;

	/**
	 * This clears the log of the TDBCronModule specified by the ModuleId,
	 * or if none specified, then the cron executing this task.
	 * @param TDbCronModule $cron
	 */
	public function execute($cron)
	{
		if ($mid = $this->getModuleId()) {
			$cron = $this->getApplication()->getModule($mid);
			if ($cron === null) {
				throw new TInvalidDataValueException('dbcronclean_moduleid_is_null', $mid);
			}
		}
		if (is_object($cron) && $cron instanceof \Prado\Util\Cron\TDbCronModule) {
			$count = $cron->clearCronLog($this->getTimePeriod(), $this->getTaskName(), $this->getUsername());

			if ($cron->asa(TCronModule::SHELL_LOG_BEHAVIOR)) {
				$cron->getOutputWriter()->writeLine("Cleared {$count} Cron Task Logs", TShellWriter::GREEN);
			}
		} elseif (is_object($cron) && $cron->asa(TCronModule::SHELL_LOG_BEHAVIOR)) {
			/** @var TCronModule $cron */
			$cron->getOutputWriter()->writeLine("No DB Cron Module to clean", TShellWriter::RED);
		}
	}

	/**
	 * @return int number of seconds, before which cron logs are to be deleted
	 */
	public function getTimePeriod()
	{
		return $this->_timeperiod;
	}

	/**
	 *
	 * @param int $timeperiod number of seconds, before which cron logs are to be deleted
	 */
	public function setTimePeriod($timeperiod)
	{
		$this->_timeperiod = TPropertyValue::ensureSeconds($timeperiod);
	}

	/**
	 * _ is a wild card and a % is a wild string.
	 * @return null|string the name of tasks in the log to be cleared.
	 */
	public function getTaskName()
	{
		return $this->_taskname;
	}

	/**
	 *
	 * @param null|string $name the name of tasks in the log to be cleared
	 */
	public function setTaskName($name)
	{
		$this->_taskname = !$name ? null : TPropertyValue::ensureString($name);
	}

	/**
	 * _ is a wild card and a % is a wild string.
	 * @return null|string the username of tasks in the log to be cleared.
	 */
	public function getUsername()
	{
		return $this->_username;
	}

	/**
	 *
	 * @param null|string $username the username of tasks in the log to be cleared
	 */
	public function setUsername($username)
	{
		$this->_username = !$username ? null : TPropertyValue::ensureString($username);
	}
}
