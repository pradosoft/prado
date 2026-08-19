<?php

/**
 * TAppendStream class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Stream;

use Prado\TComponent;
use Psr\Http\Message\StreamInterface;

/**
 * TAppendStream class.
 *
 * Reads several streams in sequence as if they were one, moving to the next stream when
 * the current one ends.  It is read-only and useful for joining parts (a header stream
 * and a body stream) without copying them into a single buffer.
 *
 * The combined stream is seekable only when every appended stream is seekable; in that
 * case a seek is supported to the absolute start (offset 0), which rewinds each part.
 * {@see getSize()} is the sum of the parts' sizes, or null when any size is unknown.
 * {@see close()} and {@see detach()} release every part and end the stream's life: a
 * closed sequence reports {@see isReadable()} false and its reads throw, per the PSR-7
 * detached-stream contract.
 *
 * ```php
 * $all = new TAppendStream([TStream::fromString('HEAD'), TStream::fromString('BODY')]);
 * echo (string) $all;   // HEADBODY
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TAppendStream extends TComponent implements StreamInterface
{
	/** @var int The default chunk size used when draining the stream a piece at a time. */
	public const CHUNK_SIZE = 8192;

	/** @var array<int, StreamInterface> The appended streams in order. */
	private array $_streams = [];

	/** @var int The index of the stream currently being read. */
	private int $_current = 0;

	/** @var int The number of bytes read so far. */
	private int $_position = 0;

	/** @var bool Whether all appended streams are seekable. */
	private bool $_seekable = true;

	/** @var bool Whether the stream is closed or detached, ending its life. */
	private bool $_detached = false;

	/**
	 * @param array<int, StreamInterface> $streams The streams to append, in order.
	 */
	public function __construct(array $streams = [])
	{
		parent::__construct();
		foreach ($streams as $stream) {
			$this->add($stream);
		}
	}

	//
	// ─── Self-encapsulated raw accessors ─────────────────────────────────────
	//

	/**
	 * Returns the raw appended-stream list.
	 * @return array<int, StreamInterface> The raw appended-stream list.
	 */
	protected function getStreamsDirect(): array
	{
		return $this->_streams;
	}

	/**
	 * Sets the raw appended-stream list.
	 * @param array<int, StreamInterface> $value The raw appended-stream list.
	 */
	protected function setStreamsDirect(array $value): void
	{
		$this->_streams = $value;
	}

	/**
	 * Returns the raw index of the current stream.
	 * @return int The raw current index.
	 */
	protected function getCurrentDirect(): int
	{
		return $this->_current;
	}

	/**
	 * Sets the raw index of the current stream.
	 * @param int $value The raw current index.
	 */
	protected function setCurrentDirect(int $value): void
	{
		$this->_current = $value;
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
	 * Returns the raw seekable flag.
	 * @return bool The raw seekable flag.
	 */
	protected function getSeekableDirect(): bool
	{
		return $this->_seekable;
	}

	/**
	 * Sets the raw seekable flag.
	 * @param bool $value The raw seekable flag.
	 */
	protected function setSeekableDirect(bool $value): void
	{
		$this->_seekable = $value;
	}

	/**
	 * Appends a stream to the sequence.
	 * @param StreamInterface $stream The readable stream to append.
	 * @throws \InvalidArgumentException When the stream is not readable.
	 * @return static This instance, for chaining.
	 */
	public function add(StreamInterface $stream): static
	{
		if (!$stream->isReadable()) {
			throw new \InvalidArgumentException('Each TAppendStream part must be readable');
		}
		if (!$stream->isSeekable()) {
			$this->setSeekableDirect(false);
		}
		$streams = $this->getStreamsDirect();
		$streams[] = $stream;
		$this->setStreamsDirect($streams);
		return $this;
	}

	/**
	 * Returns the appended streams in order.
	 * @return array<int, StreamInterface> The streams.
	 */
	public function getStreams(): array
	{
		return $this->getStreamsDirect();
	}

	/**
	 * Reads the whole sequence into a string.  A read failure (or a detached stream) yields '',
	 * since PSR-7 forbids __toString to throw.
	 * @return string The concatenated contents, or '' on failure.
	 */
	public function __toString(): string
	{
		try {
			if ($this->getSeekableDirect()) {
				$this->rewind();
			}
			return $this->getContents();
		} catch (\Throwable $e) {
			return '';
		}
	}

	/**
	 * Reads up to $length bytes, crossing from one part to the next at its end.
	 * @param int $length The maximum number of bytes to read.
	 * @throws \RuntimeException When the stream is closed or detached.
	 * @return string The bytes read; '' at the end of the last stream.
	 */
	public function read(int $length): string
	{
		if ($this->_detached) {
			throw new \RuntimeException('Cannot read from a detached TAppendStream');
		}
		$data = '';
		$remaining = $length;
		$streams = $this->getStreamsDirect();
		while ($remaining > 0 && $this->getCurrentDirect() < count($streams)) {
			$stream = $streams[$this->getCurrentDirect()];
			$chunk = $stream->read($remaining);
			if ($chunk === '') {
				$this->setCurrentDirect($this->getCurrentDirect() + 1);
				continue;
			}
			$data .= $chunk;
			$remaining -= strlen($chunk);
		}
		$this->setPositionDirect($this->getPositionDirect() + strlen($data));
		return $data;
	}

	/**
	 * Returns the remaining contents across all parts.
	 * @throws \RuntimeException When the stream is closed or detached.
	 * @return string The remaining contents.
	 */
	public function getContents(): string
	{
		if ($this->_detached) {
			throw new \RuntimeException('Cannot read from a detached TAppendStream');
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
	 * Returns the total size of the sequence, or null once the stream is closed or detached.
	 * @return ?int The sum of the parts' sizes, or null when any is unknown.
	 */
	public function getSize(): ?int
	{
		if ($this->_detached) {
			return null;
		}
		$total = 0;
		foreach ($this->getStreamsDirect() as $stream) {
			$size = $stream->getSize();
			if ($size === null) {
				return null;
			}
			$total += $size;
		}
		return $total;
	}

	/**
	 * Returns the number of bytes read so far.
	 * @return int The position.
	 */
	public function tell(): int
	{
		return $this->getPositionDirect();
	}

	/**
	 * Indicates whether the last stream has been fully read.
	 * @return bool Whether at the end of the sequence.
	 */
	public function eof(): bool
	{
		$streams = $this->getStreamsDirect();
		if ($this->getCurrentDirect() >= count($streams)) {
			return true;
		}
		return $this->getCurrentDirect() === count($streams) - 1 && $streams[$this->getCurrentDirect()]->eof();
	}

	/**
	 * Indicates whether the sequence is seekable (only when all parts are).
	 * @return bool Whether seekable.
	 */
	public function isSeekable(): bool
	{
		return $this->getSeekableDirect();
	}

	/**
	 * Seeks within the sequence; only an absolute seek to the start is supported.
	 * @param int $offset The target offset (must be 0).
	 * @param int $whence SEEK_SET.
	 * @throws \RuntimeException When not seekable or the target is unsupported.
	 */
	public function seek(int $offset, int $whence = SEEK_SET): void
	{
		if (!$this->getSeekableDirect()) {
			throw new \RuntimeException('This TAppendStream is not seekable');
		}
		if ($offset !== 0 || $whence !== SEEK_SET) {
			throw new \RuntimeException('A TAppendStream can only seek to its start');
		}
		foreach ($this->getStreamsDirect() as $stream) {
			$stream->rewind();
		}
		$this->setCurrentDirect(0);
		$this->setPositionDirect(0);
	}

	/**
	 * Seeks to the start of the sequence.
	 */
	public function rewind(): void
	{
		$this->seek(0);
	}

	/**
	 * Indicates whether the sequence is readable: true until it is closed or detached.
	 * @return bool Whether the sequence is readable.
	 */
	public function isReadable(): bool
	{
		return !$this->_detached;
	}

	/**
	 * Indicates the sequence is not writable.
	 * @return bool Always false.
	 */
	public function isWritable(): bool
	{
		return false;
	}

	/**
	 * Throws, as an append stream is not writable.
	 * @param string $string The bytes (unused).
	 * @throws \RuntimeException Always.
	 */
	public function write(string $string): int
	{
		throw new \RuntimeException('Cannot write to a TAppendStream');
	}

	/**
	 * Closes every appended stream and ends the sequence's life.
	 */
	public function close(): void
	{
		$this->_detached = true;
		foreach ($this->getStreamsDirect() as $stream) {
			$stream->close();
		}
		$this->setStreamsDirect([]);
		$this->setCurrentDirect(0);
		$this->setPositionDirect(0);
	}

	/**
	 * Detaches every appended stream and ends the sequence's life.
	 * @return null Always null; the sequence has no single resource.
	 */
	public function detach()
	{
		$this->_detached = true;
		foreach ($this->getStreamsDirect() as $stream) {
			$stream->detach();
		}
		$this->setStreamsDirect([]);
		$this->setCurrentDirect(0);
		$this->setPositionDirect(0);
		return null;
	}

	/**
	 * Returns null metadata; the sequence has none of its own.
	 * @param ?string $key A specific metadata key, or null for the whole array.
	 * @return mixed An empty array, or null for a specific key.
	 */
	public function getMetadata(?string $key = null): mixed
	{
		return $key === null ? [] : null;
	}
}
