<?php

/**
 * TDbEmailCleanerTask class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Email\Cron;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Shell\TShellWriter;
use Prado\TPropertyValue;
use Prado\Util\Cron\TCronModule;
use Prado\Util\Cron\TCronTask;
use Prado\Util\Email\TDbEmailModule;

/**
 * TDbEmailCleanerTask class.
 *
 * TDbEmailCleanerTask deletes the emails logged by a {@see TDbEmailModule} that are
 * older than {@see setTimePeriod TimePeriod}.  Pending emails are kept unless
 * {@see setStatus Status} is "pending".  ModuleId names the module.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TDbEmailCleanerTask extends TCronTask
{
	/** @var int the age, in seconds, of the emails to delete; default 90 days. */
	private $_timePeriod = 7776000;

	/** @var ?string the status of the emails to delete; null deletes all except pending. */
	private $_status;

	/**
	 * Deletes the old logged emails.
	 * @param TCronModule $cron the cron module running the task.
	 * @throws TInvalidDataValueException when ModuleId does not name a TDbEmailModule.
	 */
	public function execute($cron)
	{
		$emailer = $this->getApplication()->getModule((string) $this->getModuleId());
		if (!($emailer instanceof TDbEmailModule)) {
			throw new TInvalidDataValueException('dbemailcleanertask_moduleid_invalid', $this->getModuleId());
		}
		$count = $emailer->clearEmailLog($this->getTimePeriod(), $this->getStatus());
		if (is_object($cron) && $cron->asa(TCronModule::SHELL_LOG_BEHAVIOR)) {
			$cron->getOutputWriter()->writeLine("Deleted {$count} logged emails", TShellWriter::GREEN);
		}
	}

	/**
	 * @return int the age, in seconds, of the emails to delete. Defaults to 90 days.
	 */
	public function getTimePeriod()
	{
		return $this->_timePeriod;
	}

	/**
	 * @param int $value the age, in seconds, of the emails to delete.
	 */
	public function setTimePeriod($value)
	{
		$this->_timePeriod = max(0, TPropertyValue::ensureInteger($value));
	}

	/**
	 * @return ?string the status of the emails to delete; null deletes all except pending.
	 */
	public function getStatus()
	{
		return $this->_status;
	}

	/**
	 * @param ?string $value the status of the emails to delete; empty deletes all except pending.
	 */
	public function setStatus($value)
	{
		$this->_status = ($value === null || $value === '') ? null : TPropertyValue::ensureString($value);
	}
}
