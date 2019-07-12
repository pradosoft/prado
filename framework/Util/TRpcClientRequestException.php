<?php
/**
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @since 3.2
 * @package Prado\Util
 */

namespace Prado\Util;

use Prado\Exceptions\TApplicationException;

/**
 * TRpcClientRequestException class
 *
 * This Exception is fired if the RPC request fails because of transport problems e.g. when
 * there is no RPC server responding on the given remote host.
 *
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @package Prado\Util
 * @since 3.2
 */

class TRpcClientRequestException extends TApplicationException
{
}
