<?php

/**
 * TMailTransport class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\Transport;

use Prado\Email\TEmail;
use Prado\TPropertyValue;

/**
 * TMailTransport class.
 *
 * TMailTransport delivers emails with the PHP mail() function, which hands them to
 * the mail system of the host.  It is the transport of a {@see \Prado\Email\TEmailModule}
 * that configures none.
 *
 * <code>
 * <transport class="Prado\Email\Transport\TMailTransport" UseEnvelopeSender="true" />
 * </code>
 *
 * The Bcc addresses go to mail() as a header, which the mail system removes on
 * delivery.  With {@see setUseEnvelopeSender UseEnvelopeSender} the Return-Path of the
 * email becomes the "-f" parameter of sendmail, so bounces reach it.
 *
 * mail() reports only that the mail system took the email.  A refusal is a
 * transient failure because its reason is unknown.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TMailTransport extends TEmailTransport
{
	/** The addresses that are safe as a sendmail "-f" parameter. */
	protected const SAFE_ADDRESS = '/^[A-Za-z0-9._%+=\-]+@[A-Za-z0-9.\-]+$/';

	/** @var string the additional parameters of mail(). */
	private string $_additionalParameters = '';

	/** @var bool whether the envelope sender is passed to sendmail. */
	private bool $_useEnvelopeSender = false;

	/**
	 * Delivers an email through mail().
	 * @param TEmail $email the final email.
	 * @throws TEmailTransportException when the email has no sender or recipient, or mail() refuses it.
	 */
	public function send(TEmail $email): void
	{
		$sender = $this->getEnvelopeSender($email)->getAddress();
		$this->getEnvelopeRecipients($email);
		$builder = $this->getMimeBuilder();
		$headers = $builder->getHeaders($email);
		[$contentHeaders, $content] = $builder->getContent($email);
		$to = $headers['To'] ?? '';
		$subject = $headers['Subject'];
		unset($headers['To'], $headers['Subject']);
		$parameters = $this->getAdditionalParameters();
		if ($this->getUseEnvelopeSender() && preg_match(self::SAFE_ADDRESS, $sender)) {
			$parameters = trim($parameters . ' -f' . $sender);
		}
		try {
			$accepted = $this->mail($to, $subject, $content, array_merge($headers, $contentHeaders), $parameters);
			$reason = 'mail() returned false';
		} catch (\Throwable $e) {
			$accepted = false;
			$reason = rtrim($e->getMessage(), '.');
		}
		if (!$accepted) {
			throw (new TEmailTransportException('mailtransport_failed', $to, $reason))->setIsTransient(true);
		}
	}

	/**
	 * Calls the PHP mail() function.
	 * @param string $to the encoded To header value.
	 * @param string $subject the encoded subject.
	 * @param string $content the encoded content.
	 * @param array<string, string> $headers the other headers by name.
	 * @param string $parameters the additional parameters of the mail program.
	 * @return bool whether the mail system took the email.
	 */
	protected function mail(string $to, string $subject, string $content, array $headers, string $parameters): bool
	{
		return mail($to, $subject, $content, $headers, $parameters);
	}

	/**
	 * @return string the additional parameters of the mail program. Defaults to ''.
	 */
	public function getAdditionalParameters(): string
	{
		return $this->_additionalParameters;
	}

	/**
	 * @param string $value the additional parameters that mail() passes to the mail program.
	 */
	public function setAdditionalParameters($value)
	{
		$this->_additionalParameters = TPropertyValue::ensureString($value);
	}

	/**
	 * @return bool whether the envelope sender is passed to sendmail as "-f". Defaults to false.
	 */
	public function getUseEnvelopeSender(): bool
	{
		return $this->_useEnvelopeSender;
	}

	/**
	 * @param bool|string $value whether the envelope sender is passed to sendmail as "-f".
	 */
	public function setUseEnvelopeSender($value)
	{
		$this->_useEnvelopeSender = TPropertyValue::ensureBoolean($value);
	}
}
