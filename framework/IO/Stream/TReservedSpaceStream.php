<?php

/**
 * TReservedSpaceStream class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Stream;

use Prado\Exceptions\TIOException;
use Prado\TPropertyValue;
use Psr\Http\Message\StreamInterface;

/**
 * TReservedSpaceStream class.
 *
 * Presents the inner stream with its addressing unchanged (logical offset equals physical
 * offset) while protecting reserved byte ranges, the private spaces of a format such as
 * EXIF (maker notes, private IFDs).  The whole stream is addressable, so offset pointers
 * elsewhere in the data stay valid; reads and writes lay data down around the reserved
 * spaces, and the reserved bytes stay untouched.
 *
 * {@see getMode()} (a {@see TReservedSpaceMode}) decides what a read or write that reaches
 * a reserved space does:
 *  - Clip (default): the operation stops at the reserved boundary and reports the bytes
 *    handled; a position inside a reserved space handles zero bytes.
 *  - Fail: an operation whose range overlaps a reserved space throws a {@see TIOException}.
 *  - Skip: the operation jumps over the reserved space and continues on the far side.
 *
 * ```php
 * $s = new TReservedSpaceStream(TStream::fromString(str_repeat('.', 20)), [[8, 4]]);
 * $s->write(str_repeat('A', 12));   // writes 8 bytes, clips at the reserved space at 8
 * ```
 *
 * {@see getSize()}, {@see tell()}, and {@see seek()} pass through unchanged.  The contiguous
 * free-space view is {@see TFreeSpaceStream}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TReservedSpaceStream extends TBaseSpaceStream
{
	/** @var string The on-access behavior (a {@see TReservedSpaceMode} constant). */
	private string $_mode = TReservedSpaceMode::Clip;

	/**
	 * @param ?StreamInterface $stream The inner stream to decorate.
	 * @param array<int, array{0: int, 1: int}> $reservedSpaces The reserved spaces as [offset, length] pairs.
	 * @param string $mode The on-access behavior (a {@see TReservedSpaceMode} constant). Default Clip.
	 */
	public function __construct(?StreamInterface $stream = null, array $reservedSpaces = [], string $mode = TReservedSpaceMode::Clip)
	{
		parent::__construct($stream, $reservedSpaces);
		$this->setMode($mode);
	}

	/**
	 * Returns the raw on-access mode.
	 * @return string The raw on-access mode.
	 */
	protected function getModeDirect(): string
	{
		return $this->_mode;
	}

	/**
	 * Sets the raw on-access mode.
	 * @param string $value The raw on-access mode.
	 */
	protected function setModeDirect(string $value): void
	{
		$this->_mode = $value;
	}

	/**
	 * Returns the on-access mode.
	 * @return string A {@see TReservedSpaceMode} constant.
	 */
	public function getMode(): string
	{
		return $this->getModeDirect();
	}

	/**
	 * Sets the on-access mode.
	 * @param string $value A {@see TReservedSpaceMode} constant.
	 * @throws \Prado\Exceptions\TInvalidDataValueException When the value is not a {@see TReservedSpaceMode}.
	 */
	public function setMode(string $value): void
	{
		$this->setModeDirect(TPropertyValue::ensureEnum($value, TReservedSpaceMode::class));
	}

	/**
	 * Reads up to $length bytes, handling a reserved space per the {@see getMode() Mode}.
	 * @param int $length The maximum number of bytes to read.
	 * @throws TIOException When the mode is Fail and the read overlaps a reserved space.
	 * @return string The bytes read.
	 */
	public function read(int $length): string
	{
		if ($this->getModeDirect() === TReservedSpaceMode::Skip) {
			return $this->readSkipping($length);
		}
		$pos = $this->getStream()->tell();
		if ($this->spaceContaining($pos) !== null) {
			if ($this->getModeDirect() === TReservedSpaceMode::Fail) {
				throw new TIOException('reservedspace_access_denied', $pos);
			}
			return '';
		}
		$next = $this->nextSpaceAfter($pos);
		if ($this->getModeDirect() === TReservedSpaceMode::Fail && $next !== null) {
			// Bound the overlap test by the bytes the read can actually return, so a large
			// request near the end of stream does not trip on a space it cannot reach.
			$size = $this->getStream()->getSize();
			$effective = ($size === null) ? $length : min($length, max(0, $size - $pos));
			if ($next[0] < $pos + $effective) {
				throw new TIOException('reservedspace_access_denied', $next[0]);
			}
		}
		$gap = ($next === null) ? $length : min($length, $next[0] - $pos);
		return $this->getStream()->read($gap);
	}

	/**
	 * Writes $string, handling a reserved space per the {@see getMode() Mode}.
	 * @param string $string The bytes to write.
	 * @throws TIOException When the mode is Fail and the write overlaps a reserved space.
	 * @return int The number of bytes written.
	 */
	public function write(string $string): int
	{
		if ($this->getModeDirect() === TReservedSpaceMode::Skip) {
			return $this->writeSkipping($string);
		}
		$pos = $this->getStream()->tell();
		if ($this->spaceContaining($pos) !== null) {
			if ($this->getModeDirect() === TReservedSpaceMode::Fail) {
				throw new TIOException('reservedspace_access_denied', $pos);
			}
			return 0;
		}
		$next = $this->nextSpaceAfter($pos);
		$length = strlen($string);
		if ($this->getModeDirect() === TReservedSpaceMode::Fail && $next !== null && $next[0] < $pos + $length) {
			throw new TIOException('reservedspace_access_denied', $next[0]);
		}
		$cap = ($next === null) ? $length : min($length, $next[0] - $pos);
		return $this->getStream()->write(substr($string, 0, $cap));
	}
}
