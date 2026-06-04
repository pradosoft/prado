<?php

/**
 * TStreamResourceWrapper class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO;

use Prado\Exceptions\TInvalidDataValueException;
use Psr\Http\Message\StreamInterface;

/**
 * TStreamResourceWrapper class
 *
 * Exposes a PSR-7 {@see StreamInterface} as a native PHP stream *resource*, so a
 * stream object can be handed to the many built-in functions and extensions that
 * accept only a real resource handle, such as {@see fgetcsv()}, {@see stream_copy_to_stream()},
 * and {@see curl_setopt()} with CURLOPT_FILE.  It is the inverse of
 * {@see TStream::fromResource()} (which wraps a resource as a stream) and is normally
 * reached through the convenience method {@see TStream::asResource()}.
 *
 * The class implements PHP's stream-wrapper prototype and registers itself under the
 * {@see PROTOCOL} scheme.  It is not instantiated directly: {@see getResource()}
 * registers the wrapper, passes the target stream through a stream context, and
 * {@see fopen()}s a `prado-stream://` URL.  PHP then creates the wrapper and routes
 * every read/write/seek/tell/eof on the returned resource to the underlying
 * {@see StreamInterface}.  The open mode follows the stream's readable/writable
 * capabilities.  Closing the resource frees this view only; the underlying stream
 * stays open (its owner closes it).
 *
 * This is distinct from the future general-purpose custom-protocol base
 * `Util/TStreamWrapper`, which registers user-defined URL schemes.
 *
 * ```php
 * use Prado\IO\TStream;
 *
 * $stream   = TStream::fromString("name,score\nAda,99\n");
 * $resource = TStream::asResource($stream);   // native resource backed by $stream
 *
 * $header = fgetcsv($resource);                // ['name', 'score'] from a resource-only API
 * rewind($resource);
 * $body = stream_get_contents($resource);
 * fclose($resource);                           // frees the resource view; $stream stays open
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TStreamResourceWrapper
{
	/** @var string The registered wrapper protocol scheme. */
	public const PROTOCOL = 'prado-stream';

	/** @var mixed The stream context (populated by PHP on the wrapper instance). */
	public $context;

	/** @var StreamInterface The wrapped PSR-7 stream. */
	private StreamInterface $stream;

	/** @var string The mode the resource was opened with. */
	private string $mode;

	/**
	 * Returns a native PHP resource that proxies the given PSR-7 stream.
	 * @param StreamInterface $stream The stream to expose as a resource.
	 * @throws TInvalidDataValueException When the stream is neither readable nor writable.
	 * @return resource A native stream resource backed by $stream.
	 */
	public static function getResource(StreamInterface $stream)
	{
		self::register();
		if ($stream->isReadable()) {
			$mode = $stream->isWritable() ? 'r+' : 'r';
		} elseif ($stream->isWritable()) {
			$mode = 'w';
		} else {
			throw new TInvalidDataValueException('streamresourcewrapper_unusable_stream');
		}
		$context = stream_context_create([self::PROTOCOL => ['stream' => $stream]]);
		return fopen(self::PROTOCOL . '://stream', $mode, false, $context);
	}

	/**
	 * Registers the wrapper protocol once per process.
	 */
	public static function register(): void
	{
		if (!self::isRegistered()) {
			stream_wrapper_register(self::PROTOCOL, static::class);
		}
	}

	/**
	 * Indicates whether the wrapper protocol is registered in this process.
	 * @return bool Whether {@see PROTOCOL} is a registered stream wrapper.
	 */
	public static function isRegistered(): bool
	{
		return in_array(self::PROTOCOL, self::getRegisteredWrappers(), true);
	}

	/**
	 * Returns the stream-wrapper protocols registered in this process.
	 * @return array<int, string> The registered wrapper protocols ({@see stream_get_wrappers()}).
	 */
	public static function getRegisteredWrappers(): array
	{
		return stream_get_wrappers();
	}

	/**
	 * Binds the wrapper to the StreamInterface carried in the stream context.
	 * @param string $path The opened URL (ignored).
	 * @param string $mode The fopen mode used by {@see getResource()}.
	 * @param int $options PHP stream-wrapper option flags.
	 * @param ?string &$opened_path Unused.
	 * @return bool Whether a StreamInterface was found in the context.
	 */
	public function stream_open(string $path, string $mode, int $options, ?string &$opened_path = null): bool
	{
		$contextOptions = stream_context_get_options($this->context);
		if (!isset($contextOptions[self::PROTOCOL]['stream']) || !($contextOptions[self::PROTOCOL]['stream'] instanceof StreamInterface)) {
			return false;
		}
		$this->mode = $mode;
		$this->stream = $contextOptions[self::PROTOCOL]['stream'];
		return true;
	}

	/**
	 * Reads from the underlying stream.
	 * @param int $count The maximum number of bytes to read.
	 * @return string The bytes read.
	 */
	public function stream_read(int $count): string
	{
		return $this->stream->read($count);
	}

	/**
	 * Writes to the underlying stream.
	 * @param string $data The bytes to write.
	 * @return int The number of bytes written.
	 */
	public function stream_write(string $data): int
	{
		return $this->stream->write($data);
	}

	/**
	 * Returns the position of the underlying stream.
	 * @return int The current position.
	 */
	public function stream_tell(): int
	{
		return $this->stream->tell();
	}

	/**
	 * Reports end-of-file on the underlying stream.
	 * @return bool Whether the stream is at end of file.
	 */
	public function stream_eof(): bool
	{
		return $this->stream->eof();
	}

	/**
	 * Seeks the underlying stream when it is seekable.
	 * @param int $offset The seek offset.
	 * @param int $whence SEEK_SET, SEEK_CUR or SEEK_END.
	 * @return bool Whether the seek succeeded.
	 */
	public function stream_seek(int $offset, int $whence): bool
	{
		if (!$this->stream->isSeekable()) {
			return false;
		}
		$this->stream->seek($offset, $whence);
		return true;
	}

	/**
	 * Returns a synthetic {@see fstat()} for the proxied stream.
	 * @return array The stat array, with a mode derived from the open mode and the stream size.
	 */
	public function stream_stat(): array
	{
		static $modeMap = [
			'r' => 33060,
			'r+' => 33206,
			'w' => 33188,
		];
		return [
			'dev' => 0, 'ino' => 0, 'mode' => $modeMap[$this->mode] ?? 33206, 'nlink' => 0,
			'uid' => 0, 'gid' => 0, 'rdev' => 0, 'size' => $this->stream->getSize() ?? 0,
			'atime' => 0, 'mtime' => 0, 'ctime' => 0, 'blksize' => -1, 'blocks' => -1,
		];
	}

	/**
	 * Returns a zeroed stat array; the wrapper has no addressable URL to stat.
	 * @param string $path The (ignored) URL being stat-ed.
	 * @param int $flags The stat flags.
	 * @return array A zeroed stat array.
	 */
	public function url_stat(string $path, int $flags): array
	{
		return [
			'dev' => 0, 'ino' => 0, 'mode' => 0, 'nlink' => 0,
			'uid' => 0, 'gid' => 0, 'rdev' => 0, 'size' => 0,
			'atime' => 0, 'mtime' => 0, 'ctime' => 0, 'blksize' => -1, 'blocks' => -1,
		];
	}
}
