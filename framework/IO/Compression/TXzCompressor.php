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
 * A built-in stub for the xz/LZMA format (`.xz`).  PHP bundles no xz extension.  The
 * third-party `codemasher/php-ext-xz` extension (installed through PIE, `extension=xz.so`)
 * provides `xzencode()`/`xzdecode()`, and its fork `mateuszanella/php-ext-xz` changes those
 * signatures.  Core wraps neither, so unlike the other {@see TBuiltinCompressor} codecs the
 * codec is always {@see isAvailable() unavailable} and every call throws
 * {@see TNotSupportedException}.  The format still has a stable, discoverable class in core.
 *
 * A working xz codec lives in the `belisoful/prado-compression` package, which prefers a
 * loaded xz extension and falls back to the xz command through {@see TCliCompressorTrait}.
 * If core adopts a PHP xz extension, this stub becomes a native codec with the same name and
 * {@see ICompressor} contract.  The `.tar.xz` archive path uses the xz command directly in
 * {@see \Prado\IO\TTarFileExtractor}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TXzCompressor extends TBuiltinCompressor
{
	/** The wire-format name. */
	public const NAME = 'xz';

	/** The PHP extension name of the third-party xz extensions; core does not call it, so the stub is inert. */
	protected const EXTENSION = 'xz';

	/**
	 * Reports the codec unavailable: core wraps no PHP xz extension.
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
	 * Unreachable native backend; the stub calls no xz extension.
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
	 * Unreachable native backend; the stub calls no xz extension.
	 * @param string $data The compressed bytes.
	 * @throws TNotSupportedException Always.
	 * @return false|int|string Never returns.
	 */
	protected static function decode(string $data): string|int|false
	{
		throw new TNotSupportedException('compression_xz_unsupported');
	}
}
