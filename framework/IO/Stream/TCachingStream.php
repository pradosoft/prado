<?php

/**
 * TCachingStream class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Stream;

use Prado\IO\TStream;
use Psr\Http\Message\StreamInterface;

/**
 * TCachingStream class.
 *
 * Makes a non-seekable source seekable by caching bytes as they are read.  Each read
 * draws from the cache first and pulls any shortfall from the source, appending it to
 * the cache.  Seeking backward reads from the cache; seeking forward past the cache
 * pulls and stores the intervening bytes from the source.
 *
 * ```php
 * $remote = $client->send($request)->getBody();   // non-seekable
 * $seekable = new TCachingStream($remote);
 * $head = $seekable->read(16);
 * $seekable->rewind();                              // works, served from cache
 * ```
 *
 * The cache defaults to a `php://temp` stream, so large bodies spill to disk.  The cache
 * trades memory for seekability over a one-pass source.
 *
 * The stream is read-only: a write would desynchronize the cache from the source, so
 * {@see write()} throws.  A seek past the end of the source clamps to the last byte
 * cached.  {@see close()} and {@see detach()} release the source along with the cache.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCachingStream extends TStreamDecorator
{
	/** @var StreamInterface The non-seekable source being cached. */
	private StreamInterface $_remote;

	/**
	 * @param StreamInterface $remote The source stream to cache.
	 * @param ?StreamInterface $cache The seekable cache; null creates a php://temp stream.
	 */
	public function __construct(StreamInterface $remote, ?StreamInterface $cache = null)
	{
		parent::__construct($cache ?? TStream::fromTemp());
		$this->setRemoteDirect($remote);
	}

	//
	// ─── Self-encapsulated raw accessors ─────────────────────────────────────
	//

	/**
	 * Returns the source stream being cached.
	 * @return StreamInterface The source stream.
	 */
	public function getRemote(): StreamInterface
	{
		return $this->_remote;
	}

	/**
	 * Sets the raw source stream.
	 * @param StreamInterface $value The raw source stream.
	 */
	protected function setRemoteDirect(StreamInterface $value): void
	{
		$this->_remote = $value;
	}

	/**
	 * Returns the larger of the cached size and the known source size.
	 * @return ?int The size in bytes, or null when neither size is known.
	 */
	public function getSize(): ?int
	{
		$cache = $this->getStream()->getSize();
		$remote = $this->getRemote()->getSize();
		if ($remote === null) {
			return $cache;
		}
		return $cache === null ? $remote : max($cache, $remote);
	}

	/**
	 * Indicates that the stream is seekable.
	 * @return bool Always true.
	 */
	public function isSeekable(): bool
	{
		return true;
	}

	/**
	 * Indicates whether both the cache position and the source have reached the end.
	 * @return bool True when no more bytes are available.
	 */
	public function eof(): bool
	{
		return $this->getRemote()->eof() && $this->tell() >= (int) $this->getStream()->getSize();
	}

	/**
	 * Reads up to $length bytes, serving the cache first and the source for any shortfall.
	 * @param int $length The maximum number of bytes to read.
	 * @return string The bytes read.
	 */
	public function read(int $length): string
	{
		$data = $this->getStream()->read($length);
		$remaining = $length - strlen($data);
		if ($remaining > 0) {
			$fetched = $this->getRemote()->read($remaining);
			if ($fetched !== '') {
				$this->getStream()->write($fetched);
				$data .= $fetched;
			}
		}
		return $data;
	}

	/**
	 * Seeks to a position, filling the cache from the source when seeking past it.
	 * @param int $offset The target offset.
	 * @param int $whence SEEK_SET, SEEK_CUR, or SEEK_END.
	 * @throws \RuntimeException When the whence is invalid.
	 */
	public function seek(int $offset, int $whence = SEEK_SET): void
	{
		$byte = match ($whence) {
			SEEK_SET => $offset,
			SEEK_CUR => $this->tell() + $offset,
			SEEK_END => $this->cacheEntireSource() + $offset,
			default => throw new \RuntimeException('Invalid whence for TCachingStream::seek'),
		};
		$cached = (int) $this->getStream()->getSize();
		if ($byte > $cached) {
			$this->getStream()->seek(0, SEEK_END);
			while ((int) $this->getStream()->getSize() < $byte && !$this->getRemote()->eof()) {
				$need = $byte - (int) $this->getStream()->getSize();
				$fetched = $this->getRemote()->read($need);
				if ($fetched === '') {
					break;
				}
				$this->getStream()->write($fetched);
			}
		}
		$this->getStream()->seek(min($byte, (int) $this->getStream()->getSize()), SEEK_SET);
	}

	/**
	 * Reads the remainder of the source into the cache and returns the total cached size.
	 * @return int The cached size in bytes after the source is drained.
	 */
	private function cacheEntireSource(): int
	{
		$this->getStream()->seek(0, SEEK_END);
		while (!$this->getRemote()->eof()) {
			$fetched = $this->getRemote()->read(static::CHUNK_SIZE);
			if ($fetched === '') {
				break;
			}
			$this->getStream()->write($fetched);
		}
		return (int) $this->getStream()->getSize();
	}

	/**
	 * Indicates the stream is read-only; a write would desynchronize the cache from the source.
	 * @return bool Always false.
	 */
	public function isWritable(): bool
	{
		return false;
	}

	/**
	 * Rejects a write; a write would desynchronize the cache from the source.
	 * @param string $string The bytes (unused).
	 * @throws \RuntimeException Always.
	 */
	public function write(string $string): int
	{
		throw new \RuntimeException('Cannot write to a TCachingStream');
	}

	/**
	 * Closes the source along with the cache.
	 */
	public function close(): void
	{
		$this->getRemote()->close();
		parent::close();
	}

	/**
	 * Detaches the source along with the cache.
	 * @return null|resource The cache's detached resource, or null.
	 */
	public function detach()
	{
		$this->getRemote()->detach();
		return parent::detach();
	}
}
