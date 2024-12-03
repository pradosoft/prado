<?php

/**
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @since 3.2
 */

namespace Prado\Util;

/**
 * TRpcClientTypesEnumerable class
 *
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @since 3.2
 */
class TRpcClientTypesEnumerable extends \Prado\TEnumerable
{
	public const JSON = 'TJsonRpcClient';
	public const XML = 'TXmlRpcClient';
}
