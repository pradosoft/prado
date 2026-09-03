<?php

/**
 * TStreamHelper class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Util;

use Psr\Http\Message\StreamInterface;

/**
 * TStreamHelper class.
 *
 * Static utilities over a PSR-7 {@see StreamInterface}, for operations the interface itself
 * does not provide: copying a stream into a string or another stream, hashing a stream's
 * contents, and reading a single line.  They work on any StreamInterface, not just
 * {@see \Prado\IO\TStream}.
 *
 * The copies and the hash move {@see CHUNK_SIZE} bytes per pass, so a body larger than
 * memory streams through without materializing.  Mapping a file name or extension to its
 * media type is {@see \Prado\Web\TMediaType::mimeTypeFromFilename()}, with the media
 * types themselves.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TStreamHelper
{
	/** @var int The chunk size used when copying and hashing. */
	public const CHUNK_SIZE = 8192;

	/**
	 * Reads a stream from its current position into a string, optionally size-bounded.
	 * @param StreamInterface $stream The stream to read.
	 * @param int $maxLength The maximum number of bytes to read, or -1 for all remaining. Default -1.
	 * @return string The bytes read.
	 */
	public static function copyToString(StreamInterface $stream, int $maxLength = -1): string
	{
		$buffer = '';
		while (!$stream->eof()) {
			$want = $maxLength === -1 ? static::CHUNK_SIZE : min(static::CHUNK_SIZE, $maxLength - strlen($buffer));
			if ($want <= 0) {
				break;
			}
			$chunk = $stream->read($want);
			if ($chunk === '') {
				break;
			}
			$buffer .= $chunk;
		}
		return $buffer;
	}

	/**
	 * Copies bytes from one stream to another, reading from the source's current position and
	 * writing at the destination's current position.  Each chunk is written completely,
	 * looping over short writes, so the destination never receives a torn copy.
	 * @param StreamInterface $source The stream to read from.
	 * @param StreamInterface $dest The stream to write to.
	 * @param int $maxLength The maximum number of bytes to copy, or -1 for all remaining. Default -1.
	 * @throws \RuntimeException When the destination stops accepting bytes mid-copy.
	 * @return int The number of bytes copied.
	 */
	public static function copyToStream(StreamInterface $source, StreamInterface $dest, int $maxLength = -1): int
	{
		$copied = 0;
		while (!$source->eof()) {
			$want = $maxLength === -1 ? static::CHUNK_SIZE : min(static::CHUNK_SIZE, $maxLength - $copied);
			if ($want <= 0) {
				break;
			}
			$chunk = $source->read($want);
			if ($chunk === '') {
				break;
			}
			$offset = 0;
			$length = strlen($chunk);
			while ($offset < $length) {
				$written = $dest->write($offset === 0 ? $chunk : substr($chunk, $offset));
				if ($written <= 0) {
					throw new \RuntimeException('copyToStream destination stopped accepting bytes at ' . ($copied + $offset));
				}
				$offset += $written;
			}
			$copied += $length;
		}
		return $copied;
	}

	/**
	 * Copies an exact byte range of a seekable source stream to a destination.  The source is
	 * seeked to $offset and exactly $length bytes are copied, in {@see CHUNK_SIZE} passes, so a
	 * payload far larger than memory moves from one stream to another without materializing.  It
	 * copies an exact count and throws when the source ends before $length bytes are read.
	 * @param StreamInterface $source The seekable stream to read from.
	 * @param int $offset The absolute byte offset in the source to start at.
	 * @param int $length The number of bytes to copy.
	 * @param StreamInterface $dest The stream to write to.
	 * @throws \InvalidArgumentException When $length is negative.
	 * @throws \RuntimeException When the source is not seekable, ends before the range does, or the destination stops accepting bytes.
	 * @return int The number of bytes copied (equal to $length).
	 */
	public static function copyRange(StreamInterface $source, int $offset, int $length, StreamInterface $dest): int
	{
		if ($length < 0) {
			throw new \InvalidArgumentException('copyRange length cannot be negative');
		}
		$source->seek($offset);
		$copied = 0;
		while ($copied < $length) {
			$chunk = $source->read(min(static::CHUNK_SIZE, $length - $copied));
			if ($chunk === '') {
				throw new \RuntimeException('copyRange source ended after ' . $copied . ' of ' . $length . ' bytes');
			}
			$pos = 0;
			$chunkLength = strlen($chunk);
			while ($pos < $chunkLength) {
				$written = $dest->write($pos === 0 ? $chunk : substr($chunk, $pos));
				if ($written <= 0) {
					throw new \RuntimeException('copyRange destination stopped accepting bytes at ' . ($copied + $pos));
				}
				$pos += $written;
			}
			$copied += $chunkLength;
		}
		return $copied;
	}

	/**
	 * Hashes a stream's contents.  A seekable stream is hashed in full from the beginning and
	 * its position restored; a non-seekable stream is hashed from its current position.
	 * @param StreamInterface $stream The stream to hash.
	 * @param string $algorithm A {@see hash_algos()} algorithm. Default 'sha256'.
	 * @param bool $rawOutput Whether to return raw binary instead of lowercase hex. Default false.
	 * @return string The hash digest.
	 */
	public static function hash(StreamInterface $stream, string $algorithm = 'sha256', bool $rawOutput = false): string
	{
		$position = $stream->isSeekable() ? $stream->tell() : null;
		if ($position !== null) {
			$stream->rewind();
		}
		$context = hash_init($algorithm);
		while (!$stream->eof()) {
			$chunk = $stream->read(static::CHUNK_SIZE);
			if ($chunk === '') {
				break;
			}
			hash_update($context, $chunk);
		}
		if ($position !== null) {
			$stream->seek($position);
		}
		return hash_final($context, $rawOutput);
	}

	/**
	 * Reads one line from a stream: bytes up to and including the next "\n", or until the end
	 * of the stream, or until $maxLength - 1 bytes have been read (matching {@see fgets()}).
	 * @param StreamInterface $stream The stream to read from.
	 * @param ?int $maxLength The maximum line length including the newline, or null for unbounded.
	 * @return string The line read, including the trailing "\n" when present; '' at end of stream.
	 */
	public static function readLine(StreamInterface $stream, ?int $maxLength = null): string
	{
		$line = '';
		while (!$stream->eof()) {
			$byte = $stream->read(1);
			if ($byte === '') {
				break;
			}
			$line .= $byte;
			if ($byte === "\n" || ($maxLength !== null && strlen($line) === $maxLength - 1)) {
				break;
			}
		}
		return $line;
	}
}
