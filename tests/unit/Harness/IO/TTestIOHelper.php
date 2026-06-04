<?php

/**
 * TTestIOHelper class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\IO\TStream;
use Psr\Http\Message\StreamInterface;

/**
 * TTestIOHelper builds and inspects PHP stream resources and {@see \Prado\IO\TStream}s for
 * unit tests.  Resources and streams are a universal aspect of PHP, so any test — not just
 * the IO layer's — can lean on these factories instead of re-opening `php://temp` by hand.
 *
 * The factories cover the common shapes:
 *
 * | Method                       | Produces                                              |
 * |------------------------------|-------------------------------------------------------|
 * | {@see memoryResource()}      | a `php://memory` resource                             |
 * | {@see tempResource()}        | a `php://temp` resource                               |
 * | {@see dataResource()}        | a `php://temp` resource seeded with bytes and rewound |
 * | {@see fileResource()}        | a file resource                                       |
 * | {@see pipeResource()}        | a non-seekable, read-only pipe carrying given bytes   |
 * | {@see memoryStream()} / {@see tempStream()} / {@see dataStream()} | the {@see TStream} equivalents |
 *
 * {@see contents()} reads a stream or resource in full (rewinding first when seekable),
 * {@see closeAny()} closes either a pipe or a plain resource, and {@see tempFile()} /
 * {@see removeTempFiles()} manage scratch files so a test's tearDown can clean up in one
 * call.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestIOHelper
{
	/** @var array<int, string> Scratch file paths created by {@see tempFile()}. */
	private static array $_tempFiles = [];

	/** @var array<int, true> Resource ids of pipes opened by {@see pipeResource()}. */
	private static array $_pipes = [];

	// -------------------------------------------------------------------------
	// Raw resource factories
	// -------------------------------------------------------------------------

	/**
	 * Opens a php://memory resource.
	 * @param string $mode The fopen mode. Default 'r+b'.
	 * @return resource The open resource.
	 */
	public static function memoryResource(string $mode = 'r+b')
	{
		return fopen('php://memory', $mode);
	}

	/**
	 * Opens a php://temp resource.
	 * @param string $mode The fopen mode. Default 'r+b'.
	 * @return resource The open resource.
	 */
	public static function tempResource(string $mode = 'r+b')
	{
		return fopen('php://temp', $mode);
	}

	/**
	 * Opens a php://temp resource seeded with bytes and rewound to the start.
	 * @param string $data The initial contents.
	 * @param string $mode The fopen mode. Default 'r+b'.
	 * @return resource The seeded, rewound resource.
	 */
	public static function dataResource(string $data, string $mode = 'r+b')
	{
		$resource = fopen('php://temp', $mode);
		if ($data !== '') {
			fwrite($resource, $data);
			rewind($resource);
		}
		return $resource;
	}

	/**
	 * Opens a file resource.
	 * @param string $path The file path.
	 * @param string $mode The fopen mode. Default 'rb'.
	 * @return resource The open resource.
	 */
	public static function fileResource(string $path, string $mode = 'rb')
	{
		return fopen($path, $mode);
	}

	/**
	 * Opens a non-seekable, read-only pipe carrying the given bytes (via {@see popen()}),
	 * for exercising non-seekable stream paths.
	 * @param string $data The bytes the pipe yields.
	 * @return resource The open pipe resource (close with {@see closeAny()}).
	 */
	public static function pipeResource(string $data)
	{
		$path = static::tempFile($data, 'iopipe');
		$command = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg('readfile(' . var_export($path, true) . ');');
		$pipe = popen($command, 'r');
		if (is_resource($pipe)) {
			self::$_pipes[(int) $pipe] = true;
		}
		return $pipe;
	}

	// -------------------------------------------------------------------------
	// TStream factories
	// -------------------------------------------------------------------------

	/**
	 * Creates a php://memory {@see TStream}.
	 * @param string $mode The fopen mode. Default 'r+b'.
	 * @return TStream The stream.
	 */
	public static function memoryStream(string $mode = 'r+b'): TStream
	{
		return TStream::fromMemory($mode);
	}

	/**
	 * Creates a php://temp {@see TStream}.
	 * @return TStream The stream.
	 */
	public static function tempStream(): TStream
	{
		return TStream::fromTemp();
	}

	/**
	 * Creates a {@see TStream} seeded with bytes, positioned at the start.
	 * @param string $data The initial contents.
	 * @return TStream The stream.
	 */
	public static function dataStream(string $data = ''): TStream
	{
		return TStream::fromString($data);
	}

	/**
	 * Wraps an open resource as a {@see TStream}.
	 * @param resource $resource The open resource.
	 * @param bool $owns Whether the stream owns (and closes) the resource. Default true.
	 * @return TStream The stream.
	 */
	public static function resourceStream($resource, bool $owns = true): TStream
	{
		return TStream::fromResource($resource, $owns);
	}

	// -------------------------------------------------------------------------
	// Inspection & cleanup
	// -------------------------------------------------------------------------

	/**
	 * Reads a stream or raw resource in full, rewinding first when it is seekable.
	 * @param StreamInterface|resource $streamOrResource The stream or resource.
	 * @return string The full contents.
	 */
	public static function contents($streamOrResource): string
	{
		if ($streamOrResource instanceof StreamInterface) {
			if ($streamOrResource->isSeekable()) {
				$streamOrResource->seek(0);
			}
			return $streamOrResource->getContents();
		}
		$meta = stream_get_meta_data($streamOrResource);
		if (!empty($meta['seekable'])) {
			rewind($streamOrResource);
		}
		return (string) stream_get_contents($streamOrResource);
	}

	/**
	 * Closes a resource, using {@see pclose()} for a pipe and {@see fclose()} otherwise.
	 * @param resource $resource The resource to close.
	 */
	public static function closeAny($resource): void
	{
		if (!is_resource($resource)) {
			return;
		}
		$id = (int) $resource;
		if (isset(self::$_pipes[$id])) {
			unset(self::$_pipes[$id]);
			@pclose($resource);
			return;
		}
		fclose($resource);
	}

	/**
	 * Creates a scratch file (registered for {@see removeTempFiles()}), optionally seeded.
	 * @param string $contents The initial contents. Default '' (empty file).
	 * @param string $prefix The temp-name prefix. Default 'iotest'.
	 * @return string The file path.
	 */
	public static function tempFile(string $contents = '', string $prefix = 'iotest'): string
	{
		$path = tempnam(sys_get_temp_dir(), $prefix);
		if ($contents !== '') {
			file_put_contents($path, $contents);
		}
		self::$_tempFiles[] = $path;
		return $path;
	}

	/**
	 * Deletes every scratch file created by {@see tempFile()} and clears the registry.
	 */
	public static function removeTempFiles(): void
	{
		foreach (self::$_tempFiles as $path) {
			if (is_file($path)) {
				@unlink($path);
			}
		}
		self::$_tempFiles = [];
	}
}
