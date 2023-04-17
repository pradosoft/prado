<?php
/**
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @since 3.2
 */

namespace Prado\Util;

/**
 * TRpcClientTypesEnumerable enum
 *
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @since 3.2
 */
enum TRpcClientTypesEnumerable: string
{
	case JSON = 'TJsonRpcClient';
	case XML = 'TXmlRpcClient';
}
