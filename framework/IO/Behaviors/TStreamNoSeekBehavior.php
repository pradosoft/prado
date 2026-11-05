<?php

/**
 * TStreamNoSeekBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Behaviors;

use Prado\Util\TBehavior;
use Prado\Util\TCallChain;

/**
 * TStreamNoSeekBehavior class.
 *
 * Forbids repositioning on the {@see \Prado\IO\TStream} it is attached to.  The
 * behavior answers the stream's {@see \Prado\IO\TStream::isSeekable() dyIsSeekable}
 * capability hook, so {@see \Prado\IO\TStream::isSeekable() isSeekable()} reports false
 * and {@see \Prado\IO\TStream::seek() seek()} throws without moving the cursor.
 *
 * ```php
 * $s = TStream::fromString('forward only');
 * $s->attachBehavior('noseek', new TStreamNoSeekBehavior());
 * $s->read(7);
 * $s->seek(0);       // throws; the cursor stays put
 * ```
 *
 * It leaves reads and writes untouched, which suits wrapping a seekable stream that
 * downstream code must treat as a one-pass source.  {@see \Prado\IO\TStream::rewind()
 * rewind()} is a seek to the start, so it is blocked with the rest.  The decorator
 * counterpart, for wrapping any PSR-7 stream, is {@see \Prado\IO\Stream\TNoSeekStream}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TStreamNoSeekBehavior extends TBehavior
{
	/**
	 * Forces the seekable capability to false, which makes every seek throw.  The chain
	 * continues with the forced value, so a later behavior observes the effective
	 * capability, and its result is discarded, so the denial stands.
	 * @param bool $seekable The incoming seekable flag.
	 * @param ?TCallChain $chain The behavior call chain.
	 * @return bool False, denying seekability, regardless of the chained result.
	 */
	public function dyIsSeekable(bool $seekable, ?TCallChain $chain = null): bool
	{
		if ($chain !== null) {
			$chain->dyIsSeekable(false);
		}
		return false;
	}
}
