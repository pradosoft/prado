<?php

/**
 * TEmailEventParameter class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email;

use Prado\IEventStoppableParameter;

/**
 * TEmailEventParameter class.
 *
 * TEmailEventParameter is the parameter of the {@see TEmailModule} sending events.
 *
 * | Event | Parameter |
 * |---|---|
 * | onSendEmail | The final email, before delivery.  A handler may change it, or {@see cancel} it. |
 * | onEmailSent | The email a transport accepted. |
 * | onEmailFailed | The email and the {@see getException exception} of the failed delivery. |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailEventParameter extends \Prado\TEventParameter implements IEventStoppableParameter
{
	/** @var ?\Throwable the exception of a failed delivery. */
	private ?\Throwable $_exception;

	/** @var bool whether the email is cancelled. */
	private bool $_cancelled = false;

	/**
	 * @param TEmail $email the email.
	 * @param ?\Throwable $exception the exception of a failed delivery.
	 */
	public function __construct(TEmail $email, ?\Throwable $exception = null)
	{
		$this->_exception = $exception;
		parent::__construct($email);
	}

	/**
	 * @return TEmail the email.
	 */
	public function getEmail(): TEmail
	{
		return $this->getParameter();
	}

	/**
	 * @return ?\Throwable the exception of a failed delivery, null for the other events.
	 */
	public function getException(): ?\Throwable
	{
		return $this->_exception;
	}

	/**
	 * Cancels the email in an onSendEmail handler.  The email is not delivered and
	 * the later handlers do not run.
	 */
	public function cancel(): void
	{
		$this->_cancelled = true;
	}

	/**
	 * @return bool whether a handler cancelled the email.
	 */
	public function getIsCancelled(): bool
	{
		return $this->_cancelled;
	}

	/**
	 * @return bool whether the later handlers are skipped, which {@see cancel} causes.
	 */
	public function getStopped(): bool
	{
		return $this->_cancelled;
	}

	/**
	 * Cancels the email; stopping the event and cancelling the email are one act.
	 */
	public function stopImmediatePropagation(): void
	{
		$this->cancel();
	}
}
