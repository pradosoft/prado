<?php

/**
 * TFlushableStreamTrait trait file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO;

/**
 * TFlushableStreamTrait trait.
 *
 * Adds a {@see flush()} to output-bound standard streams ({@see TStdOutStream},
 * {@see TOutputStream}).  On top of the resource-level {@see TResource::fflush()},
 * it pushes any active PHP output buffer ({@see ob_flush()}) and the SAPI buffer
 * ({@see flush()}) toward the client/terminal.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TFlushableStreamTrait
{
	/**
	 * Flushes buffered output: the stream buffer, any active output buffer, and
	 * the SAPI buffer.
	 * @return bool Whether the underlying {@see TResource::fflush()} succeeded.
	 */
	public function flush(): bool
	{
		$result = $this->fflush();
		if (ob_get_level() > 0) {
			@ob_flush();
		}
		flush();
		return $result;
	}
}
