<?php

/**
 * TMemoryStream class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO;

use Prado\Exceptions\TIOException;

/**
 * TMemoryStream class.
 *
 * Provides a {@see TStream} over 'php://memory', a readable, writable stream kept
 * entirely in memory.  Use {@see TTempStream} when the data may be large enough to
 * spill to disk.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TMemoryStream extends TStream
{
	public const URI = 'php://memory';

	/**
	 * Opens an in-memory stream.
	 * @param string $mode The fopen mode. Default 'r+b'.
	 * @throws TIOException When the memory stream cannot be opened.
	 */
	public function __construct(string $mode = 'r+b')
	{
		$resource = @fopen(static::URI, $mode);
		if ($resource === false) {
			throw new TIOException('stream_open_failed', static::URI, $mode);
		}
		parent::__construct($resource);
	}
}
