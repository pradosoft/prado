<?php

/**
 * TDbEmailSenderTask class file.
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
 * TDbEmailSenderTask class.
 *
 * TDbEmailSenderTask sends the emails queued by a {@see TDbEmailModule} whose
 * OfflineEmail is true.  ModuleId names the module.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TDbEmailSenderTask extends TCronTask
{
	/** @var int the maximum number of emails per run; 0 is unlimited. */
	private $_limit = 0;

	/**
	 * Sends the pending emails.
	 * @param TCronModule $cron the cron module running the task.
	 * @throws TInvalidDataValueException when ModuleId does not name a TDbEmailModule.
	 */
	public function execute($cron)
	{
		$emailer = $this->getApplication()->getModule((string) $this->getModuleId());
		if (!($emailer instanceof TDbEmailModule)) {
			throw new TInvalidDataValueException('dbemailsendertask_moduleid_invalid', $this->getModuleId());
		}
		$sent = $emailer->sendPendingEmails($this->getLimit());
		if (is_object($cron) && $cron->asa(TCronModule::SHELL_LOG_BEHAVIOR)) {
			$cron->getOutputWriter()->writeLine("Sent {$sent} pending emails", TShellWriter::GREEN);
		}
	}

	/**
	 * @return int the maximum number of emails per run; 0 is unlimited. Defaults to 0.
	 */
	public function getLimit()
	{
		return $this->_limit;
	}

	/**
	 * @param int $value the maximum number of emails per run; 0 is unlimited.
	 */
	public function setLimit($value)
	{
		$this->_limit = max(0, TPropertyValue::ensureInteger($value));
	}
}
