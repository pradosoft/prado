<?php

/**
 * TXzCompressor class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Compression;

use Prado\Exceptions\TNotSupportedException;

/**
 * TXzCompressor class.
 *
 * A built-in stub for the xz/LZMA format (`.xz`).  PHP has no xz extension, so unlike the
 * other {@see TBuiltinCompressor} codecs there is no native function to wrap: the codec is
 * always {@see isAvailable() unavailable} and every call throws {@see TNotSupportedException},
 * so the format has a stable, discoverable class in core without pretending to work.
 *
 * A working xz codec lives in the `belisoful/prado-compression` package, which uses the xz
 * command as a fallback (and a native xz extension first, if one is ever adopted) through
 * {@see TCliCompressorTrait}.  When a maintained PHP xz extension is adopted into core, this
 * stub is replaced by a native codec with the same name and {@see ICompressor} contract.  The
 * `.tar.xz` archive path uses the xz command directly in {@see \Prado\IO\TTarFileExtractor}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TXzCompressor extends TBuiltinCompressor
{
	/** The wire-format name. */
	public const NAME = 'xz';

	/** The PHP extension that would back the codec; none is published, so the stub is inert. */
	protected const EXTENSION = 'xz';

	/**
	 * Reports the codec unavailable: no PHP xz extension exists.
	 * @return bool Always false.
	 */
	public static function isAvailable(): bool
	{
		return false;
	}

	/**
	 * Throws: the core stub cannot compress xz.  Use `belisoful/prado-compression`.
	 * @param string $data The raw bytes.
	 * @param int $level The compression level (unused).
	 * @throws TNotSupportedException Always.
	 * @return string Never returns.
	 */
	public static function compress(string $data, int $level = -1): string
	{
		throw new TNotSupportedException('compression_xz_unsupported');
	}

	/**
	 * Throws: the core stub cannot decompress xz.  Use `belisoful/prado-compression`.
	 * @param string $data The compressed bytes.
	 * @throws TNotSupportedException Always.
	 * @return string Never returns.
	 */
	public static function decompress(string $data): string
	{
		throw new TNotSupportedException('compression_xz_unsupported');
	}

	/**
	 * Unreachable native backend; the stub has no xz extension to call.
	 * @param string $data The raw bytes.
	 * @param int $level The compression level.
	 * @throws TNotSupportedException Always.
	 * @return false|int|string Never returns.
	 */
	protected static function encode(string $data, int $level): string|int|false
	{
		throw new TNotSupportedException('compression_xz_unsupported');
	}

	/**
	 * Unreachable native backend; the stub has no xz extension to call.
	 * @param string $data The compressed bytes.
	 * @throws TNotSupportedException Always.
	 * @return false|int|string Never returns.
	 */
	protected static function decode(string $data): string|int|false
	{
		throw new TNotSupportedException('compression_xz_unsupported');
	}
}
