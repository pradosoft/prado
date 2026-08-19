<?php

/**
 * TBuiltinCompressor class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Compression;

use Prado\Exceptions\TIOException;

/**
 * TBuiltinCompressor class.
 *
 * The shared base for the {@see ICompressor} codecs that wrap PHP's native compression
 * functions, so the framework exposes the standard formats without reimplementing them.
 * A subclass names its required extension through {@see EXTENSION} and provides the two
 * native calls through {@see encode()} and {@see decode()}; this base adds the availability
 * guard, the compression-level parameter, and uniform {@see TIOException} failure reporting.
 *
 * A native call reports failure by returning a non-string (`false` for the zlib functions,
 * an integer error code for the bzip2 functions), so both surface as a thrown exception
 * rather than a silent wrong value.  The concrete codecs are {@see TGzipCompressor},
 * {@see TZlibCompressor}, {@see TDeflateCompressor}, and {@see TBzip2Compressor}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
abstract class TBuiltinCompressor implements ICompressor
{
	/** The wire-format name of the codec, for diagnostics and content negotiation. */
	public const NAME = '';

	/** The PHP extension the codec requires, or '' when it is part of the PHP core. */
	protected const EXTENSION = '';

	/**
	 * Returns whether the codec's backing extension is loaded in this PHP installation.
	 * @return bool Whether the codec can run.
	 */
	public static function isAvailable(): bool
	{
		return static::EXTENSION === '' || extension_loaded(static::EXTENSION);
	}

	/**
	 * Compresses a byte string with the native codec.
	 * @param string $data The raw bytes.
	 * @param int $level The compression level, or -1 for the codec's default.
	 * @throws TIOException When the backing extension is absent or the native call fails.
	 * @return string The compressed bytes.
	 */
	public static function compress(string $data, int $level = -1): string
	{
		static::assertAvailable();
		$result = static::encode($data, $level);
		if (!is_string($result)) {
			throw new TIOException('builtincompressor_compress_failed', static::NAME);
		}
		return $result;
	}

	/**
	 * Decompresses a byte string produced by {@see compress()}.
	 * @param string $data The compressed bytes.
	 * @throws TIOException When the backing extension is absent or the data is corrupt.
	 * @return string The decompressed bytes.
	 */
	public static function decompress(string $data): string
	{
		static::assertAvailable();
		$result = static::decode($data);
		if (!is_string($result)) {
			throw new TIOException('builtincompressor_decompress_failed', static::NAME);
		}
		return $result;
	}

	/**
	 * Asserts the backing extension is loaded before a native call.
	 * @throws TIOException When the extension is absent.
	 */
	protected static function assertAvailable(): void
	{
		if (!static::isAvailable()) {
			throw new TIOException('builtincompressor_extension_required', static::NAME, static::EXTENSION);
		}
	}

	/**
	 * Runs the native compression call.
	 * @param string $data The raw bytes.
	 * @param int $level The compression level, or -1 for the codec's default.
	 * @return false|int|string The compressed bytes, or a non-string on failure.
	 */
	abstract protected static function encode(string $data, int $level): string|int|false;

	/**
	 * Runs the native decompression call.
	 * @param string $data The compressed bytes.
	 * @return false|int|string The decompressed bytes, or a non-string on failure.
	 */
	abstract protected static function decode(string $data): string|int|false;
}
