<?php

/**
 * TCliCompressor class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Compression;

/**
 * TCliCompressor class.
 *
 * The base for an {@see ICompressor} codec that is backed only by a system command, for a
 * format with no PHP extension at all.  It wires the {@see TCliCompressorTrait} command
 * backend to the {@see ICompressor} contract: {@see isAvailable()} reports whether the
 * command is present, and {@see compress()}/{@see decompress()} run it.  A subclass supplies
 * the command and its arguments through {@see commands()}, {@see compressArgs()}, and
 * {@see decompressArgs()}.
 *
 * A codec whose format also has a PHP extension does not extend this; it extends
 * {@see TBuiltinCompressor} and mixes in {@see TCliCompressorTrait} directly to fall back to
 * the command only when the extension is absent.  A CLI codec spawns a process per call, so
 * it costs far more than a native codec and suits an occasional whole-string transform, not a
 * hot loop.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
abstract class TCliCompressor implements ICompressor
{
	use TCliCompressorTrait;

	/** The wire-format name of the codec. */
	public const NAME = '';

	/**
	 * Returns whether the backing command is available on this system.
	 * @return bool Whether the codec can run.
	 */
	public static function isAvailable(): bool
	{
		return static::cliAvailable();
	}

	/**
	 * Compresses a byte string by running the command.
	 * @param string $data The raw bytes.
	 * @param int $level The compression level, or -1 for the command's default.
	 * @throws \Prado\Exceptions\TIOException When the command is missing or fails.
	 * @return string The compressed bytes.
	 */
	public static function compress(string $data, int $level = -1): string
	{
		return static::cliCompress($data, $level);
	}

	/**
	 * Decompresses a byte string by running the command.
	 * @param string $data The compressed bytes.
	 * @throws \Prado\Exceptions\TIOException When the command is missing, the data is corrupt, or the command fails.
	 * @return string The decompressed bytes.
	 */
	public static function decompress(string $data): string
	{
		return static::cliDecompress($data);
	}
}
