<?php

/**
 * TTestPsrStream class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Psr\Http\Message\StreamInterface;

/**
 * TTestPsrStream is a dependency-free, in-memory PSR-7 {@see StreamInterface} double.
 *
 * It backs an internal string buffer and a position pointer, implementing the whole
 * PSR-7 stream contract without {@see \Prado\IO\TStream} or any PHP stream resource.
 * Tests for code that consumes a StreamInterface use it to prove the consumer honors
 * the interface itself rather than a specific implementation.
 *
 * The capability flags are fixed at construction:
 *
 * - $readable / $writable / $seekable control what {@see isReadable()},
 *   {@see isWritable()}, and {@see isSeekable()} report, so a test can build a
 *   read-only, write-only, or non-seekable stream on demand.
 *
 * {@see read()}, {@see write()}, and {@see seek()} throw {@see \RuntimeException} when
 * the matching capability is absent, matching the PSR-7 contract.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestPsrStream implements StreamInterface
{
	/** @var string The backing buffer. */
	private string $buffer;

	/** @var int The read/write position. */
	private int $pos = 0;

	/** @var bool Whether the stream reports readable. */
	private bool $readable;

	/** @var bool Whether the stream reports writable. */
	private bool $writable;

	/** @var bool Whether the stream reports seekable. */
	private bool $seekable;

	/** @var bool Whether the stream has been detached or closed. */
	private bool $detached = false;

	/**
	 * @param string $contents The initial buffer contents.
	 * @param bool $readable Whether the stream is readable. Default true.
	 * @param bool $writable Whether the stream is writable. Default true.
	 * @param bool $seekable Whether the stream is seekable. Default true.
	 */
	public function __construct(string $contents = '', bool $readable = true, bool $writable = true, bool $seekable = true)
	{
		$this->buffer = $contents;
		$this->readable = $readable;
		$this->writable = $writable;
		$this->seekable = $seekable;
	}

	/**
	 * Reads the whole buffer to a string from the start.
	 * @return string The entire buffer, or '' when detached.
	 */
	public function __toString(): string
	{
		if ($this->detached) {
			return '';
		}
		$this->pos = 0;
		return $this->buffer;
	}

	/**
	 * Closes the stream, discarding the buffer.
	 */
	public function close(): void
	{
		$this->detached = true;
		$this->buffer = '';
	}

	/**
	 * Detaches the stream. No PHP resource backs it, so this returns null.
	 * @return null Always null.
	 */
	public function detach()
	{
		$this->detached = true;
		return null;
	}

	/**
	 * Returns the buffer length in bytes.
	 * @return ?int The size, or null when detached.
	 */
	public function getSize(): ?int
	{
		return $this->detached ? null : strlen($this->buffer);
	}

	/**
	 * Returns the current position.
	 * @throws \RuntimeException When the stream is detached.
	 * @return int The current position.
	 */
	public function tell(): int
	{
		if ($this->detached) {
			throw new \RuntimeException('Stream is detached');
		}
		return $this->pos;
	}

	/**
	 * Reports whether the position is at or past the end of the buffer.
	 * @return bool Whether the stream is at end of file.
	 */
	public function eof(): bool
	{
		return $this->detached || $this->pos >= strlen($this->buffer);
	}

	/**
	 * Reports the seekable capability.
	 * @return bool Whether the stream is seekable.
	 */
	public function isSeekable(): bool
	{
		return !$this->detached && $this->seekable;
	}

	/**
	 * Moves the position relative to $whence.
	 * @param int $offset The seek offset.
	 * @param int $whence SEEK_SET, SEEK_CUR or SEEK_END.
	 * @throws \RuntimeException When the stream is not seekable.
	 */
	public function seek(int $offset, int $whence = SEEK_SET): void
	{
		if (!$this->isSeekable()) {
			throw new \RuntimeException('Stream is not seekable');
		}
		$base = match ($whence) {
			SEEK_CUR => $this->pos,
			SEEK_END => strlen($this->buffer),
			default => 0,
		};
		$this->pos = max(0, $base + $offset);
	}

	/**
	 * Moves the position to the start of the buffer.
	 * @throws \RuntimeException When the stream is not seekable.
	 */
	public function rewind(): void
	{
		$this->seek(0);
	}

	/**
	 * Reports the writable capability.
	 * @return bool Whether the stream is writable.
	 */
	public function isWritable(): bool
	{
		return !$this->detached && $this->writable;
	}

	/**
	 * Writes bytes at the current position, growing the buffer as needed.
	 * @param string $string The bytes to write.
	 * @throws \RuntimeException When the stream is not writable.
	 * @return int The number of bytes written.
	 */
	public function write(string $string): int
	{
		if (!$this->isWritable()) {
			throw new \RuntimeException('Stream is not writable');
		}
		$length = strlen($string);
		if ($this->pos > strlen($this->buffer)) {
			$this->buffer = str_pad($this->buffer, $this->pos, "\0");
		}
		$this->buffer = substr($this->buffer, 0, $this->pos) . $string . substr($this->buffer, $this->pos + $length);
		$this->pos += $length;
		return $length;
	}

	/**
	 * Reports the readable capability.
	 * @return bool Whether the stream is readable.
	 */
	public function isReadable(): bool
	{
		return !$this->detached && $this->readable;
	}

	/**
	 * Reads up to $length bytes from the current position.
	 * @param int $length The maximum number of bytes to read.
	 * @throws \RuntimeException When the stream is not readable.
	 * @return string The bytes read (may be shorter than $length, '' at EOF).
	 */
	public function read(int $length): string
	{
		if (!$this->isReadable()) {
			throw new \RuntimeException('Stream is not readable');
		}
		$data = substr($this->buffer, $this->pos, $length);
		$this->pos += strlen($data);
		return $data;
	}

	/**
	 * Returns the buffer from the current position to the end.
	 * @throws \RuntimeException When the stream is not readable.
	 * @return string The remaining contents.
	 */
	public function getContents(): string
	{
		if (!$this->isReadable()) {
			throw new \RuntimeException('Stream is not readable');
		}
		$data = substr($this->buffer, $this->pos);
		$this->pos = strlen($this->buffer);
		return $data;
	}

	/**
	 * Returns minimal stream metadata.
	 * @param ?string $key A single metadata key, or null for the whole array.
	 * @return mixed The metadata array, one value, or null.
	 */
	public function getMetadata(?string $key = null): mixed
	{
		$meta = [
			'seekable' => $this->isSeekable(),
			'eof' => $this->eof(),
			'mode' => $this->writable ? ($this->readable ? 'r+b' : 'wb') : 'rb',
			'unread_bytes' => max(0, strlen($this->buffer) - $this->pos),
		];
		if ($key === null) {
			return $meta;
		}
		return $meta[$key] ?? null;
	}
}
