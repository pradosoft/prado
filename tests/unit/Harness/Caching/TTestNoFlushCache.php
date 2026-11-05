<?php

/**
 * TTestNoFlushCache class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Caching;

use Prado\Exceptions\TNotSupportedException;

/**
 * TTestNoFlushCache is a {@see TTestCache} whose {@see flush()} throws
 * {@see TNotSupportedException}, as the {@see \Prado\Caching\TCache} default does.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestNoFlushCache extends TTestCache
{
	public function flush()
	{
		throw new TNotSupportedException('cache_flush_unsupported');
	}
}
