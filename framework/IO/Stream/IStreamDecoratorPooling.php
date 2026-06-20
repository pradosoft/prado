<?php

/**
 * IStreamDecoratorPooling interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Stream;

use Psr\Http\Message\StreamInterface;

/**
 * IStreamDecoratorPooling interface.
 *
 * Implemented by a stream decorator that can be returned to a fresh state and reused, so a pool
 * hands the same instance to successive operations instead of allocating a new one.
 * {@see recycle()} clears the per-use state (a codec context, buffers, the position) and rebinds the
 * given inner stream, leaving the decorator ready as if freshly constructed; {@see release()} is the
 * inverse, unbinding the inner stream and clearing the state so the decorator can be parked.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IStreamDecoratorPooling
{
	/**
	 * Returns the decorator to a fresh state for reuse, optionally over a new inner stream.
	 * @param ?StreamInterface $stream The new inner stream to decorate, or null to keep the current one.
	 * @return static The recycled decorator.
	 */
	public function recycle(?StreamInterface $stream = null): static;

	/**
	 * Clears the state and unbinds the inner stream, the inverse of {@see recycle()}.  The decorator
	 * does not flush or close the inner stream, so close it first when its output must be complete.
	 * @return ?StreamInterface The released inner stream, or null when none was bound.
	 */
	public function release(): ?StreamInterface;
}
