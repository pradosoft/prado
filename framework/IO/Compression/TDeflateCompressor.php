<?php

/**
 * TDeflateCompressor class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Compression;

/**
 * TDeflateCompressor class.
 *
 * Wraps PHP's {@see https://www.php.net/gzdeflate gzdeflate}/{@see https://www.php.net/gzinflate
 * gzinflate} for raw DEFLATE (RFC 1951): the compressed body with no header, checksum, or
 * length.  Raw DEFLATE is the most compact of the three zlib forms and the payload other
 * formats wrap, so it suits an embedded stream that carries its own framing (a PNG `IDAT`
 * chunk, a ZIP entry, a WebSocket permessage-deflate frame).  Its streaming counterpart is
 * {@see \Prado\IO\Stream\TDeflateStream} with `ZLIB_ENCODING_RAW`.
 *
 * With no checksum, corrupt input is caught only when it cannot be inflated.  The
 * header-and-checksum variants are {@see TZlibCompressor} and {@see TGzipCompressor}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TDeflateCompressor extends TBuiltinCompressor
{
	/** The wire-format name. */
	public const NAME = 'deflate';

	/** The backing PHP extension. */
	protected const EXTENSION = 'zlib';

	/**
	 * Compresses with gzdeflate.
	 * @param string $data The raw bytes.
	 * @param int $level The compression level 0..9, or -1 for the zlib default.
	 * @return false|string The raw DEFLATE bytes, or false on failure.
	 */
	protected static function encode(string $data, int $level): string|false
	{
		$level = ($level < -1 || $level > 9) ? -1 : $level;   // an out-of-range level is the zlib default, never a ValueError
		return @gzdeflate($data, $level);
	}

	/**
	 * Decompresses with gzinflate.
	 * @param string $data The raw DEFLATE bytes.
	 * @return false|string The raw bytes, or false on failure.
	 */
	protected static function decode(string $data): string|false
	{
		return @gzinflate($data);
	}
}
