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
 * TRpcClientTypesEnumerable class
 *
 * @author Robin J. Rogge <rrogge@bigpoint.net>
 * @version $Id$
 * @package Prado\Util
 * @since 3.2
 */

class TRpcClientTypesEnumerable extends TEnumerable
{
	const JSON = 'TJsonRpcClient';
	const XML = 'TXmlRpcClient';
}