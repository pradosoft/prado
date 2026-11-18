<?php

/**
 * TEmailTransportException class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\Transport;

use Prado\Exceptions\TNetworkException;

/**
 * TEmailTransportException class.
 *
 * TEmailTransportException is thrown when a transport cannot deliver an email.
 * {@see getIsTransient} says whether a later attempt may succeed, which is what
 * the email queue retries on.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailTransportException extends TNetworkException
{
	/** @var bool whether a later attempt may succeed. */
	private bool $_transient = false;

	/**
	 * @return bool whether a later attempt may succeed. Defaults to false.
	 */
	public function getIsTransient(): bool
	{
		return $this->_transient;
	}

	/**
	 * @param bool $value whether a later attempt may succeed.
	 * @return static the exception.
	 */
	public function setIsTransient(bool $value): static
	{
		$this->_transient = $value;
		return $this;
	}
}
