<?php

/**
 * TBaseSpaceStream class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Stream;

use Prado\Exceptions\TInvalidDataValueException;
use Psr\Http\Message\StreamInterface;

/**
 * TBaseSpaceStream class.
 *
 * The shared base for decorators that treat byte ranges of an inner stream as reserved
 * spaces (regions to preserve, such as EXIF private spaces).  It holds the reserved-space
 * list, normalized to sorted, non-overlapping `[start, end)` ranges, and the lookups and
 * skip-aware read/write helpers the concrete decorators build on.
 *
 * {@see TReservedSpaceStream} keeps the inner addressing 1:1 and protects the reserved
 * spaces in place; {@see TFreeSpaceStream} presents only the non-reserved bytes as a
 * contiguous stream.  A reserved space is given as a `[offset, length]` pair; overlapping
 * or touching pairs merge.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
abstract class TBaseSpaceStream extends TStreamDecorator
{
	/** @var array<int, array{0: int, 1: int}> The reserved spaces as sorted, merged [start, end) ranges. */
	private array $_spaces = [];

	/**
	 * @param ?StreamInterface $stream The inner stream to decorate.
	 * @param array<int, array{0: int, 1: int}> $reservedSpaces The reserved spaces as [offset, length] pairs.
	 */
	public function __construct(?StreamInterface $stream = null, array $reservedSpaces = [])
	{
		parent::__construct($stream);
		$this->setReservedSpaces($reservedSpaces);
	}

	//
	// ─── Self-encapsulated raw accessors ─────────────────────────────────────
	//

	/**
	 * Returns the raw reserved-space ranges.
	 * @return array<int, array{0: int, 1: int}> The raw [start, end) ranges.
	 */
	protected function getSpacesDirect(): array
	{
		return $this->_spaces;
	}

	/**
	 * Sets the raw reserved-space ranges.
	 * @param array<int, array{0: int, 1: int}> $value The raw [start, end) ranges.
	 */
	protected function setSpacesDirect(array $value): void
	{
		$this->_spaces = $value;
	}

	//
	// ─── Reserved-space management ───────────────────────────────────────────
	//

	/**
	 * Returns the reserved spaces as [offset, length] pairs, in offset order.
	 * @return array<int, array{0: int, 1: int}> The reserved spaces as [offset, length] pairs.
	 */
	public function getReservedSpaces(): array
	{
		$pairs = [];
		foreach ($this->getSpacesDirect() as [$start, $end]) {
			$pairs[] = [$start, $end - $start];
		}
		return $pairs;
	}

	/**
	 * Replaces the reserved spaces, normalizing them to sorted, merged ranges.
	 * @param array<int, array{0: int, 1: int}> $spaces The reserved spaces as [offset, length] pairs.
	 */
	public function setReservedSpaces(array $spaces): void
	{
		$this->setSpacesDirect($this->normalizeSpaces($spaces));
	}

	/**
	 * Adds a reserved space and re-normalizes against the existing spaces.
	 * @param int $offset The reserved space's byte offset.
	 * @param int $length The reserved space's length in bytes.
	 */
	public function addReservedSpace(int $offset, int $length): void
	{
		$this->setReservedSpaces([...$this->getReservedSpaces(), [$offset, $length]]);
	}

	/**
	 * Normalizes [offset, length] pairs into sorted, non-overlapping [start, end) ranges,
	 * merging any that overlap or touch.
	 * @param array<int, array{0: int, 1: int}> $spaces The reserved spaces as [offset, length] pairs.
	 * @throws TInvalidDataValueException When an offset is negative or a length is not positive.
	 * @return array<int, array{0: int, 1: int}> The normalized [start, end) ranges.
	 */
	protected function normalizeSpaces(array $spaces): array
	{
		$ranges = [];
		foreach ($spaces as [$offset, $length]) {
			if ($offset < 0 || $length <= 0) {
				throw new TInvalidDataValueException('reservedspace_invalid', $offset, $length);
			}
			$ranges[] = [$offset, $offset + $length];
		}
		usort($ranges, fn ($a, $b) => $a[0] <=> $b[0]);

		$merged = [];
		foreach ($ranges as $range) {
			$last = count($merged) - 1;
			if ($last >= 0 && $range[0] <= $merged[$last][1]) {
				$merged[$last][1] = max($merged[$last][1], $range[1]);
			} else {
				$merged[] = $range;
			}
		}
		return $merged;
	}

	/**
	 * Returns the reserved space containing a physical position, or null when the position
	 * is free.
	 * @param int $position The physical byte position.
	 * @return ?array{0: int, 1: int} The containing [start, end) range, or null.
	 */
	protected function spaceContaining(int $position): ?array
	{
		foreach ($this->getSpacesDirect() as $range) {
			if ($range[0] <= $position && $position < $range[1]) {
				return $range;
			}
		}
		return null;
	}

	/**
	 * Returns the first reserved space that starts after a physical position, or null when
	 * none follows.
	 * @param int $position The physical byte position.
	 * @return ?array{0: int, 1: int} The next [start, end) range, or null.
	 */
	protected function nextSpaceAfter(int $position): ?array
	{
		foreach ($this->getSpacesDirect() as $range) {
			if ($range[0] > $position) {
				return $range;
			}
		}
		return null;
	}

	//
	// ─── Skip-aware read/write ───────────────────────────────────────────────
	//

	/**
	 * Reads up to $length bytes from the inner stream, jumping over any reserved spaces in
	 * the way.  The jump seeks when the inner stream is seekable and reads-and-discards
	 * otherwise.
	 * @param int $length The number of non-reserved bytes to read.
	 * @return string The non-reserved bytes read.
	 */
	protected function readSkipping(int $length): string
	{
		$inner = $this->getStream();
		$data = '';
		while (strlen($data) < $length) {
			$pos = $inner->tell();
			$region = $this->spaceContaining($pos);
			if ($region !== null) {
				if ($inner->isSeekable()) {
					$inner->seek($region[1], SEEK_SET);
				} elseif ($this->discard($region[1] - $pos) === 0) {
					break;
				}
				continue;
			}
			if ($inner->eof()) {
				break;
			}
			$next = $this->nextSpaceAfter($pos);
			$want = $length - strlen($data);
			$gap = ($next === null) ? $want : min($want, $next[0] - $pos);
			$chunk = $inner->read($gap);
			if ($chunk === '') {
				break;
			}
			$data .= $chunk;
		}
		return $data;
	}

	/**
	 * Writes $string to the inner stream, jumping over any reserved spaces in the way.  The
	 * jump requires a seekable inner stream.
	 * @param string $string The bytes to write into the non-reserved space.
	 * @return int The number of bytes written.
	 */
	protected function writeSkipping(string $string): int
	{
		$inner = $this->getStream();
		$written = 0;
		$length = strlen($string);
		while ($written < $length) {
			$pos = $inner->tell();
			$region = $this->spaceContaining($pos);
			if ($region !== null) {
				if (!$inner->isSeekable()) {
					break;
				}
				$inner->seek($region[1], SEEK_SET);
				continue;
			}
			$next = $this->nextSpaceAfter($pos);
			$want = $length - $written;
			$cap = ($next === null) ? $want : min($want, $next[0] - $pos);
			$n = $inner->write(substr($string, $written, $cap));
			if ($n <= 0) {
				break;
			}
			$written += $n;
		}
		return $written;
	}

	/**
	 * Reads and discards up to $count bytes from the inner stream.
	 * @param int $count The number of bytes to discard.
	 * @return int The number of bytes discarded.
	 */
	private function discard(int $count): int
	{
		$inner = $this->getStream();
		$done = 0;
		while ($done < $count && !$inner->eof()) {
			$chunk = $inner->read($count - $done);
			if ($chunk === '') {
				break;
			}
			$done += strlen($chunk);
		}
		return $done;
	}

	//
	// ─── Physical/logical mapping (free-space coordinates) ───────────────────
	//

	/**
	 * Returns the total reserved bytes lying before a physical position.
	 * @param int $physical The physical byte position.
	 * @return int The reserved byte count before the position.
	 */
	protected function reservedBefore(int $physical): int
	{
		$reserved = 0;
		foreach ($this->getSpacesDirect() as [$start, $end]) {
			if ($end <= $physical) {
				$reserved += $end - $start;
			} elseif ($start < $physical) {
				$reserved += $physical - $start;
			} else {
				break;
			}
		}
		return $reserved;
	}

	/**
	 * Maps a logical (free-space) offset to a physical offset in the inner stream.
	 * @param int $logical The logical offset.
	 * @return int The physical offset.
	 */
	protected function logicalToPhysical(int $logical): int
	{
		$physical = $logical;
		foreach ($this->getSpacesDirect() as [$start, $end]) {
			if ($start <= $physical) {
				$physical += $end - $start;
			} else {
				break;
			}
		}
		return $physical;
	}

	/**
	 * Maps a physical offset in the inner stream to a logical (free-space) offset.
	 * @param int $physical The physical offset.
	 * @return int The logical offset.
	 */
	protected function physicalToLogical(int $physical): int
	{
		return max(0, $physical - $this->reservedBefore($physical));
	}
}
