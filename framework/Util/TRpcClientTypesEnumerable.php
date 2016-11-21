<?php
/**
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @link https://github.com/pradosoft/prado4
 * @copyright 2010 Bigpoint GmbH
 * @license https://github.com/pradosoft/prado4/blob/master/LICENSE
 * @since 3.2
 * @package Prado\Util
 */

namespace Prado\Util;

/**
 * TRpcClientTypesEnumerable class
 *
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @package Prado\Util
 * @since 3.2
 */

class TRpcClientTypesEnumerable extends \Prado\TEnumerable
{
	const JSON = 'TJsonRpcClient';
	const XML = 'TXmlRpcClient';
}