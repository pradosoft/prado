<?php

/**
 * TEmailBounceTask class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\Cron;

use Prado\Email\Mailbox\IEmailMailbox;
use Prado\Email\Mailbox\TImapMailbox;
use Prado\TPropertyValue;

/**
 * TEmailBounceTask class.
 *
 * TEmailBounceTask reads the unseen messages of the mailbox that receives
 * bounces, the Return-Path of the emails, and hands each to
 * {@see \Prado\Email\TEmailModule::processBounceMessage}.  A processed message is
 * marked seen; a message that is not a bounce is marked seen and ignored.
 *
 * <code>
 * <job Name="emailbounce" Schedule="5,35 * * * *" Task="Prado\Email\Cron\TEmailBounceTask"
 *     ModuleId="email" Dsn="imaps://bounces%40example.com:password@imap.example.com/INBOX" />
 * </code>
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailBounceTask extends TEmailTask
{
	/** @var string the DSN of the bounce mailbox. */
	private string $_dsn = '';

	/** @var int the most messages per run. */
	private int $_limit = 100;

	/**
	 * Processes the unseen messages of the bounce mailbox.
	 * @param \Prado\Util\Cron\TCronModule $cronModule the cron module running the task.
	 */
	public function execute($cronModule)
	{
		$module = $this->getEmailModule();
		$mailbox = $this->createMailbox();
		$messages = $bounces = 0;
		try {
			$mailbox->open();
			$ids = $mailbox->getUnseenMessageIds();
			if (($limit = $this->getLimit()) > 0) {
				$ids = array_slice($ids, 0, $limit);
			}
			foreach ($ids as $id) {
				$bounces += count($module->processBounceMessage($mailbox->fetchMessage($id)));
				$mailbox->markSeen($id);
				$messages++;
			}
		} finally {
			$mailbox->close();
		}
		$this->writeLine($cronModule, "Processed {$messages} messages with {$bounces} bounced recipients");
	}

	/**
	 * @return IEmailMailbox the bounce mailbox, a {@see TImapMailbox} of the Dsn.
	 */
	protected function createMailbox(): IEmailMailbox
	{
		$mailbox = new TImapMailbox();
		$mailbox->setDsn($this->getDsn());
		return $mailbox;
	}

	/**
	 * @return string the DSN of the bounce mailbox. Defaults to ''.
	 */
	public function getDsn(): string
	{
		return $this->_dsn;
	}

	/**
	 * @param string $value the DSN of the bounce mailbox, as {@see TImapMailbox::setDsn} reads it.
	 */
	public function setDsn($value)
	{
		$this->_dsn = TPropertyValue::ensureString($value);
	}

	/**
	 * @return int the most messages per run; 0 for all. Defaults to 100.
	 */
	public function getLimit(): int
	{
		return $this->_limit;
	}

	/**
	 * @param int|string $value the most messages per run; 0 for all.
	 */
	public function setLimit($value)
	{
		$this->_limit = max(0, TPropertyValue::ensureInteger($value));
	}
}
