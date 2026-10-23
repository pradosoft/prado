<?php

/**
 * TPsrCacheInvalidArgumentException class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Caching;

use Prado\Exceptions\TInvalidDataValueException;

/**
 * TPsrCacheInvalidArgumentException class.
 *
 * TPsrCacheInvalidArgumentException is the {@see TInvalidDataValueException} that
 * {@see TPsrSimpleCache} and {@see TPsrCacheItemPool} throw for a key that is not a
 * legal PSR cache key. It implements the PSR-6 {@see \Psr\Cache\InvalidArgumentException}
 * and PSR-16 {@see \Psr\SimpleCache\InvalidArgumentException} interfaces, as both
 * standards require of an illegal key.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TPsrCacheInvalidArgumentException extends TInvalidDataValueException implements \Psr\Cache\InvalidArgumentException, \Psr\SimpleCache\InvalidArgumentException
{
}
