<?php
/**
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @link http://www.pradosoft.com/
 * @copyright 2010 Bigpoint GmbH
 * @license http://www.pradosoft.com/license/
 * @since 3.2
 * @package Prado\Util
 */

namespace Prado\Util;

/**
 * TRpcClientResponseException class
 *
 * This Exception is fired when the
 *
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @version $Id$
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