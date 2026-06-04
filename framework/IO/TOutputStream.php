<?php

/**
 * TOutputStream class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO;

use Prado\Exceptions\TIOException;

/**
 * TOutputStream class.
 *
 * Provides a write-only, flushable {@see TStream} over 'php://output'.  Writes pass
 * through PHP's output buffering toward the SAPI (echo equivalent).  Owned: closing
 * it releases this per-request handle.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TOutputStream extends TStream
{
	use TFlushableStreamTrait;

	public const URI = 'php://output';

	/**
	 * Opens the output stream.
	 * @throws TIOException When the output stream cannot be opened.
	 */
	public function __construct()
	{
		$resource = @fopen(static::URI, 'wb');
		if ($resource === false) {
			throw new TIOException('stream_open_failed', static::URI, 'wb');
		}
		parent::__construct($resource);
	}
}
