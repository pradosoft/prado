<?php

/**
 * TPumpStream class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Stream;

use Prado\TComponent;
use Psr\Http\Message\StreamInterface;

/**
 * TPumpStream class.
 *
 * Presents a read-only stream whose bytes are produced on demand by a source callable.
 * Each read calls the source for more data; bytes returned beyond what the read asked
 * for are buffered for the next read.  The source signals end-of-stream by returning an
 * empty string, false, or null.
 *
 * It suits adapting a generator or a producer function to a {@see StreamInterface}
 * without materializing the whole output first.  The stream is not seekable.
 *
 * An exhausted source leaves the stream readable: further reads return '' at end of
 * stream.  {@see close()} and {@see detach()} end the stream's life instead: a closed or
 * detached pump reports {@see isReadable()} false, {@see getSize()} null, and its reads
 * throw, per the PSR-7 detached-stream contract.
 *
 * ```php
 * $counter = 0;
 * $pump = new TPumpStream(fn (int $n) => $counter++ < 3 ? "line{$counter}\n" : '');
 * echo $pump->getContents();   // line1\nline2\nline3\n
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TPumpStream extends TComponent implements StreamInterface
{
	/** @var int The default chunk size used when draining the stream a piece at a time. */
	public const CHUNK_SIZE = 8192;

	/** @var ?callable The source producing bytes on demand; null once exhausted/detached. */
	private $_source;

	/** @var ?int The declared size, or null when unknown. */
	private ?int $_size;

	/** @var string The bytes read from the source but not yet consumed. */
	private string $_buffer = '';

	/** @var int The number of bytes consumed so far. */
	private int $_position = 0;

	/** @var bool Whether the stream is closed or detached, ending its life. */
	private bool $_detached = false;

	/**
	 * @param callable $source A callable `fn(int $length): string|false|null` producing bytes.
	 * @param ?int $size The declared total size, or null when unknown.
	 */
	public function __construct(callable $source, ?int $size = null)
	{
		$this->setSourceDirect($source);
		$this->setSizeDirect($size);
		parent::__construct();
	}

	//
	// ─── Self-encapsulated raw accessors ─────────────────────────────────────
	//

	/**
	 * Returns the raw source callable.
	 * @return ?callable The raw source callable.
	 */
	protected function getSourceDirect(): ?callable
	{
		return $this->_source;
	}

	/**
	 * Sets the raw source callable.
	 * @param ?callable $value The raw source callable.
	 */
	protected function setSourceDirect(?callable $value): void
	{
		$this->_source = $value;
	}

	/**
	 * Returns the raw declared size.
	 * @return ?int The raw declared size.
	 */
	protected function getSizeDirect(): ?int
	{
		return $this->_size;
	}

	/**
	 * Sets the raw declared size.
	 * @param ?int $value The raw declared size.
	 */
	protected function setSizeDirect(?int $value): void
	{
		$this->_size = $value;
	}

	/**
	 * Returns the raw read-ahead buffer.
	 * @return string The raw read-ahead buffer.
	 */
	protected function getBufferDirect(): string
	{
		return $this->_buffer;
	}

	/**
	 * Sets the raw read-ahead buffer.
	 * @param string $value The raw read-ahead buffer.
	 */
	protected function setBufferDirect(string $value): void
	{
		$this->_buffer = $value;
	}

	/**
	 * Returns the raw position.
	 * @return int The raw position.
	 */
	protected function getPositionDirect(): int
	{
		return $this->_position;
	}

	/**
	 * Sets the raw position.
	 * @param int $value The raw position.
	 */
	protected function setPositionDirect(int $value): void
	{
		$this->_position = $value;
	}

	/**
	 * Returns the raw detached flag.
	 * @return bool The raw detached flag.
	 */
	protected function getDetachedDirect(): bool
	{
		return $this->_detached;
	}

	/**
	 * Sets the raw detached flag.
	 * @param bool $value The raw detached flag.
	 */
	protected function setDetachedDirect(bool $value): void
	{
		$this->_detached = $value;
	}

	/**
	 * Blocks unserialization: the source may be any callable, so a crafted serialized payload
	 * would choose what runs when the stream is read.
	 * @throws \LogicException Always.
	 */
	public function __wakeup()
	{
		throw new \LogicException('TPumpStream cannot be unserialized');
	}

	/**
	 * Reads the whole stream into a string.  An exception from the source (or a detached
	 * stream) yields '', since PSR-7 forbids __toString to throw.
	 * @return string The full contents, or '' on failure.
	 */
	public function __toString(): string
	{
		try {
			return $this->getContents();
		} catch (\Throwable $e) {
			return '';
		}
	}

	/**
	 * Reads up to $length bytes, pulling more from the source when the buffer is short.
	 * @param int $length The maximum number of bytes to read.
	 * @throws \RuntimeException When the stream is closed or detached.
	 * @return string The bytes read; '' at end of stream.
	 */
	public function read(int $length): string
	{
		if ($this->getDetachedDirect()) {
			throw new \RuntimeException('Cannot read from a detached TPumpStream');
		}
		if ($length <= 0) {
			return '';
		}
		while (strlen($this->getBufferDirect()) < $length && $this->getSourceDirect() !== null) {
			$chunk = ($this->getSourceDirect())($length - strlen($this->getBufferDirect()));
			if ($chunk === null || $chunk === false || $chunk === '') {
				$this->setSourceDirect(null);
				break;
			}
			$this->setBufferDirect($this->getBufferDirect() . $chunk);
		}
		$data = substr($this->getBufferDirect(), 0, $length);
		$this->setBufferDirect(substr($this->getBufferDirect(), strlen($data)));
		$this->setPositionDirect($this->getPositionDirect() + strlen($data));
		return $data;
	}

	/**
	 * Returns the remaining contents by pumping the source to exhaustion.
	 * @throws \RuntimeException When the stream is closed or detached.
	 * @return string The remaining contents.
	 */
	public function getContents(): string
	{
		if ($this->getDetachedDirect()) {
			throw new \RuntimeException('Cannot read from a detached TPumpStream');
		}
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
	 * Indicates end of stream: the source is exhausted and the buffer is drained, or the
	 * stream is closed or detached.
	 * @return bool Whether no more bytes are available.
	 */
	public function eof(): bool
	{
		return $this->getDetachedDirect() || ($this->getSourceDirect() === null && $this->getBufferDirect() === '');
	}

	/**
	 * Returns the declared size, or null once the stream is closed or detached.
	 * @return ?int The size, or null when unknown.
	 */
	public function getSize(): ?int
	{
		return $this->getDetachedDirect() ? null : $this->getSizeDirect();
	}

	/**
	 * Returns the number of bytes consumed so far.
	 * @return int The position.
	 */
	public function tell(): int
	{
		return $this->getPositionDirect();
	}

	/**
	 * Detaches the source, ending the stream's life.
	 * @return null Always null; the pump has no resource.
	 */
	public function detach()
	{
		$this->setDetachedDirect(true);
		$this->setSourceDirect(null);
		$this->setBufferDirect('');
		return null;
	}

	/**
	 * Releases the source, ending the stream's life.
	 */
	public function close(): void
	{
		$this->detach();
	}

	/**
	 * Indicates whether the stream is readable: true until it is closed or detached.
	 * @return bool Whether the stream is readable.
	 */
	public function isReadable(): bool
	{
		return !$this->getDetachedDirect();
	}

	/**
	 * Indicates the stream is not writable.
	 * @return bool Always false.
	 */
	public function isWritable(): bool
	{
		return false;
	}

	/**
	 * Indicates the stream is not seekable.
	 * @return bool Always false.
	 */
	public function isSeekable(): bool
	{
		return false;
	}

	/**
	 * Throws, as a pump stream is not writable.
	 * @param string $string The bytes (unused).
	 * @throws \RuntimeException Always.
	 */
	public function write(string $string): int
	{
		throw new \RuntimeException('Cannot write to a TPumpStream');
	}

	/**
	 * Throws, as a pump stream is not seekable.
	 * @param int $offset The offset (unused).
	 * @param int $whence The whence (unused).
	 * @throws \RuntimeException Always.
	 */
	public function seek(int $offset, int $whence = SEEK_SET): void
	{
		throw new \RuntimeException('Cannot seek a TPumpStream');
	}

	/**
	 * Throws, as a pump stream cannot rewind.
	 * @throws \RuntimeException Always.
	 */
	public function rewind(): void
	{
		throw new \RuntimeException('Cannot rewind a TPumpStream');
	}

	/**
	 * Returns null metadata; a pump stream has none.
	 * @param ?string $key A specific metadata key, or null for the whole array.
	 * @return mixed An empty array, or null for a specific key.
	 */
	public function getMetadata(?string $key = null): mixed
	{
		return $key === null ? [] : null;
	}
}
