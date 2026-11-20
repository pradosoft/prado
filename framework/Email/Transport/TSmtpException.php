<?php

/**
 * TSmtpException class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\Transport;

/**
 * TSmtpException class.
 *
 * TSmtpException is thrown when an SMTP server answers a command with a reply
 * that the command does not allow.  A 4xx reply is transient; a 5xx reply is permanent.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TSmtpException extends TEmailTransportException
{
	/** @var int the three-digit reply code of the server. */
	private int $_replyCode = 0;

	/** @var string the reply text of the server. */
	private string $_replyText = '';

	/**
	 * @return int the three-digit reply code of the server, 0 when there was no reply.
	 */
	public function getReplyCode(): int
	{
		return $this->_replyCode;
	}

	/**
	 * @return string the reply text of the server.
	 */
	public function getReplyText(): string
	{
		return $this->_replyText;
	}

	/**
	 * Sets the server reply and derives {@see getIsTransient} from its code.
	 * @param int $code the three-digit reply code.
	 * @param string $text the reply text.
	 * @return static the exception.
	 */
	public function setReply(int $code, string $text): static
	{
		$this->_replyCode = $code;
		$this->_replyText = $text;
		return $this->setIsTransient($code >= 400 && $code < 500);
	}
}
