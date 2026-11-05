<?php

/**
 * TPhpStreamBehavior class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Behaviors;

use Prado\Exceptions\TIOException;
use Prado\IO\IResource;
use Prado\Util\TBehavior;
use Psr\Http\Message\StreamInterface;

/**
 * TPhpStreamBehavior class.
 *
 * Adds the PHP-name stream-access methods ({@see fread()}, {@see fwrite()},
 * {@see fputs()}, {@see fseek()}, {@see ftell()}, {@see feof()}, {@see fgets()},
 * {@see fgetc()}) to any {@see StreamInterface} it is attached to.  Because Prado
 * behaviors expose their public methods on the owner, attaching this behavior gives a
 * stream the familiar PHP file-function names, keeping those names off the PSR-7
 * {@see \Prado\IO\TStream} surface.
 *
 * ```php
 * $s = TStream::fromMemory();
 * $s->attachBehavior('php', new TPhpStreamBehavior());
 * $s->fwrite('abc');
 * $s->fseek(0);
 * $line = $s->fgets();
 * ```
 *
 * Most methods delegate to the PSR-7 surface ({@see fread()} to read, {@see fseek()} to
 * seek, and so on).  {@see fgets()} and {@see fgetc()} read the raw PHP resource directly
 * for line and character semantics, so they require a resource-backed owner ({@see
 * IResource}); they return false when no open resource is available.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TPhpStreamBehavior extends TBehavior
{
	/**
	 * Alias of {@see StreamInterface::read()}.
	 * @param int $length The maximum number of bytes to read.
	 * @return string The bytes read.
	 */
	public function fread(int $length): string
	{
		return $this->stream()->read($length);
	}

	/**
	 * Alias of {@see StreamInterface::write()}.
	 * @param string $data The bytes to write.
	 * @return int The number of bytes written.
	 */
	public function fwrite(string $data): int
	{
		return $this->stream()->write($data);
	}

	/**
	 * Alias of {@see StreamInterface::write()} (matches PHP's {@see fputs()}).
	 * @param string $data The bytes to write.
	 * @return int The number of bytes written.
	 */
	public function fputs(string $data): int
	{
		return $this->stream()->write($data);
	}

	/**
	 * Seeks the stream, returning success like PHP's {@see fseek()}.
	 * @param int $offset The stream offset.
	 * @param int $whence SEEK_SET, SEEK_CUR or SEEK_END.
	 * @return bool Whether the seek succeeded.
	 */
	public function fseek(int $offset, int $whence = SEEK_SET): bool
	{
		$stream = $this->stream();
		if (!$stream->isSeekable()) {
			return false;
		}
		try {
			$stream->seek($offset, $whence);
			return true;
		} catch (\Throwable $e) {
			return false;
		}
	}

	/**
	 * Alias of {@see StreamInterface::tell()}.
	 * @return int The current position.
	 */
	public function ftell(): int
	{
		return $this->stream()->tell();
	}

	/**
	 * Alias of {@see StreamInterface::eof()}.
	 * @return bool Whether the stream is at end of file.
	 */
	public function feof(): bool
	{
		return $this->stream()->eof();
	}

	/**
	 * Reads a line from the stream (PHP {@see fgets()} semantics).  It reads the raw
	 * resource directly, bypassing the PSR-7 read path.
	 * @param ?int $length Max bytes to read (including newline); null reads to EOL.
	 * @return false|string The line, or false at EOF, on error, or with no resource.
	 */
	public function fgets(?int $length = null): false|string
	{
		$resource = $this->resource();
		if (!is_resource($resource)) {
			return false;
		}
		return $length === null ? fgets($resource) : fgets($resource, $length);
	}

	/**
	 * Reads a single byte from the stream (PHP {@see fgetc()} semantics).  It reads the
	 * raw resource directly, bypassing the PSR-7 read path.
	 * @return false|string The byte read, or false at EOF or with no resource.
	 */
	public function fgetc(): false|string
	{
		$resource = $this->resource();
		if (!is_resource($resource)) {
			return false;
		}
		return fgetc($resource);
	}

	/**
	 * Returns the owner stream.
	 * @throws TIOException When the owner is not a stream.
	 * @return StreamInterface The owner stream.
	 */
	private function stream(): StreamInterface
	{
		$owner = $this->getOwner();
		if (!($owner instanceof StreamInterface)) {
			throw new TIOException('phpstream_no_stream_owner');
		}
		return $owner;
	}

	/**
	 * Returns the owner's raw PHP resource, or null when the owner is not resource-backed.
	 * @return mixed The owner's resource, or null.
	 */
	private function resource(): mixed
	{
		$owner = $this->getOwner();
		return $owner instanceof IResource ? $owner->getResource() : null;
	}
}
