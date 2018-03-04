<?php
/**
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @link https://github.com/pradosoft/prado
 * @copyright 2010 Bigpoint GmbH
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @since 3.2
 * @package Prado\Util
 */

namespace Prado\Util;

use Prado\Exceptions\TApplicationException;

/**
 * TRpcClientResponseException class
 *
 * This Exception is fired when the
 *
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @package Prado\Util
 * @since 3.2
 */

class TRpcClientResponseException extends TApplicationException
{
	/**
	 * @param string error message
	 * @param integer error code (optional)
	 */
	public function __construct($errorMessage, $errorCode = null)
	{
		$this->setErrorCode($errorCode);

		parent::__construct($errorMessage);
	}
}
