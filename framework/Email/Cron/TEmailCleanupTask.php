<?php

/**
 * TEmailCleanupTask class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\Cron;

use Prado\Email\TEmailStatus;
use Prado\Exceptions\TConfigurationException;
use Prado\TPropertyValue;

/**
 * TEmailCleanupTask class.
 *
 * TEmailCleanupTask deletes the old emails of the store of a
 * {@see \Prado\Email\TEmailModule}.  The suppression list is kept.
 *
 * <code>
 * <job Name="emailcleanup" Schedule="0 3 * * *" Task="Prado\Email\Cron\TEmailCleanupTask" ModuleId="email" TimePeriod="2592000" />
 * </code>
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailCleanupTask extends TEmailTask
{
	/** @var int the age, in seconds, of the emails to delete. */
	private int $_timePeriod = 7776000;

	/** @var ?string the status of the emails to delete. */
	private ?string $_status = null;

	/**
	 * Deletes the emails older than the TimePeriod.
	 * @param \Prado\Util\Cron\TCronModule $cronModule the cron module running the task.
	 * @throws TConfigurationException when the email module has no store.
	 */
	public function execute($cronModule)
	{
		if (($store = $this->getEmailModule()->getStore()) === null) {
			throw new TConfigurationException('emailmodule_store_required');
		}
		$count = (int) $store->deleteEmails($this->getTimePeriod(), $this->getStatus());
		$this->writeLine($cronModule, "Deleted {$count} stored emails");
	}

	/**
	 * @return int the age, in seconds, of the emails to delete. Defaults to 90 days.
	 */
	public function getTimePeriod(): int
	{
		return $this->_timePeriod;
	}

	/**
	 * @param int|string $value the age, in seconds, of the emails to delete.
	 */
	public function setTimePeriod($value)
	{
		$this->_timePeriod = max(0, TPropertyValue::ensureInteger($value));
	}

	/**
	 * @return ?string the TEmailStatus of the emails to delete; null for every status except Queued and Sending.
	 */
	public function getStatus(): ?string
	{
		return $this->_status;
	}

	/**
	 * @param ?string $value the TEmailStatus of the emails to delete; empty for every status except Queued and Sending.
	 */
	public function setStatus($value)
	{
		$this->_status = ($value === null || $value === '') ? null : TPropertyValue::ensureEnum($value, TEmailStatus::class);
	}
}
