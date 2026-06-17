<?php

/**
 * TStreamDecorator class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Stream;

use Prado\TComponent;
use Psr\Http\Message\StreamInterface;

/**
 * TStreamDecorator class.
 *
 * Wraps a PSR-7 {@see StreamInterface} and forwards every method to it.  Subclasses
 * override only the methods whose behavior they change, which keeps a decorator focused
 * on its one concern (a read window, a cache, and so on).
 *
 * The decorator is itself a {@see StreamInterface}, so it composes: a caching decorator
 * may wrap a limit decorator that wraps a socket stream.  {@see __toString()} rewinds
 * and returns the full contents, matching PSR-7.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
abstract class TStreamDecorator extends TComponent implements StreamInterface
{
	/** @var int The default chunk size used when draining the stream a piece at a time. */
	public const CHUNK_SIZE = 8192;

	/** @var StreamInterface The wrapped inner stream. */
	private StreamInterface $_stream;

	// =========================================================================
	// Construction
	// =========================================================================

	/**
	 * @param ?StreamInterface $stream The inner stream to decorate; null defers it to a
	 *   subclass that builds the inner stream lazily by overriding {@see getStream()}.
	 */
	public function __construct(?StreamInterface $stream = null)
	{
		if ($stream !== null) {
			$this->setStreamDirect($stream);
		}
		parent::__construct();
	}

	// =========================================================================
	// Self-encapsulated raw accessors
	// =========================================================================

	/**
	 * Returns the inner stream.  Subclasses may override this to build it on first use.
	 * Every forwarding method reads the inner stream through this accessor, so an
	 * override is honored uniformly.
	 * @return StreamInterface The wrapped inner stream.
	 */
	public function getStream(): StreamInterface
	{
		return $this->_stream;
	}

	/**
	 * Sets the raw inner stream.
	 * @param StreamInterface $value The raw inner stream.
	 */
	protected function setStreamDirect(StreamInterface $value): void
	{
		$this->_stream = $value;
	}

	// =========================================================================
	// Stream Interface
	// =========================================================================

	/**
	 * Reads the entire stream into a string from the beginning.
	 * A read failure propagates.
	 * @return string The full stream contents.
	 */
	public function __toString(): string
	{
		if ($this->isSeekable()) {
			$this->seek(0);
		}
		return $this->getContents();
	}

	/**
	 * Closes the underlying stream.
	 */
	public function close(): void
	{
		$this->getStream()->close();
	}

	/**
	 * Detaches the underlying stream resource.
	 * @return null|resource The detached resource, or null when none is present.
	 */
	public function detach()
	{
		return $this->getStream()->detach();
	}

	/**
	 * Returns the size of the underlying stream.
	 * @return ?int The size in bytes, or null when unknown.
	 */
	public function getSize(): ?int
	{
		return $this->getStream()->getSize();
	}

	/**
	 * Returns the current position of the underlying stream.
	 * @return int The position in bytes.
	 */
	public function tell(): int
	{
		return $this->getStream()->tell();
	}

	/**
	 * Indicates whether the underlying stream is at its end.
	 * @return bool True at the end of the stream.
	 */
	public function eof(): bool
	{
		return $this->getStream()->eof();
	}

	/**
	 * Indicates whether the underlying stream is seekable.
	 * @return bool True when seekable.
	 */
	public function isSeekable(): bool
	{
		return $this->getStream()->isSeekable();
	}

	/**
	 * Seeks to a position in the underlying stream.
	 * @param int $offset The stream offset.
	 * @param int $whence SEEK_SET, SEEK_CUR, or SEEK_END.
	 */
	public function seek(int $offset, int $whence = SEEK_SET): void
	{
		$this->getStream()->seek($offset, $whence);
	}

	/**
	 * Seeks to the beginning of the underlying stream.
	 */
	public function rewind(): void
	{
		$this->getStream()->rewind();
	}

	/**
	 * Indicates whether the underlying stream is writable.
	 * @return bool True when writable.
	 */
	public function isWritable(): bool
	{
		return $this->getStream()->isWritable();
	}

	/**
	 * Writes data to the underlying stream.
	 * @param string $string The bytes to write.
	 * @return int The number of bytes written.
	 */
	public function write(string $string): int
	{
		return $this->getStream()->write($string);
	}

	/**
	 * Indicates whether the underlying stream is readable.
	 * @return bool True when readable.
	 */
	public function isReadable(): bool
	{
		return $this->getStream()->isReadable();
	}

	/**
	 * Reads up to $length bytes from the underlying stream.
	 * @param int $length The maximum number of bytes to read.
	 * @return string The bytes read.
	 */
	public function read(int $length): string
	{
		return $this->getStream()->read($length);
	}

	/**
	 * Returns the remaining contents by reading through this decorator's {@see read()}.
	 * Reading via the polymorphic read keeps subclass behavior (windowing, caching) in effect.
	 * @return string The remaining contents.
	 */
	public function getContents(): string
	{
		$contents = '';
		while (!$this->eof()) {
			$chunk = $this->read(static::CHUNK_SIZE);
			if ($chunk === '') {
				break;
			}
			$contents .= $chunk;
		}
		return $contents;
	}

	/**
	 * Returns stream metadata from the underlying stream.
	 * @param ?string $key A specific metadata key, or null for the whole array.
	 * @return mixed The metadata value, the whole array, or null when the key is absent.
	 */
	public function getMetadata(?string $key = null): mixed
	{
		return $this->getStream()->getMetadata($key);
	}
}
