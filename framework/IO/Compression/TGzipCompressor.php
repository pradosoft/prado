<?php

/**
 * TGzipCompressor class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Compression;

/**
 * TGzipCompressor class.
 *
 * Wraps PHP's {@see https://www.php.net/gzencode gzencode}/{@see https://www.php.net/gzdecode
 * gzdecode} for the gzip format (RFC 1952): a header, a raw DEFLATE body, and a trailing
 * CRC-32 and length.  This is the format of a `.gz` file and of an HTTP `Content-Encoding:
 * gzip` body, so the compressed bytes interoperate with any gzip reader.  Its streaming
 * counterpart is {@see \Prado\IO\Stream\TDeflateStream} with `ZLIB_ENCODING_GZIP`.
 *
 * ```php
 * $packed = TGzipCompressor::compress($data);   // a .gz-compatible byte string
 * $data = TGzipCompressor::decompress($packed);
 * ```
 *
 * Decompression accepts what the `gzip` command accepts.  The data is one or more gzip
 * members, and concatenated members decode to their concatenated data.  A member cut off
 * before its CRC-32 and length trailer ends, or any byte after the last member, is rejected.
 * PHP's `gzdecode` is laxer: it decodes the first member only, ignores the data after it, and
 * accepts a member missing its trailer.  The codec drives an
 * {@see https://www.php.net/inflate_init inflate context} instead, which reports where each
 * member ends and whether it ended.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TGzipCompressor extends TBuiltinCompressor
{
	/** The wire-format name. */
	public const NAME = 'gzip';

	/** The backing PHP extension. */
	protected const EXTENSION = 'zlib';

	/**
	 * Compresses with gzencode.
	 * @param string $data The raw bytes.
	 * @param int $level The compression level 0..9, or -1 for the zlib default.
	 * @return false|string The gzip bytes, or false on failure.
	 */
	protected static function encode(string $data, int $level): string|false
	{
		$level = ($level < -1 || $level > 9) ? -1 : $level;   // an out-of-range level is the zlib default, never a ValueError
		return @gzencode($data, $level);
	}

	/**
	 * Decompresses each gzip member in turn until the data is consumed.
	 * @param string $data The gzip bytes.
	 * @return false|string The raw bytes, or false when a member is corrupt or incomplete or
	 *   data follows the last member.
	 */
	protected static function decode(string $data): string|false
	{
		$result = '';
		$offset = 0;
		do {
			$context = inflate_init(ZLIB_ENCODING_GZIP);
			$member = @inflate_add($context, substr($data, $offset), ZLIB_FINISH);
			if ($member === false || inflate_get_status($context) !== ZLIB_STREAM_END) {
				return false;
			}
			$result .= $member;
			$offset += inflate_get_read_len($context);
		} while ($offset < strlen($data));
		return $result;
	}
}
