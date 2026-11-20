<?php

/**
 * TLogRouter, TLogRoute, TFileLogRoute, TEmailLogRoute class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Log;

use Prado\Email\TEmail;
use Prado\Email\TEmailModule;
use Prado\Email\TEmailStatus;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TLogException;
use Prado\TPropertyValue;

/**
 * TEmailLogRoute class.
 *
 * TEmailLogRoute sends selected log messages to email addresses.
 * The target email addresses may be specified via {@see setEmails Emails} property.
 * Optionally, you may set the email {@see setSubject Subject} and the
 * {@see setSentFrom SentFrom} address.
 *
 * The messages are sent with the PHP mail() function.  When
 * {@see setEmailModuleID EmailModuleID} is the ID of a {@see TEmailModule}, they
 * are sent through that module and its transport, and the SentFrom may be left
 * to the From of the module.
 *
 * TEmailLogRoute is configured as a `<route>` element inside a
 * {@see \Prado\Util\Log\TLogRouter} module in the application configuration.
 *
 * XML configuration style:
 * ```xml
 * <modules>
 *   <module id="log" class="Prado\Util\Log\TLogRouter">
 *     <route class="Prado\Util\Log\TEmailLogRoute" Levels="Error, Fatal"
 *         Emails="admin@example.com" SentFrom="app@example.com"
 *         Subject="Application Error" />
 *   </module>
 * </modules>
 * ```
 *
 * PHP configuration style:
 * ```php
 * return [
 *     'modules' => [
 *         'log' => [
 *             'class' => 'Prado\Util\Log\TLogRouter',
 *             'routes' => [
 *                 [
 *                     'class' => 'Prado\Util\Log\TEmailLogRoute',
 *                     'properties' => [
 *                         'Levels' => 'Error, Fatal',
 *                         'Emails' => 'admin@example.com',
 *                         'SentFrom' => 'app@example.com',
 *                         'Subject' => 'Application Error',
 *                     ],
 *                 ],
 *             ],
 *         ],
 *     ],
 * ];
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TEmailLogRoute extends TLogRoute
{
	/**
	 * Regex pattern for email address.
	 */
	public const EMAIL_PATTERN = '/^([0-9a-zA-Z]+[-._+&])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,}$/';
	/**
	 * Default email subject.
	 */
	public const DEFAULT_SUBJECT = 'Prado Application Log';
	/**
	 * @var array list of destination email addresses.
	 */
	private $_emails = [];
	/**
	 * @var ?string email subject; null for {@see DEFAULT_SUBJECT}
	 */
	private $_subject;
	/**
	 * @var string email sent from address
	 */
	private $_from = '';
	/**
	 * @var string the ID of the email module that sends the messages
	 */
	private $_emailModuleID = '';

	/**
	 * Initializes the route.
	 * @param null|array|\Prado\Xml\TXmlElement $config configurations specified in {@see \Prado\Util\Log\TLogRouter}.
	 * @throws TConfigurationException if {@see getSentFrom SentFrom} is empty and
	 *   'sendmail_from' in php.ini is also empty, unless an EmailModuleID is set.
	 */
	public function init($config)
	{
		if ($this->_emailModuleID !== '') {
			return;
		}
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
		if (($module = $this->getEmailModule()) !== null) {
			$this->sendByModule($module, $message);
			return;
		}
		$returnPath = ini_get('sendmail_path') ? "Return-Path:{$this->_from}\r\n" : '';
		foreach ($this->_emails as $email) {
			if (!$this->sendMail($email, $this->getSubject(), $message, "From:{$this->_from}\r\n{$returnPath}")) {
				throw new TLogException('emaillogroute_mail_failed', $email);
			}
		}
	}

	/**
	 * Sends one email with PHP's `mail()`.
	 * @param string $email the recipient address.
	 * @param string $subject the email subject.
	 * @param string $message the email body.
	 * @param string $headers the additional headers.
	 * @return bool whether the mail was accepted for delivery.
	 * @since 4.4.0
	 * @codeCoverageIgnore Sends real mail.
	 */
	protected function sendMail(string $email, string $subject, string $message, string $headers): bool
	{
		return mail($email, $subject, $message, $headers);
	}

	/**
	 * @return array list of destination email addresses
	 */
	public function getEmails()
	{
		return $this->_emails;
	}

	/**
	 * Sets the destination email addresses.  Each address is trimmed, and an address
	 * that does not match {@see EMAIL_PATTERN} is dropped.
	 * @param array|string $emails list of destination email addresses. If the value is
	 * a string, it is assumed to be comma-separated email addresses.
	 */
	public function setEmails($emails)
	{
		if (!is_array($emails)) {
			$emails = explode(',', $emails);
		}
		$this->_emails = [];
		foreach ($emails as $email) {
			$email = trim((string) $email);
			if (preg_match(self::EMAIL_PATTERN, $email)) {
				$this->_emails[] = $email;
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
	 * @param ?string $value email subject; null for {@see DEFAULT_SUBJECT}.
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

	/**
	 * Sends the log text to each address through an email module.  The text is a
	 * placeholder value, so "{{...}}" in a log message stays as it is.
	 * @param TEmailModule $module the email module.
	 * @param string $message the log text.
	 * @throws TLogException when an email fails.
	 * @since 4.4.0
	 */
	protected function sendByModule(TEmailModule $module, string $message): void
	{
		foreach ($this->_emails as $address) {
			$email = new TEmail();
			$email->setTo($address);
			$email->setFrom($this->_from);
			$email->setSubject($this->getSubject());
			$email->setText('{{{LOG}}}');
			$email->setData(['LOG' => $message]);
			if ($module->sendEmail($email) === TEmailStatus::Failed) {
				throw new TLogException('emaillogroute_mail_failed', $address);
			}
		}
	}

	/**
	 * @throws TConfigurationException when the EmailModuleID is not the ID of a TEmailModule.
	 * @return ?TEmailModule the email module that sends the messages, null to send with mail().
	 * @since 4.4.0
	 */
	protected function getEmailModule(): ?TEmailModule
	{
		if ($this->_emailModuleID === '') {
			return null;
		}
		$module = $this->getApplication()->getModule($this->_emailModuleID);
		if (!($module instanceof TEmailModule)) {
			throw new TConfigurationException('emaillogroute_emailmoduleid_invalid', $this->_emailModuleID);
		}
		return $module;
	}

	/**
	 * @return string the ID of the email module that sends the messages, empty to send with mail(). Defaults to ''.
	 * @since 4.4.0
	 */
	public function getEmailModuleID()
	{
		return $this->_emailModuleID;
	}

	/**
	 * @param string $value the ID of the {@see TEmailModule} that sends the messages; empty to send with mail().
	 * @since 4.4.0
	 */
	public function setEmailModuleID($value)
	{
		$this->_emailModuleID = TPropertyValue::ensureString($value);
	}
}
