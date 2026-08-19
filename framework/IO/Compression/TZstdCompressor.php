<?php

/**
 * TZstdCompressor class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Compression;

/**
 * TZstdCompressor class.
 *
 * Wraps the Zstandard (zstd) functions for the `zstd` format: a modern codec that reaches
 * gzip-class ratios at much higher speed, and higher ratios at comparable speed.  It is the
 * HTTP `Content-Encoding: zstd` coding.  The functions live in the optional `zstd`
 * extension, so {@see isAvailable()} reports whether the codec can run before it is used;
 * the framework never bundles a fallback implementation.
 *
 * The compression level runs 1..22 (higher is smaller and slower); -1 selects
 * {@see DEFAULT_LEVEL}, the zstd default.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TZstdCompressor extends TBuiltinCompressor
{
	/** The wire-format name (the HTTP content-coding token). */
	public const NAME = 'zstd';

	/** The backing PHP extension. */
	protected const EXTENSION = 'zstd';

	/** The lowest zstd compression level. */
	public const MIN_LEVEL = 1;

	/** The highest zstd compression level. */
	public const MAX_LEVEL = 22;

	/** The zstd compression level used when no explicit level is given. */
	public const DEFAULT_LEVEL = 3;

	/**
	 * Compresses with zstd_compress.
	 * @param string $data The raw bytes.
	 * @param int $level The compression level 1..22, or -1 for {@see DEFAULT_LEVEL}.
	 * @return false|string The zstd bytes, or false on failure.
	 */
	protected static function encode(string $data, int $level): string|false
	{
		$level = ($level < self::MIN_LEVEL || $level > self::MAX_LEVEL) ? self::DEFAULT_LEVEL : $level;
		return @zstd_compress($data, $level);
	}

	/**
	 * Decompresses with zstd_uncompress.
	 * @param string $data The zstd bytes.
	 * @return false|string The raw bytes, or false on failure.
	 */
	protected static function decode(string $data): string|false
	{
		return @zstd_uncompress($data);
	}
}
