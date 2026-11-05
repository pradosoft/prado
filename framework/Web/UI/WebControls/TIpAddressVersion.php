<?php

/**
 * TIpAddressVersion class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TIpAddressVersion class.
 *
 * TIpAddressVersion defines the enumerable type for the IP address versions that
 * {@see TIpAddressValidator} accepts:
 * - Any: IPv4 and IPv6 addresses.
 * - IPv4: dotted decimal IPv4 addresses only.
 * - IPv6: IPv6 addresses only, including the IPv4-mapped form "::ffff:a.b.c.d".
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TIpAddressVersion extends \Prado\TEnumerable
{
	public const Any = 'Any';
	public const IPv4 = 'IPv4';
	public const IPv6 = 'IPv6';
}
