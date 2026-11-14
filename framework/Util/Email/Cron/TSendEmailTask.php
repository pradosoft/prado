<?php

/**
 * TSendEmailTask class file.
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
use Prado\Util\Cron\TCronTask;
use Prado\Util\Email\TEmailModule;
use Prado\Util\Cron\TDbCronManager;

/**
 * TSendEmailTask class.
 *
 * TSendEmailTask sends a single email from cron.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TSendEmailTask extends TCronTask
{
	/**
	 * @var \Prado\Util\Email\TEmail the email to be sent.
	 */
	private $_email;

	/**
	 * @param \Prado\Util\Email\TEmail $email the email to send on execute.
	 */
	public function __construct($email = null)
	{
		$this->_email = $email ? clone $email : null;
		$this->setName('sendmail-' . $email->getMessageId());
		$this->setUsername($email->getUsername());
		if ($emailer = $email->getEmailerModule()) {
			$this->setModuleId($emailer->getId());
		}
		$this->setAutoRemove(true);
	}

	/**
	 * This sends one email as a cron task.
	 * @param TDbCronManager $cron
	 */
	public function execute($cron)
	{
		$emailer = null;
		if ($mid = $this->getModuleId()) {
			$emailer = $this->getApplication()->getModule($mid);
			if ($emailer === null) {
				throw new TInvalidDataValueException('sendemailtask_moduleid_is_null', $mid);
			}
		}
		if (is_object($emailer) && $emailer instanceof TEmailModule) {
			$sent = $emailer->sendEmail($this->_email, $this->_email->getCustomData(), true);

			if (($b = $cron->asa(TCronModule::SHELL_LOG_BEHAVIOR)) && $b->getEnabled()) {
				$cron->getOutputWriter()->write("Send Email From {$this->_email->getFrom()} to {$this->_email->getTo()} subject '{$this->_email->getSubject()}' ");
				$cron->getOutputWriter()->writeLine($sent ? 'Sucess' : 'Failed', $sent ? TShellWriter::GREEN : TShellWriter::RED);
			}
		} elseif (is_object($emailer) && $cron->asa(TCronModule::SHELL_LOG_BEHAVIOR)) {
			$cron->getOutputWriter()->writeLine("No TEmailModule to send Email.", TShellWriter::RED);
		}
	}
}
