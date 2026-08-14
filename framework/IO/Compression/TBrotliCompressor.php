<?php

/**
 * TBrotliCompressor class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Compression;

/**
 * TBrotliCompressor class.
 *
 * Wraps the Brotli functions for the `br` format: a codec that compresses text more tightly
 * than gzip, which is why browsers advertise it for web content.  It is the HTTP
 * `Content-Encoding: br` coding.  The functions live in the optional `brotli` extension, so
 * {@see isAvailable()} reports whether the codec can run before it is used; the framework
 * never bundles a fallback implementation.
 *
 * The quality runs 0..11 (higher is smaller and slower); -1 selects {@see DEFAULT_QUALITY},
 * the maximum, which is Brotli's own default.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TBrotliCompressor extends TBuiltinCompressor
{
	/** The wire-format name (the HTTP content-coding token). */
	public const NAME = 'br';

	/** The backing PHP extension. */
	protected const EXTENSION = 'brotli';

	/** The lowest Brotli quality. */
	public const MIN_QUALITY = 0;

	/** The highest Brotli quality, which is also its default. */
	public const MAX_QUALITY = 11;

	/** The Brotli quality used when no explicit level is given. */
	public const DEFAULT_QUALITY = 11;

	/**
	 * Compresses with brotli_compress.
	 * @param string $data The raw bytes.
	 * @param int $level The quality 0..11, or -1 for {@see DEFAULT_QUALITY}.
	 * @return false|string The brotli bytes, or false on failure.
	 */
	protected static function encode(string $data, int $level): string|false
	{
		$quality = ($level < self::MIN_QUALITY || $level > self::MAX_QUALITY) ? self::DEFAULT_QUALITY : $level;
		return @brotli_compress($data, $quality);
	}

	/**
	 * Decompresses with brotli_uncompress.
	 * @param string $data The brotli bytes.
	 * @return false|string The raw bytes, or false on failure.
	 */
	protected static function decode(string $data): string|false
	{
		return @brotli_uncompress($data);
	}
}
