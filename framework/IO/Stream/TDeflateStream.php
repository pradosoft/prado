<?php

/**
 * TDeflateStream class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Stream;

use Prado\Exceptions\TIOException;
use Psr\Http\Message\StreamInterface;

/**
 * TDeflateStream class.
 *
 * A write-side decorator that compresses the bytes written to it with {@see deflate_add()} and
 * forwards the compressed bytes to the inner stream.  This is the natural direction for deflate:
 * write plain bytes, the sink receives compressed ones (the counterpart of {@see TInflateStream},
 * which decompresses on read).  The encoding selects the wrapper: {@see ZLIB_ENCODING_DEFLATE}
 * (zlib, default), {@see ZLIB_ENCODING_GZIP} (gzip), or {@see ZLIB_ENCODING_RAW} (headerless).
 * Writes use ZLIB_NO_FLUSH by default, so the compressor buffers across calls for the best ratio
 * and emits on {@see close()}.  Enable {@see setSyncFlush() SyncFlush} to flush each write
 * (ZLIB_SYNC_FLUSH) so a reader can decode it immediately; that streams incrementally at a real cost
 * to ratio (PHP's deflate cannot flush its buffer without input, so flushing is per write, not on
 * demand).
 *
 * {@see close()} (or {@see detach()}) MUST be called to emit the final deflate block — and, for
 * gzip, the trailing CRC and length.  Without it the compressed output is truncated and will not
 * decode.  The stream is write-only and forward-only: {@see read()}/{@see seek()} throw and
 * {@see getSize()} is null.
 *
 * ```php
 * $gz = new TDeflateStream(TStream::fromFile('page.gz', 'wb'), ZLIB_ENCODING_GZIP);
 * $gz->write($html);
 * $gz->close();   // flushes the final block and closes the file
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TDeflateStream extends TStreamDecorator implements IStreamDecoratorPooling
{
	/** @var int The zlib encoding (a ZLIB_ENCODING_* constant). */
	private int $_encoding;

	/** @var ?\DeflateContext The deflate context, created on first write. */
	private ?\DeflateContext $_context = null;

	/** @var bool Whether the final block has been flushed. */
	private bool $_finished = false;

	/** @var bool Whether each write is flushed (ZLIB_SYNC_FLUSH) for streaming, rather than buffered. */
	private bool $_syncFlush = false;

	/**
	 * @param ?StreamInterface $stream The inner stream the compressed bytes are written to.
	 * @param int $encoding The zlib encoding (a ZLIB_ENCODING_* constant). Default {@see ZLIB_ENCODING_DEFLATE}.
	 */
	public function __construct(?StreamInterface $stream = null, int $encoding = ZLIB_ENCODING_DEFLATE)
	{
		$this->_encoding = $encoding;
		parent::__construct($stream);
	}

	/**
	 * Returns the stream to a fresh state so it can compress a new input, for reuse from a pool.
	 * The codec context and finished flag are cleared; an inner stream replaces the current one when
	 * given.  Any unflushed data on the previous inner stream is abandoned, so {@see close()} the
	 * prior output first when it must be complete.
	 * @param ?StreamInterface $stream The new inner stream to write compressed bytes to, or null to keep the current one.
	 * @return static The recycled stream.
	 */
	public function recycle(?StreamInterface $stream = null): static
	{
		if ($stream !== null) {
			$this->setStreamDirect($stream);
		}
		$this->resetState();
		return $this;
	}

	/**
	 * Clears the state and unbinds the inner stream, the inverse of {@see recycle()}.  The pending
	 * compression is abandoned without a final block, so {@see close()} the prior output first when
	 * it must be complete.
	 * @return ?StreamInterface The released inner stream, or null when none was bound.
	 */
	public function release(): ?StreamInterface
	{
		$this->resetState();
		return $this->clearStreamDirect();
	}

	/**
	 * Clears the encode state back to a fresh deflate, keeping the {@see getSyncFlush() SyncFlush} mode.
	 */
	private function resetState(): void
	{
		$this->_context = null;
		$this->_finished = false;
	}

	/**
	 * Compresses the bytes and writes the compressed output to the inner stream.
	 * @param string $string The plain bytes to compress.
	 * @throws TIOException When the deflate context cannot be created.
	 * @throws \RuntimeException When called after the stream is closed.
	 * @return int The number of plain bytes accepted.
	 */
	public function write(string $string): int
	{
		if ($this->_finished) {
			throw new \RuntimeException('Cannot write to a deflate stream after it is closed');
		}
		// NO_FLUSH buffers across writes for the best ratio (emitted on close); SyncFlush makes each
		// write self-contained and decodable now, at a cost to ratio.
		$out = deflate_add($this->context(), $string, $this->_syncFlush ? ZLIB_SYNC_FLUSH : ZLIB_NO_FLUSH);
		if ($out !== '') {
			$this->getStream()->write($out);
		}
		return strlen($string);
	}

	/**
	 * Returns the deflate context, creating it on first use.
	 * @throws TIOException When the deflate context cannot be created.
	 * @return \DeflateContext The deflate context.
	 */
	private function context(): \DeflateContext
	{
		if ($this->_context === null) {
			$context = deflate_init($this->_encoding);
			if ($context === false) {
				throw new TIOException('deflatestream_init_failed', $this->_encoding);
			}
			$this->_context = $context;
		}
		return $this->_context;
	}

	/**
	 * Returns whether each write is flushed for streaming.
	 * @return bool Whether writes use ZLIB_SYNC_FLUSH rather than ZLIB_NO_FLUSH.
	 */
	public function getSyncFlush(): bool
	{
		return $this->_syncFlush;
	}

	/**
	 * Sets whether each write is flushed (ZLIB_SYNC_FLUSH) so a reader can decode it immediately.
	 * The default false buffers (ZLIB_NO_FLUSH) for the best ratio, emitting on {@see close()}.
	 * PHP cannot flush the deflate buffer without input, so this flushes per write rather than on
	 * demand.
	 * @param bool $value Whether to flush each write.
	 * @return static The current stream.
	 */
	public function setSyncFlush(bool $value): static
	{
		$this->_syncFlush = $value;
		return $this;
	}

	/**
	 * Flushes the final deflate block and closes the inner stream.
	 */
	public function close(): void
	{
		$this->finish();
		$this->getStream()->close();
	}

	/**
	 * Flushes the final deflate block and detaches the inner stream's resource.
	 * @return null|resource The detached resource, or null when none is present.
	 */
	public function detach()
	{
		$this->finish();
		return $this->getStream()->detach();
	}

	/**
	 * Writes the final deflate block (and, for gzip, the trailer) to the inner stream, once.  It runs
	 * even when nothing was written, so closing an empty deflate stream emits a valid, decodable
	 * (empty) compressed stream rather than no bytes at all.
	 */
	protected function finish(): void
	{
		if ($this->_finished) {
			return;
		}
		$this->_finished = true;
		$this->getStream()->write(deflate_add($this->context(), '', ZLIB_FINISH));
		$this->_context = null;
	}

	/**
	 * Reports the stream as not readable.
	 * @return bool Always false.
	 */
	public function isReadable(): bool
	{
		return false;
	}

	/**
	 * Reports the stream as not seekable.
	 * @return bool Always false.
	 */
	public function isSeekable(): bool
	{
		return false;
	}

	/**
	 * Rejects any read, as the deflate stream is write-only.
	 * @param int $length The number of bytes to read (unused).
	 * @throws \RuntimeException Always.
	 */
	public function read(int $length): string
	{
		throw new \RuntimeException('Cannot read from a deflate (write) stream');
	}

	/**
	 * Rejects any seek, as the deflate stream is forward-only.
	 * @param int $offset The stream offset (unused).
	 * @param int $whence SEEK_SET, SEEK_CUR, or SEEK_END (unused).
	 * @throws \RuntimeException Always.
	 */
	public function seek(int $offset, int $whence = SEEK_SET): void
	{
		throw new \RuntimeException('Cannot seek a deflate stream');
	}

	/**
	 * Returns null; the compressed size is not known ahead of time.
	 * @return ?int Always null.
	 */
	public function getSize(): ?int
	{
		return null;
	}

	/**
	 * Returns ''; a write-only stream has no string representation.
	 * @return string An empty string.
	 */
	public function __toString(): string
	{
		return '';
	}
}
