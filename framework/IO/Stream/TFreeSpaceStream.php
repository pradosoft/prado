<?php

/**
 * TFreeSpaceStream class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Stream;

use Psr\Http\Message\StreamInterface;

/**
 * TFreeSpaceStream class.
 *
 * Presents only the non-reserved bytes of an inner stream as one contiguous stream: the
 * reserved spaces are excluded from the logical address space, so reading and writing flow
 * through the free space and the reserved ranges are never touched.  {@see tell()},
 * {@see seek()}, and {@see getSize()} report logical (free-space) coordinates, so a consumer
 * sees a stream whose length is the inner size minus the reserved bytes.
 *
 * ```php
 * $whole = TStream::fromString('AAA####BBB');   // #### reserved at offset 3
 * $free  = new TFreeSpaceStream($whole, [[3, 4]]);
 * echo (string) $free;                           // "AAABBB"
 * ```
 *
 * Skipping reserved spaces relies on a seekable inner stream.  The 1:1 view that keeps
 * reserved spaces addressable in place is {@see TReservedSpaceStream}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TFreeSpaceStream extends TBaseSpaceStream
{
	/**
	 * @param ?StreamInterface $stream The inner stream to decorate.
	 * @param array<int, array{0: int, 1: int}> $reservedSpaces The reserved spaces as [offset, length] pairs.
	 */
	public function __construct(?StreamInterface $stream = null, array $reservedSpaces = [])
	{
		parent::__construct($stream, $reservedSpaces);
		if ($stream !== null && $stream->isSeekable()) {
			$stream->seek($this->logicalToPhysical(0), SEEK_SET);
		}
	}

	/**
	 * Returns the logical size: the inner size minus the reserved bytes within it.
	 * @return ?int The free-space size in bytes, or null when the inner size is unknown.
	 */
	public function getSize(): ?int
	{
		$size = $this->getStream()->getSize();
		if ($size === null) {
			return null;
		}
		$reserved = 0;
		foreach ($this->getSpacesDirect() as [$start, $end]) {
			$clippedStart = min($start, $size);
			$clippedEnd = min($end, $size);
			$reserved += max(0, $clippedEnd - $clippedStart);
		}
		return $size - $reserved;
	}

	/**
	 * Returns the logical (free-space) position.
	 * @return int The position in bytes.
	 */
	public function tell(): int
	{
		return $this->physicalToLogical($this->getStream()->tell());
	}

	/**
	 * Indicates whether the free space is exhausted.
	 * @return bool True at the end of the free space.
	 */
	public function eof(): bool
	{
		$size = $this->getSize();
		if ($size !== null) {
			return $this->tell() >= $size;
		}
		return $this->getStream()->eof();
	}

	/**
	 * Seeks within the logical (free-space) coordinates.
	 * @param int $offset The logical offset.
	 * @param int $whence SEEK_SET, SEEK_CUR, or SEEK_END.
	 * @throws \RuntimeException When the whence is unknown, the target is negative, or SEEK_END
	 *   is used and the size is unknown.
	 */
	public function seek(int $offset, int $whence = SEEK_SET): void
	{
		$target = match ($whence) {
			SEEK_SET => $offset,
			SEEK_CUR => $this->tell() + $offset,
			SEEK_END => $this->seekEndTarget($offset),
			default => throw new \RuntimeException('TFreeSpaceStream cannot seek with unknown whence ' . $whence),
		};
		if ($target < 0) {
			throw new \RuntimeException('TFreeSpaceStream cannot seek to a negative position');
		}
		$this->getStream()->seek($this->logicalToPhysical($target), SEEK_SET);
	}

	/**
	 * Resolves a SEEK_END target against the logical size.
	 * @param int $offset The offset relative to the logical end.
	 * @throws \RuntimeException When the size is unknown.
	 * @return int The logical target.
	 */
	private function seekEndTarget(int $offset): int
	{
		$size = $this->getSize();
		if ($size === null) {
			throw new \RuntimeException('TFreeSpaceStream cannot seek from the end of an unsized stream');
		}
		return $size + $offset;
	}

	/**
	 * Reads up to $length bytes from the free space, skipping reserved spaces.
	 * @param int $length The maximum number of bytes to read.
	 * @return string The bytes read.
	 */
	public function read(int $length): string
	{
		return $this->readSkipping($length);
	}

	/**
	 * Writes $string into the free space, skipping reserved spaces.
	 * @param string $string The bytes to write.
	 * @return int The number of bytes written.
	 */
	public function write(string $string): int
	{
		return $this->writeSkipping($string);
	}
}
