<?php

/**
 * TStdOutStream class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO;

use Prado\Exceptions\TIOException;

/**
 * TStdOutStream class.
 *
 * Provides a write-only, flushable {@see TStream} over 'php://stdout'.  It is
 * non-owning: the process standard output is never closed by this object.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TStdOutStream extends TStream
{
	use TFlushableStreamTrait;

	public const URI = 'php://stdout';

	/**
	 * Opens the process standard output.
	 * @throws TIOException When standard output cannot be opened.
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
