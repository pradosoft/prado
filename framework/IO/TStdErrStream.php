<?php

/**
 * TStdErrStream class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO;

use Prado\Exceptions\TIOException;

/**
 * TStdErrStream class.
 *
 * Provides a write-only {@see TStream} over 'php://stderr'.  It is non-owning: the
 * process standard error is never closed by this object.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TStdErrStream extends TStream
{
	public const URI = 'php://stderr';

	/**
	 * Opens the process standard error.
	 * @throws TIOException When standard error cannot be opened.
	 */
	public function __construct()
	{
		parent::__construct();
		$resource = @fopen(static::URI, 'wb');
		if ($resource === false) {
			throw new TIOException('stream_open_failed', static::URI, 'wb');
		}
		$this->attachResource($resource, false);
	}
}
