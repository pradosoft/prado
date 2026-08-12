<?php

/**
 * TReservedSpaceMode class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Stream;

use Prado\TEnumerable;

/**
 * TReservedSpaceMode class.
 *
 * Enumerates how {@see TReservedSpaceStream} handles a read or write that reaches a
 * reserved space.
 *
 * | Constant | Behavior |
 * |----------|----------|
 * | Clip     | The operation stops at the reserved boundary and reports the bytes handled, like a short read or write.  A position inside a reserved space handles zero bytes. |
 * | Fail     | An operation whose range overlaps a reserved space raises a {@see \Prado\Exceptions\TIOException}. |
 * | Skip     | The operation jumps over the reserved space and continues on the far side within the same call. |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TReservedSpaceMode extends TEnumerable
{
	public const Clip = 'Clip';
	public const Fail = 'Fail';
	public const Skip = 'Skip';
}
