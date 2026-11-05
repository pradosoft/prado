<?php

/**
 * TInputStream class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO;

use Prado\Exceptions\TIOException;

/**
 * TInputStream class.
 *
 * Provides a read-only {@see TStream} over 'php://input', the raw HTTP request
 * body.  Owned: closing it releases this per-request handle.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TInputStream extends TStream
{
	public const URI = 'php://input';

	/**
	 * Opens the request input stream.
	 * @throws TIOException When the request input stream cannot be opened.
	 */
	public function __construct()
	{
		$resource = @fopen(static::URI, 'rb');
		if ($resource === false) {
			throw new TIOException('stream_open_failed', static::URI, 'rb');
		}
		parent::__construct($resource);
	}
}
