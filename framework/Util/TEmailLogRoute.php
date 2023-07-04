<?php
/**
 * TLogRouter, TLogRoute, TFileLogRoute, TEmailLogRoute class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TLogException;

/**
 * TEmailLogRoute class.
 *
 * TEmailLogRoute sends selected log messages to email addresses.
 * The target email addresses may be specified via {@see setEmails Emails} property.
 * Optionally, you may set the email {@see setSubject Subject} and the
 * {@see setSentFrom SentFrom} address.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TEmailLogRoute extends TLogRoute
{
	/**
	 * Regex pattern for email address.
	 */
	public const EMAIL_PATTERN = '/^([0-9a-zA-Z]+[-._+&])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,6}$/';
	/**
	 * Default email subject.
	 */
	public const DEFAULT_SUBJECT = 'Prado Application Log';
	/**
	 * @var array list of destination email addresses.
	 */
	private $_emails = [];
	/**
	 * @var string email subject
	 */
	private $_subject = '';
	/**
	 * @var string email sent from address
	 */
	private $_from = '';

	/**
	 * Initializes the route.
	 * @param \Prado\Xml\TXmlElement $config configurations specified in {@see \Prado\Util\TLogRouter}.
	 * @throws TConfigurationException if {@see getSentFrom SentFrom} is empty and
	 *   'sendmail_from' in php.ini is also empty.
	 */
	public function init($config)
	{
		if ($this->_from === '') {
			$this->_from = ini_get('sendmail_from');
		}
		if ($this->_from === '') {
			throw new TConfigurationException('emaillogroute_sentfrom_required');
		}
	}

	/**
	 * Sends log messages to specified email addresses.
	 * @param array $logs list of log messages
	 * @param bool $final is the final flush
	 * @param array $meta the meta data for the logs.
	 * @throws TLogException When the mail fails to send.
	 */
	protected function processLogs(array $logs, bool $final, array $meta)
	{
		$message = '';

		foreach ($logs as $log) {
			$message .= $this->formatLogMessage($log) . "\n";
		}
		$message = wordwrap($message, 70);
		$returnPath = ini_get('sendmail_path') ? "Return-Path:{$this->_from}\r\n" : '';
		foreach ($this->_emails as $email) {
			if (!mail($email, $this->getSubject(), $message, "From:{$this->_from}\r\n{$returnPath}")) {
				throw new TLogException('emaillogroute_mail_failed', $email);
			}
		}
	}

	/**
	 * @return array list of destination email addresses
	 */
	public function getEmails()
	{
		return $this->_emails;
	}

	/**
	 * @param array|string $emails list of destination email addresses. If the value is
	 * a string, it is assumed to be comma-separated email addresses.
	 */
	public function setEmails($emails)
	{
		if (is_array($emails)) {
			$this->_emails = $emails;
		} else {
			$this->_emails = [];
			foreach (explode(',', $emails) as $email) {
				$email = trim($email);
				if (preg_match(self::EMAIL_PATTERN, $email)) {
					$this->_emails[] = $email;
				}
			}
		}
	}

	/**
	 * @return string email subject. Defaults to TEmailLogRoute::DEFAULT_SUBJECT
	 */
	public function getSubject()
	{
		if ($this->_subject === null) {
			$this->_subject = self::DEFAULT_SUBJECT;
		}
		return $this->_subject;
	}

	/**
	 * @param string $value email subject.
	 */
	public function setSubject($value)
	{
		$this->_subject = $value;
	}

	/**
	 * @return string send from address of the email
	 */
	public function getSentFrom()
	{
		return $this->_from;
	}

	/**
	 * @param string $value send from address of the email
	 */
	public function setSentFrom($value)
	{
		$this->_from = $value;
	}
}
