<?php

/**
 * TIMAPBouncedTask class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Email\Cron;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Shell\TShellWriter;
use Prado\TPropertyValue;
use Prado\Util\Cron\TCronModule;
use Prado\Util\Cron\TCronTask;
use Prado\Util\Email\Mailbox\IEmailMailbox;
use Prado\Util\Email\Mailbox\TImapMailbox;
use Prado\Util\Email\TEmailModule;

/**
 * TIMAPBouncedTask class.
 *
 * TIMAPBouncedTask reads the unseen messages of the mailbox that receives bounces,
 * passes each to {@see TEmailModule::processBounceMessage}, and marks it seen.  The
 * email module raises onBounce for each failed recipient; {@see \Prado\Util\Email\TDbEmailModule}
 * records it on the logged email.
 *
 * The mailbox is a {@see TImapMailbox} configured by {@see setDsn Dsn}:
 * <code>
 *   <job Name="bounces" Schedule="*\/15 * * * *" Task="Prado\Util\Email\Cron\TIMAPBouncedTask"
 *       ModuleId="emailer" Dsn="imaps://bounces%40example.com:secret@imap.example.com/INBOX" />
 * </code>
 * Without a ModuleId, the first TEmailModule of the application processes the bounces.
 * Use a mailbox dedicated to bounces: every unseen message is marked seen.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TIMAPBouncedTask extends TCronTask
{
	/** @var string the IMAP DSN of the bounce mailbox. */
	private $_dsn = '';

	/** @var int the maximum number of messages per run; 0 is unlimited. */
	private $_limit = 100;

	/**
	 * Processes the unseen messages of the bounce mailbox.
	 * @param TCronModule $cron the cron module running the task.
	 * @throws TInvalidDataValueException when ModuleId names no module.
	 */
	public function execute($cron)
	{
		$emailer = $this->getEmailerModule();
		$mailbox = $this->createMailbox();
		$messages = $bounces = 0;
		try {
			$mailbox->open();
			$ids = $mailbox->getUnseenMessageIds();
			if (($limit = $this->getLimit()) > 0) {
				$ids = array_slice($ids, 0, $limit);
			}
			foreach ($ids as $id) {
				$bounces += count($emailer->processBounceMessage($mailbox->fetchMessage($id)));
				$mailbox->markSeen($id);
				$messages++;
			}
		} finally {
			$mailbox->close();
		}
		if (is_object($cron) && $cron->asa(TCronModule::SHELL_LOG_BEHAVIOR)) {
			$cron->getOutputWriter()->writeLine("Processed {$messages} messages with {$bounces} bounced recipients", TShellWriter::GREEN);
		}
	}

	/**
	 * @throws TInvalidDataValueException when ModuleId names no TEmailModule.
	 * @throws TConfigurationException when the application has no TEmailModule.
	 * @return TEmailModule the module that processes the bounces.
	 */
	protected function getEmailerModule(): TEmailModule
	{
		$app = $this->getApplication();
		if ($id = $this->getModuleId()) {
			$module = $app->getModule($id);
			if (!($module instanceof TEmailModule)) {
				throw new TInvalidDataValueException('imapbouncedtask_moduleid_invalid', $id);
			}
			return $module;
		}
		foreach (array_keys($app->getModulesByType(TEmailModule::class, false)) as $id) {
			if (($module = $app->getModule($id)) instanceof TEmailModule) {
				return $module;
			}
		}
		throw new TConfigurationException('imapbouncedtask_no_emailmodule');
	}

	/**
	 * @return IEmailMailbox the bounce mailbox.
	 */
	protected function createMailbox(): IEmailMailbox
	{
		$mailbox = new TImapMailbox();
		$mailbox->setDsn($this->getDsn());
		return $mailbox;
	}

	/**
	 * @return string the IMAP DSN of the bounce mailbox.
	 */
	public function getDsn()
	{
		return $this->_dsn;
	}

	/**
	 * @param string $dsn the IMAP DSN of the bounce mailbox, e.g. "imaps://user:password@imap.example.com/INBOX".
	 */
	public function setDsn($dsn)
	{
		$this->_dsn = TPropertyValue::ensureString($dsn);
	}

	/**
	 * @return int the maximum number of messages per run; 0 is unlimited. Defaults to 100.
	 */
	public function getLimit()
	{
		return $this->_limit;
	}

	/**
	 * @param int $value the maximum number of messages per run; 0 is unlimited.
	 */
	public function setLimit($value)
	{
		$this->_limit = max(0, TPropertyValue::ensureInteger($value));
	}
}
