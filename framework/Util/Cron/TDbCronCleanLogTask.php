<?php

/**
 * TDbCronCleanLogTask class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Cron;

use Prado\Shell\TShellWriter;
use Prado\TPropertyValue;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Util\Cron\TCronModule;

/**
 * TDbCronCleanLogTask class.
 *
 * TDbCronCleanLogTask Cleans the cron log of old entries older than the
 * age of {@see getTimePeriod} seconds.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.0
 */
class TDbCronCleanLogTask extends TCronTask
{
	/**
	 * @var int the time period in seconds of valid log items, default 28 days.
	 */
	private $_timeperiod = 2419200; //86400 seconds/day * 28 days

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
			$count = $cron->clearCronLog($this->getTimePeriod());

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
		$this->_timeperiod = TPropertyValue::ensureInteger($timeperiod);
	}
}
