<?php

/**
 * TInflateStream class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Stream;

use Prado\Exceptions\TIOException;
use Psr\Http\Message\StreamInterface;

/**
 * TInflateStream class.
 *
 * A read-side decorator that decompresses an inner stream of zlib-compressed bytes with
 * {@see inflate_add()}, yielding the plain bytes on read.  This is the natural direction for
 * inflate: read a compressed source, get plaintext (the counterpart of {@see TDeflateStream},
 * which compresses on the write side).  The encoding selects the wrapper: {@see ZLIB_ENCODING_DEFLATE}
 * (zlib, default), {@see ZLIB_ENCODING_GZIP} (gzip `Content-Encoding`), or {@see ZLIB_ENCODING_RAW}
 * (headerless).
 *
 * It pulls and inflates the inner stream a chunk at a time and buffers the output so {@see read()}
 * can return any requested length.  The decompressed length is unknown ahead of time, so the stream
 * is forward-only: {@see getSize()} is null and {@see seek()}/{@see write()} throw.  {@see recycle()}
 * returns it to a fresh state (optionally over a new inner stream) so it can be reused from a pool.
 *
 * ```php
 * $plain = (new TInflateStream(TStream::fromFile('page.gz', 'rb'), ZLIB_ENCODING_GZIP))->getContents();
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TInflateStream extends TStreamDecorator implements IStreamDecoratorPooling
{
	/** @var int The zlib encoding (a ZLIB_ENCODING_* constant). */
	private int $_encoding;

	/** @var ?\InflateContext The inflate context, created on first read. */
	private ?\InflateContext $_context = null;

	/** @var bool Whether the inner input has been fully consumed. */
	private bool $_inputDone = false;

	/** @var string The decompressed bytes awaiting a read (consumed up to {@see $_bufferPos}). */
	private string $_buffer = '';

	/** @var int The offset of the first unread byte in {@see $_buffer}. */
	private int $_bufferPos = 0;

	/** @var bool Whether the transform has produced its final bytes. */
	private bool $_finished = false;

	/** @var int The number of decompressed bytes read so far. */
	private int $_position = 0;

	/**
	 * @param ?StreamInterface $stream The inner stream of compressed bytes.
	 * @param int $encoding The zlib encoding (a ZLIB_ENCODING_* constant). Default {@see ZLIB_ENCODING_DEFLATE}.
	 */
	public function __construct(?StreamInterface $stream = null, int $encoding = ZLIB_ENCODING_DEFLATE)
	{
		$this->_encoding = $encoding;
		parent::__construct($stream);
	}

	/**
	 * Returns the stream to a fresh state so it can decompress a new input, for reuse from a pool.
	 * The codec context and buffers are cleared; an inner stream replaces the current one when given.
	 * @param ?StreamInterface $stream The new inner stream of compressed bytes, or null to keep the current one.
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
	 * Clears the state and unbinds the inner stream, the inverse of {@see recycle()}.
	 * @return ?StreamInterface The released inner stream, or null when none was bound.
	 */
	public function release(): ?StreamInterface
	{
		$this->resetState();
		return $this->clearStreamDirect();
	}

	/**
	 * Clears the decode state back to a fresh inflate.
	 */
	private function resetState(): void
	{
		$this->_context = null;
		$this->_inputDone = false;
		$this->_buffer = '';
		$this->_bufferPos = 0;
		$this->_finished = false;
		$this->_position = 0;
	}

	/**
	 * Reads up to $length decompressed bytes, pulling and inflating inner data as needed.
	 * @param int $length The maximum number of bytes to read.
	 * @return string The decompressed bytes; '' once the input is exhausted.
	 */
	public function read(int $length): string
	{
		if ($length <= 0) {
			return '';
		}
		// Read from a moving offset rather than re-slicing the remainder on every read; that drain
		// is O(n) per call and goes quadratic when reads are smaller than the inflated chunks.
		$buffer = $this->_buffer;
		$pos = $this->_bufferPos;
		$available = strlen($buffer) - $pos;
		while (!$this->_finished && $available < $length) {
			$chunk = $this->inflateChunk();
			if ($chunk === null) {
				$this->_finished = true;
				break;
			}
			$buffer .= $chunk;
			$available += strlen($chunk);
		}
		$take = $length < $available ? $length : $available;
		$data = substr($buffer, $pos, $take);
		$pos += $take;
		if ($pos >= static::CHUNK_SIZE && $pos >= strlen($buffer) - $pos) {   // reclaim the consumed prefix once it dominates
			$buffer = substr($buffer, $pos);
			$pos = 0;
		}
		$this->_buffer = $buffer;
		$this->_bufferPos = $pos;
		$this->_position += $take;
		return $data;
	}

	/**
	 * Reads the next inner chunk and inflates it, finishing the stream at the inner end.
	 * @throws TIOException When the inflate context cannot be created.
	 * @return ?string The decompressed chunk, or null when the input is exhausted.
	 */
	private function inflateChunk(): ?string
	{
		if ($this->_inputDone) {
			return null;
		}
		$context = $this->context();
		$inner = $this->getStream();
		$data = $inner->eof() ? '' : $inner->read(static::CHUNK_SIZE);
		if ($data === '') {
			$this->_inputDone = true;
			return inflate_add($context, '', ZLIB_FINISH);
		}
		return inflate_add($context, $data, ZLIB_SYNC_FLUSH);
	}

	/**
	 * Returns the inflate context, creating it on first use.
	 * @throws TIOException When the inflate context cannot be created.
	 * @return \InflateContext The inflate context.
	 */
	private function context(): \InflateContext
	{
		if ($this->_context === null) {
			$context = inflate_init($this->_encoding);
			if ($context === false) {
				throw new TIOException('inflatestream_init_failed', $this->_encoding);
			}
			$this->_context = $context;
		}
		return $this->_context;
	}

	/**
	 * Indicates whether the transform is exhausted (finished and the buffer is drained).
	 * @return bool Whether at the end of the decompressed stream.
	 */
	public function eof(): bool
	{
		return $this->_finished && strlen($this->_buffer) <= $this->_bufferPos;
	}

	/**
	 * Returns the number of decompressed bytes read so far.
	 * @return int The decompressed read position.
	 */
	public function tell(): int
	{
		return $this->_position;
	}

	/**
	 * Returns null; the decompressed size is not known ahead of time.
	 * @return ?int Always null.
	 */
	public function getSize(): ?int
	{
		return null;
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
	 * Rejects any seek, as the transform is forward-only.
	 * @param int $offset The stream offset (unused).
	 * @param int $whence SEEK_SET, SEEK_CUR, or SEEK_END (unused).
	 * @throws \RuntimeException Always.
	 */
	public function seek(int $offset, int $whence = SEEK_SET): void
	{
		throw new \RuntimeException('Cannot seek an inflate stream');
	}

	/**
	 * Reports the stream as not writable.
	 * @return bool Always false.
	 */
	public function isWritable(): bool
	{
		return false;
	}

	/**
	 * Rejects any write, as the inflate stream is read-only.
	 * @param string $string The bytes to write (unused).
	 * @throws \RuntimeException Always.
	 */
	public function write(string $string): int
	{
		throw new \RuntimeException('Cannot write to an inflate stream');
	}
}
