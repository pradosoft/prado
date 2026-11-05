<?php

/**
 * TBufferStream class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Stream;

use Prado\TComponent;
use Psr\Http\Message\StreamInterface;

/**
 * TBufferStream class.
 *
 * Holds a first-in, first-out byte buffer: {@see write()} appends to the back and
 * {@see read()} drains from the front, removing what it returns.  It is a non-seekable
 * pipe between a producer and a consumer, useful for handing bytes from one to the other
 * without a file or socket.
 *
 * {@see getSize()} reports the bytes currently buffered, and {@see eof()} is true while
 * the buffer is empty.  Reading past the buffered data returns ''.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TBufferStream extends TComponent implements StreamInterface
{
	/** @var int The minimum consumed-prefix size before {@see read()} compacts; it also waits until the consumed prefix outweighs the unread bytes. */
	public const CHUNK_SIZE = 8192;

	/** @var string The buffered bytes; the unread region starts at {@see $_bufferPos}. */
	private string $_buffer = '';

	/** @var int The offset of the first unread byte in {@see $_buffer}. */
	private int $_bufferPos = 0;

	/** @var bool Whether the stream has been closed or detached, leaving it unusable. */
	private bool $_detached = false;

	//
	// ─── Self-encapsulated raw accessors ─────────────────────────────────────
	//

	/**
	 * Returns the unread bytes by reference, compacting away any consumed prefix first so a
	 * caller can mutate the buffer in place (`$b = &$this->getBufferDirect(); $b .= ...`).  The
	 * reference is valid only until the next call that replaces the buffer ({@see read()} on
	 * compaction, {@see setBufferDirect()}), which rebinds the property.
	 * @return string The unread buffered bytes, by reference.
	 */
	protected function &getBufferDirect(): string
	{
		if ($this->_bufferPos > 0) {
			$this->_buffer = substr($this->_buffer, $this->_bufferPos);
			$this->_bufferPos = 0;
		}
		return $this->_buffer;
	}

	/**
	 * Sets the raw buffered bytes, discarding any consumed prefix.
	 * @param string $value The raw buffered bytes.
	 */
	protected function setBufferDirect(string $value): void
	{
		$this->_buffer = $value;
		$this->_bufferPos = 0;
	}

	/**
	 * Returns all buffered bytes from the front without consuming them; {@see getContents()} drains
	 * the buffer.
	 * @return string The full buffered contents.
	 */
	public function __toString(): string
	{
		return $this->getBufferDirect();
	}

	/**
	 * Reads and removes up to $length bytes from the front of the buffer.
	 * @param int $length The maximum number of bytes to read.
	 * @return string The bytes read; '' when the buffer is empty or $length is not positive.
	 */
	public function read(int $length): string
	{
		if ($this->_detached) {
			throw new \RuntimeException('Cannot read from a closed TBufferStream');
		}
		// Advance a read offset instead of re-slicing the remainder each call (which is O(n) per
		// read and quadratic over a large buffer drained in small reads); compact only when the
		// consumed prefix dominates.
		$pos = $this->_bufferPos;
		$available = strlen($this->_buffer) - $pos;
		if ($length <= 0 || $available <= 0) {
			return '';
		}
		$take = $length < $available ? $length : $available;
		$data = substr($this->_buffer, $pos, $take);
		$pos += $take;
		if ($pos >= static::CHUNK_SIZE && $pos >= strlen($this->_buffer) - $pos) {
			$this->_buffer = substr($this->_buffer, $pos);
			$pos = 0;
		}
		$this->_bufferPos = $pos;
		return $data;
	}

	/**
	 * Appends bytes to the back of the buffer.
	 * @param string $string The bytes to append.
	 * @return int The number of bytes appended.
	 */
	public function write(string $string): int
	{
		if ($this->_detached) {
			throw new \RuntimeException('Cannot write to a closed TBufferStream');
		}
		$this->_buffer .= $string;
		return strlen($string);
	}

	/**
	 * Returns the remaining buffered bytes, draining the buffer.
	 * @return string The remaining contents.
	 */
	public function getContents(): string
	{
		if ($this->_detached) {
			throw new \RuntimeException('Cannot read from a closed TBufferStream');
		}
		$data = $this->getBufferDirect();
		$this->setBufferDirect('');
		return $data;
	}

	/**
	 * Returns the number of bytes currently buffered.
	 * @return ?int The buffered byte count.
	 */
	public function getSize(): ?int
	{
		return $this->_detached ? null : strlen($this->_buffer) - $this->_bufferPos;
	}

	/**
	 * Indicates whether the buffer is empty.
	 * @return bool True when no bytes are buffered.
	 */
	public function eof(): bool
	{
		return $this->_bufferPos >= strlen($this->_buffer);
	}

	/**
	 * Detaches the stream, emptying the buffer and marking it unusable.  A buffer has no underlying
	 * resource to return, so this returns null; a later read or write throws.
	 * @return null Always null; the buffer has no resource.
	 */
	public function detach()
	{
		$this->setBufferDirect('');
		$this->_detached = true;
		return null;
	}

	/**
	 * Closes the stream, emptying the buffer and marking it unusable; a later read or write throws.
	 */
	public function close(): void
	{
		$this->setBufferDirect('');
		$this->_detached = true;
	}

	/**
	 * Empties the buffer and returns it to a fresh, usable state, clearing any closed or detached
	 * flag so the object can be reused.  This is a buffer convenience outside PSR-7, distinct from
	 * {@see close()}, which is terminal.
	 * @return static This buffer.
	 */
	public function reset(): static
	{
		$this->setBufferDirect('');
		$this->_detached = false;
		return $this;
	}

	/**
	 * Indicates whether the buffer is writable: true until closed or detached.
	 * @return bool Whether the stream is writable.
	 */
	public function isWritable(): bool
	{
		return !$this->_detached;
	}

	/**
	 * Indicates whether the buffer is readable: true until closed or detached.
	 * @return bool Whether the stream is readable.
	 */
	public function isReadable(): bool
	{
		return !$this->_detached;
	}

	/**
	 * Indicates the buffer is not seekable.
	 * @return bool Always false.
	 */
	public function isSeekable(): bool
	{
		return false;
	}

	/**
	 * Throws, as a FIFO buffer cannot report a position.
	 * @throws \RuntimeException Always.
	 */
	public function tell(): int
	{
		throw new \RuntimeException('A TBufferStream has no fixed position');
	}

	/**
	 * Throws, as a FIFO buffer cannot seek.
	 * @param int $offset The stream offset (unused).
	 * @param int $whence The whence (unused).
	 * @throws \RuntimeException Always.
	 */
	public function seek(int $offset, int $whence = SEEK_SET): void
	{
		throw new \RuntimeException('A TBufferStream cannot seek');
	}

	/**
	 * Throws, as a FIFO buffer cannot rewind.
	 * @throws \RuntimeException Always.
	 */
	public function rewind(): void
	{
		throw new \RuntimeException('A TBufferStream cannot rewind');
	}

	/**
	 * Returns stream metadata, of which a buffer has none.
	 * @param ?string $key A specific metadata key, or null for the whole array.
	 * @return mixed An empty array, or null for a specific key.
	 */
	public function getMetadata(?string $key = null): mixed
	{
		return $key === null ? [] : null;
	}
}
