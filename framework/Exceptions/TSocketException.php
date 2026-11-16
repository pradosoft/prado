<?php

/**
 * TSocketException class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Exceptions;

use Prado\TPropertyValue;

/**
 * TSocketException class
 *
 * TSocketException handles all socket related exceptions.  It manages the socket
 * errorCode and gets their translated message from PHP.
 *
 * Without a message, the message is `socket_strerror($errorCode)` when the sockets
 * extension is loaded, else `socketexception_error_code` ("Socket error {0}.").
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.0
 */
class TSocketException extends TNetworkException
{
	/**
	 * Constructor.
	 * @param int $errorCode Network error code.
	 * @param ?string $errorMessage error message.  default null to be filled in by
	 *   PHP `socket_strerror($errorCode)`, or `socketexception_error_code` without the
	 *   sockets extension.
	 */
	public function __construct($errorCode, $errorMessage = null)
	{
		if ($errorMessage !== null) {
			parent::__construct($errorCode, $errorMessage);
		} elseif ($this->getSocketsLoaded()) {
			parent::__construct($errorCode, socket_strerror($errorCode));
		} else {
			parent::__construct($errorCode, 'socketexception_error_code', $errorCode);
		}
	}

	/**
	 * Returns whether the sockets extension, which provides `socket_strerror()`, is loaded.
	 * @return bool whether the sockets extension is loaded.
	 * @since 4.4.0
	 */
	protected function getSocketsLoaded(): bool
	{
		return extension_loaded('sockets');
	}
}
