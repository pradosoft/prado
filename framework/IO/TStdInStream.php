<?php

/**
 * TStdInStream class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO;

use Prado\Exceptions\TIOException;

/**
 * TStdInStream class.
 *
 * Provides a read-only {@see TStream} over 'php://stdin'.  It is non-owning: the
 * process standard input is never closed by this object.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TStdInStream extends TStream
{
	public const URI = 'php://stdin';

	/**
	 * Opens the process standard input.
	 * @throws TIOException When standard input cannot be opened.
	 */
	public function __construct()
	{
		parent::__construct();
		$resource = @fopen(static::URI, 'rb');
		if ($resource === false) {
			throw new TIOException('stream_open_failed', static::URI, 'rb');
		}
		$this->attachResource($resource, false);
	}
}
