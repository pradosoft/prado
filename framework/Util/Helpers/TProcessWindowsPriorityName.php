<?php
/**
 * TProcessWindowsPriorityName class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Helpers;

/**
 * TProcessWindowsPriorityName class
 *
 * Windows uses these priority names to specify the priorities of processes.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.2.3
 */
class TProcessWindowsPriorityName extends \Prado\TEnumerable
{
	public const Idle = 'idle';
	public const BelowNormal = 'below normal';
	public const Normal = 'normal';
	public const AboveNormal = 'above normal';
	public const HighPriority = 'high priority';
	public const Realtime = 'realtime';
}
