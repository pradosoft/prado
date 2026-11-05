<?php

/**
 * TTempStream class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO;

use Prado\Exceptions\TIOException;

/**
 * TTempStream class.
 *
 * Provides a {@see TStream} over 'php://temp', a readable, writable stream held in
 * memory up to a threshold, then spilled to a temporary file.  It suits buffers of
 * unknown size.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTempStream extends TStream
{
	public const URI = 'php://temp';

	/** @var int The default in-memory threshold before spilling to disk (2 MiB). */
	public const DEFAULT_MAX_MEMORY = 2097152;

	/**
	 * Opens a temp stream with the given in-memory threshold.
	 * @param int $maxMemoryBytes Bytes kept in memory before spilling to disk.
	 * @param string $mode The fopen mode. Default 'r+b'.
	 * @throws TIOException When the temp stream cannot be opened.
	 */
	public function __construct(int $maxMemoryBytes = self::DEFAULT_MAX_MEMORY, string $mode = 'r+b')
	{
		$uri = static::URI . '/maxmemory:' . $maxMemoryBytes;
		$resource = @fopen($uri, $mode);
		if ($resource === false) {
			throw new TIOException('stream_open_failed', $uri, $mode);
		}
		parent::__construct($resource);
	}
}
