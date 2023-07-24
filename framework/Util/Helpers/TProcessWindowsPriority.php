<?php
/**
 * TProcessWindowsPriority class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Helpers;

/**
 * TProcessWindowsPriority class
 *
 * Windows indicates process priority with these specified priority numbers.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.3
 */
class TProcessWindowsPriority extends \Prado\TEnumerable
{
	public const Idle = 4;
	public const BelowNormal = 6;
	public const Normal = 8;
	public const AboveNormal = 10;
	public const HighPriority = 13;
	public const Realtime = 24;
}
