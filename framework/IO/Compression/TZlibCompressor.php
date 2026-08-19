<?php

/**
 * TZlibCompressor class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Compression;

/**
 * TZlibCompressor class.
 *
 * Wraps PHP's {@see https://www.php.net/gzcompress gzcompress}/{@see https://www.php.net/gzuncompress
 * gzuncompress} for the zlib format (RFC 1950): a two-byte header, a raw DEFLATE body, and
 * a trailing Adler-32 checksum.  The zlib format is more compact than gzip and is what the
 * HTTP `Content-Encoding: deflate` token specifies (despite the token's name).  Its
 * streaming counterpart is {@see \Prado\IO\Stream\TDeflateStream} with the default
 * `ZLIB_ENCODING_DEFLATE`.
 *
 * The headerless variant is {@see TDeflateCompressor}; the CRC-checked, widely interchanged
 * variant is {@see TGzipCompressor}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TZlibCompressor extends TBuiltinCompressor
{
	/** The wire-format name. */
	public const NAME = 'zlib';

	/** The backing PHP extension. */
	protected const EXTENSION = 'zlib';

	/**
	 * Compresses with gzcompress.
	 * @param string $data The raw bytes.
	 * @param int $level The compression level 0..9, or -1 for the zlib default.
	 * @return false|string The zlib bytes, or false on failure.
	 */
	protected static function encode(string $data, int $level): string|false
	{
		$level = ($level < -1 || $level > 9) ? -1 : $level;   // an out-of-range level is the zlib default, never a ValueError
		return @gzcompress($data, $level);
	}

	/**
	 * Decompresses with gzuncompress.
	 * @param string $data The zlib bytes.
	 * @return false|string The raw bytes, or false on failure.
	 */
	protected static function decode(string $data): string|false
	{
		return @gzuncompress($data);
	}
}
