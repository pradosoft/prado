<?php

/**
 * TEmailQueueTask class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\Cron;

use Prado\TPropertyValue;

/**
 * TEmailQueueTask class.
 *
 * TEmailQueueTask delivers the queued emails of the store of a
 * {@see \Prado\Email\TEmailModule}: the emails of the Queued send mode, the ones a
 * deferred send did not reach, and the retries of transient failures.
 *
 * <code>
 * <job Name="emailqueue" Schedule="* * * * *" Task="Prado\Email\Cron\TEmailQueueTask" ModuleId="email" Limit="200" />
 * </code>
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailQueueTask extends TEmailTask
{
	/** @var int the most emails per run. */
	private int $_limit = 0;

	/**
	 * Delivers the queued emails that are due and closes the transport.
	 * @param \Prado\Util\Cron\TCronModule $cronModule the cron module running the task.
	 */
	public function execute($cronModule)
	{
		$module = $this->getEmailModule();
		try {
			$sent = $module->sendQueued($this->getLimit());
		} finally {
			$module->close();
		}
		$this->writeLine($cronModule, "Sent {$sent} queued emails");
	}

	/**
	 * @return int the most emails per run; 0 for all that are due. Defaults to 0.
	 */
	public function getLimit(): int
	{
		return $this->_limit;
	}

	/**
	 * @param int|string $value the most emails per run; 0 for all that are due.
	 */
	public function setLimit($value)
	{
		$this->_limit = max(0, TPropertyValue::ensureInteger($value));
	}
}
