<?php

/**
 * TEmailBounce class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\Bounce;

/**
 * TEmailBounce class.
 *
 * TEmailBounce is the delivery failure of one recipient, read from a bounce message
 * by {@see TEmailBounceParser}.  Its fields follow the delivery status notification
 * format (RFC 3464).
 *
 * | Status class | Action | Meaning |
 * |---|---|---|
 * | 5.x.x | failed | Permanent: the address does not accept mail. |
 * | 4.x.x | delayed | Transient: delivery is still being retried. |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailBounce extends \Prado\TComponent
{
	/**
	 * @param string $recipient the address that bounced.
	 * @param string $action the DSN action: "failed" or "delayed".
	 * @param string $status the enhanced status code (RFC 3463), e.g. "5.1.1".
	 * @param string $diagnosticCode the remote server's reason, empty when absent.
	 * @param string $originalMessageId the Message-ID of the bounced email, empty when unknown.
	 * @param string $remoteMta the remote mail server, empty when absent.
	 */
	public function __construct(
		private string $recipient,
		private string $action,
		private string $status,
		private string $diagnosticCode = '',
		private string $originalMessageId = '',
		private string $remoteMta = ''
	) {
		parent::__construct();
	}

	/**
	 * @return string the address that bounced.
	 */
	public function getRecipient(): string
	{
		return $this->recipient;
	}

	/**
	 * @return string the DSN action: "failed" or "delayed".
	 */
	public function getAction(): string
	{
		return $this->action;
	}

	/**
	 * @return string the enhanced status code (RFC 3463), e.g. "5.1.1".
	 */
	public function getStatus(): string
	{
		return $this->status;
	}

	/**
	 * @return string the remote server's reason, empty when absent.
	 */
	public function getDiagnosticCode(): string
	{
		return $this->diagnosticCode;
	}

	/**
	 * @return string the Message-ID of the bounced email, empty when unknown.
	 */
	public function getOriginalMessageId(): string
	{
		return $this->originalMessageId;
	}

	/**
	 * @return string the remote mail server, empty when absent.
	 */
	public function getRemoteMta(): string
	{
		return $this->remoteMta;
	}

	/**
	 * @return bool whether the failure is permanent: a 5.x.x status, or "failed" without a 4.x.x status.
	 */
	public function getIsPermanent(): bool
	{
		$class = substr($this->status, 0, 1);
		return $class === '5' || ($this->action === 'failed' && $class !== '4');
	}
}
