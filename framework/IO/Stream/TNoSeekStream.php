<?php

/**
 * TNoSeekStream class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Stream;

/**
 * TNoSeekStream class.
 *
 * A {@see TStreamDecorator} that hides the seekability of an inner stream: {@see isSeekable()}
 * reports false and {@see seek()} throws, while every other operation forwards unchanged.  It
 * presents a forward-only view of an otherwise seekable stream, the decorator counterpart to
 * {@see \Prado\IO\Behaviors\TStreamNoSeekBehavior}.
 *
 * ```php
 * $forwardOnly = new TNoSeekStream(TStream::fromString('read once'));
 * $forwardOnly->isSeekable();   // false
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TNoSeekStream extends TStreamDecorator
{
	/**
	 * Reports the stream as not seekable.
	 * @return bool Always false.
	 */
	public function isSeekable(): bool
	{
		return false;
	}

	/**
	 * Rejects any seek.
	 * @param int $offset The stream offset (unused).
	 * @param int $whence SEEK_SET, SEEK_CUR, or SEEK_END (unused).
	 * @throws \RuntimeException Always.
	 */
	public function seek(int $offset, int $whence = SEEK_SET): void
	{
		throw new \RuntimeException('Cannot seek a TNoSeekStream');
	}

	/**
	 * Rejects a rewind, which is a seek to the start; forwarding it would bypass the block.
	 * @throws \RuntimeException Always.
	 */
	public function rewind(): void
	{
		throw new \RuntimeException('Cannot seek a TNoSeekStream');
	}
}
