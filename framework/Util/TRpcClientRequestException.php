<?php
/**
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @link https://github.com/pradosoft/prado
 * @copyright 2010 Bigpoint GmbH
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 * @since 3.2
 * @package Prado\Util
 */

namespace Prado\Util;

/**
 * TRpcClientRequestException class
 *
 * This Exception is fired if the RPC request fails because of transport problems e.g. when
 * there is no RPC server responding on the given remote host.
 *
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @version $Id$
 * @package Prado\Util
 * @since 3.2
 */

class TRpcClientRequestException extends TApplicationException
{
}