<?php

/**
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @since 3.2
 */

namespace Prado\Web\Services;

use Prado\Exceptions\TException;

/**
 * TRpcException class
 *
 * A TRpcException represents a RPC fault i.e. an error that is caused by the input data
 * sent from the client.
 *
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @since 3.2
 */
class TRpcException extends TException
{
	public function __construct($message, $errorCode = -1)
	{
		$this->setErrorCode($errorCode);

		parent::__construct($message);
	}
}
