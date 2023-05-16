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
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.3
 */
class TSocketException extends TNetworkException
{
	/**
	 * Constructor.
	 * @param int $errorCode Network error code.
	 * @param string $errorMessage error message.  default null to be filled in by
	 *   PHP `socket_strError($errorCode)`
	 */
	public function __construct($errorCode, $errorMessage = null)
	{
		if ($errorMessage === null) {
			$errorMessage = socket_strerror($errorCode);
		}
		parent::__construct($errorMessage . '=' . $errorCode);
	}
}
