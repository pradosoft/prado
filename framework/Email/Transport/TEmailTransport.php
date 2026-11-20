<?php

/**
 * TEmailTransport class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\Transport;

use Prado\Email\Mime\TEmailMimeBuilder;
use Prado\Email\TEmail;
use Prado\Email\TEmailAddress;

/**
 * TEmailTransport class.
 *
 * TEmailTransport is the base class of the transports that {@see \Prado\Email\TEmailModule}
 * loads from its `<transport>` elements.  It holds the {@see TEmailMimeBuilder}
 * that writes the message and derives the SMTP envelope of an email.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
abstract class TEmailTransport extends \Prado\TApplicationComponent implements IEmailTransport
{
	/** @var ?TEmailMimeBuilder the builder that writes the message. */
	private ?TEmailMimeBuilder $_mimeBuilder = null;

	/**
	 * Initializes the transport after its properties are set.
	 * @param mixed $config the `<transport>` configuration.
	 */
	public function init($config)
	{
	}

	/**
	 * Releases nothing; a transport that keeps a connection overrides it.
	 */
	public function close(): void
	{
	}

	/**
	 * @return TEmailMimeBuilder the builder that writes the message.
	 */
	public function getMimeBuilder(): TEmailMimeBuilder
	{
		return $this->_mimeBuilder ??= new TEmailMimeBuilder();
	}

	/**
	 * @param TEmailMimeBuilder $builder the builder that writes the message.
	 */
	public function setMimeBuilder(TEmailMimeBuilder $builder)
	{
		$this->_mimeBuilder = $builder;
	}

	/**
	 * Returns the envelope sender: the Return-Path, else the Sender, else the From.
	 * @param TEmail $email the email.
	 * @throws TEmailTransportException when the email has none of them.
	 * @return TEmailAddress the envelope sender.
	 */
	protected function getEnvelopeSender(TEmail $email): TEmailAddress
	{
		$sender = $email->getReturnPath() ?? $email->getSender() ?? $email->getFrom();
		if ($sender === null) {
			throw new TEmailTransportException('emailtransport_from_required');
		}
		return $sender;
	}

	/**
	 * Returns the envelope recipients: the To, Cc, and Bcc addresses.
	 * @param TEmail $email the email.
	 * @throws TEmailTransportException when the email has no recipient.
	 * @return TEmailAddress[] the envelope recipients.
	 */
	protected function getEnvelopeRecipients(TEmail $email): array
	{
		$recipients = $email->getRecipients();
		if (!$recipients) {
			throw new TEmailTransportException('emailtransport_recipient_required');
		}
		return $recipients;
	}
}
