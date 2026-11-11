<?php

/**
 * TIMAPBouncedTask class file.
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
use Prado\Exceptions\TConfigurationException;
use Prado\Util\Cron\TCronTask;
use Prado\Util\Email\TEmailModule;

/**
 * TIMAPBouncedTask class.
 *
 * Logs into an IMAP mail server and checks for bounced messages.
 * It will raise the onBounced event in the TEmailModule
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TIMAPBouncedTask extends TCronTask
{
	/**
	 * @var string imap connection dns.
	 */
	private $_dns;
	/**
	 * @var string mailbox name.
	 */
	private $_mailbox = 'INBOX';

	/**
	 * This clears the log of the TDBCronModule specified by the ModuleId,
	 * or if none specified, then the cron executing this task.
	 * @param TEmailModule $emailer
	 */
	public function execute($emailer)
	{
		if ($mid = $this->getModuleId()) {
			$emailer = $this->getApplication()->getModule($mid);
			if ($emailer === null) {
				throw new TInvalidDataValueException('imap_bounce_task_moduleid_is_null', $mid);
			}
		}
		if (is_object($emailer) && $emailer instanceof \Prado\Util\Email\TEmailModule) {
			$count = $this->processBouncedEmails($this->getDNS());

			if ($emailer->asa(TCronModule::SHELL_LOG_BEHAVIOR)) {
				$emailer->getOutputWriter()->writeLine("Cleared {$count} Cron Task Logs", TShellWriter::GREEN);
			}
		} elseif (is_object($emailer) && $emailer->asa(TCronModule::SHELL_LOG_BEHAVIOR)) {
			$emailer->getOutputWriter()->writeLine("No DB Cron Module to clean", TShellWriter::RED);
		}
	}

	/**
	 * @return string the connection string for the imap server
	 */
	public function getDNS()
	{
		return $this->_dns;
	}

	/**
	 * eg imap://username:password@imap.mail.me.com:993/tls/secure:
	 * @param string $dns the connection string for the imap server
	 */
	public function setDNS($dns)
	{
		$this->_dns = TPropertyValue::ensureString($dns);
	}

	/**
	 * @return string the name of the mailbox
	 */
	public function getMailbox()
	{
		return $this->_mailbox;
	}

	/**
	 * eg imap://username:password@imap.mail.me.com:993/tls/secure:
	 * @param string $box the name of the mailbox
	 */
	public function setMailbox($box)
	{
		$this->_mailbox = TPropertyValue::ensureString($box);
	}

	/**
	 *
	 * @param string $dns IMAP dns connection string
	 * @throws \Prado\Exceptions\TConfigurationException
	 */
	public function processBouncedEmails($dns)
	{
		if (!function_exists('imap_open')) {
			throw new TConfigurationException('imapbounce_no_php_imap');
		}
		if (($dns = parse_url($dns)) === false) {
			throw new TConfigurationException('imapbounce_bad_dns');
		}
		/* connect to server */
		$port = $dns['host'] ?? 993;
		$hostname = '{' . $dns['host'] . ($port ? ':' . $port : '') . $dns['path'] . '}' . $this->getMailbox();

		/* try to connect */
		$mbox = imap_open($hostname, $dns['username'], $dns['password']);

		if ($mbox === false) {
			echo('Cannot connect to mailbox: ' . imap_last_error());
			return;
		}

		/* grab emails */
		$emails = imap_search($mbox, 'UNSEEN'); //

		/* if emails are returned, cycle through each... */
		if ($emails) {
			/* for every email... */
			foreach ($emails as $email_number) {
				//$message = imap_body($mbox, $email_number, 2);
				$headers = imap_headerinfo($mbox, $email_number, 2);
				$uid = imap_uid($mbox, $email_number);

				$s = imap_fetchstructure($mbox, $email_number);
				$parts = !$s->parts ? [$s] : $s->parts;
				foreach ($parts as $partno0 => $p) {
					//getpart($mbox,$mid,$p,$partno0 + 1);
					/*
					bounced email from google looks has these fields:
					Content-Type: multipart/report; boundary="0000000000008a36c805ef9dac1d"; report-type=delivery-status
					References: <xygw52PsYpxLGQY3AAtBfKXBUaNwLjPlMYSy7U6XGE@localhost>
					In-Reply-To: <xygw52PsYpxLGQY3AAtBfKXBUaNwLjPlMYSy7U6XGE@localhost>
					X-Failed-Recipients: thisdoesntexist12345@google.com

					--<boundary, email part>
					Content-Type: message/delivery-status

					Reporting-MTA: dns; googlemail.com
					Received-From-MTA: dns; belisoful@icloud.com
					Arrival-Date: Mon, 12 Dec 2022 00:56:05 -0800 (PST)
					X-Original-Message-ID: <xygw52PsYpxLGQY3AAtBfKXBUaNwLjPlMYSy7U6XGE@localhost>

					Final-Recipient: rfc822; thisdoesntexist12345@google.com
					Action: failed
					Status: 5.0.0
					Diagnostic-Code: smtp; The email account that you tried to reach does not exist. Please try double-checking the recipient's email address for typos or unnecessary spaces. Learn more at https://support.google.com/mail/answer/6596
					Last-Attempt-Date: Mon, 12 Dec 2022 00:56:07 -0800 (PST)
					*/
				}

				//Content-Type: message/delivery-status
				// X-Original-Message-ID  Action Status Diagnostic-Code Last-Attempt-Date
				imap_setflag_full($mbox, $uid, "\\Seen", ST_UID);
				//imap_delete($mbox, $email_number);
			}
			//imap_expunge($mbox);
		}
		imap_close($mbox);
	}
}
