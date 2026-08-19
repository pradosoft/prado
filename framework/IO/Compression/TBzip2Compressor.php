<?php

/**
 * TBzip2Compressor class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Compression;

/**
 * TBzip2Compressor class.
 *
 * Wraps PHP's {@see https://www.php.net/bzcompress bzcompress}/{@see https://www.php.net/bzdecompress
 * bzdecompress} for the bzip2 format: a Burrows-Wheeler codec that compresses more tightly
 * than DEFLATE on many inputs at a higher CPU cost.  This is the format of a `.bz2` file.
 * The bzip2 functions live in the optional `bz2` extension, so {@see isAvailable()} reports
 * whether the codec can run before it is used.
 *
 * The compression level is a bzip2 block size of 1..9 (each step is 100 KB of working
 * memory); -1 selects the library default.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TBzip2Compressor extends TBuiltinCompressor
{
	/** The wire-format name. */
	public const NAME = 'bzip2';

	/** The backing PHP extension. */
	protected const EXTENSION = 'bz2';

	/** The bzip2 block size used when no explicit level is given. */
	public const DEFAULT_BLOCK_SIZE = 4;

	/**
	 * Compresses with bzcompress.
	 * @param string $data The raw bytes.
	 * @param int $level The bzip2 block size 1..9, or -1 for {@see DEFAULT_BLOCK_SIZE}.
	 * @return int|string The bzip2 bytes, or a bzip2 error number on failure.
	 */
	protected static function encode(string $data, int $level): string|int
	{
		$blockSize = ($level < 1 || $level > 9) ? self::DEFAULT_BLOCK_SIZE : $level;
		return bzcompress($data, $blockSize);
	}

	/**
	 * Decompresses with bzdecompress.
	 * @param string $data The bzip2 bytes.
	 * @return int|string The raw bytes, or a bzip2 error number on failure.
	 */
	protected static function decode(string $data): string|int
	{
		return bzdecompress($data);
	}
}
