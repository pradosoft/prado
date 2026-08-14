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
	 * Decompresses with gzdecode.
	 * @param string $data The gzip bytes.
	 * @return false|string The raw bytes, or false on failure.
	 */
	protected static function decode(string $data): string|false
	{
		return @gzdecode($data);
	}
}
