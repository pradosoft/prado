<?php

/**
 * TDbEmailSenderTask class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Email\Cron;

use Prado\Shell\TShellWriter;
use Prado\TPropertyValue;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Util\Email\TDbEmailerModule;

/**
 * TDbEmailSenderTask class.
 *
 * TDbEmailSenderTask Sends the pending unsent emails via cron.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TDbEmailSenderTask extends TCronTask
{
	/**
	 * This clears the log of the TDBCronModule specified by the ModuleId,
	 * or if none specified, then the cron executing this task.
	 * @param TDbCronModule $cron
	 */
	public function execute($cron)
	{
		$emailer = null;
		if ($mid = $this->getModuleId()) {
			$emailer = $this->getApplication()->getModule($mid);
			if ($emailer === null) {
				throw new TInvalidDataValueException('dbemailsender_moduleid_is_null', $mid);
			}
		}
		if (is_object($emailer) && $emailer instanceof TDbEmailerModule) {
			$sent = $emailer->sendPendingEmails();

			if (($b = $cron->asa(TCronModule::SHELL_LOG_BEHAVIOR)) && $b->getEnabled()) {
				$cron->getOutputWriter()->write("Send Email From {$this->_email->getFrom()} to {$this->_email->getTo()} subject '{$this->_email->getSubject()}' ");
				$cron->getOutputWriter()->writeLine($sent ? 'Sucess' : 'Failed', $sent ? TShellWriter::GREEN : TShellWriter::RED);
			}
		} elseif (is_object($emailer) && $cron->asa(TCronModule::SHELL_LOG_BEHAVIOR)) {
			$cron->getOutputWriter()->writeLine("No TDbEmailerModule to process pending emails.", TShellWriter::RED);
		}
	}
}
