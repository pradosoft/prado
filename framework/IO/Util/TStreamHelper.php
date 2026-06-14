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
 * does not provide: copying one stream into another, hashing a stream's contents, and reading
 * a single line.  They work on any StreamInterface, not just {@see \Prado\IO\TStream}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TStreamHelper
{
	/** @var int The chunk size used when copying and hashing. */
	public const CHUNK_SIZE = 8192;

	/**
	 * Copies bytes from one stream to another, reading from the source's current position and
	 * writing at the destination's current position.
	 * @param StreamInterface $source The stream to read from.
	 * @param StreamInterface $dest The stream to write to.
	 * @param int $maxLength The maximum number of bytes to copy, or -1 for all remaining. Default -1.
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
			$copied += $dest->write($chunk);
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
