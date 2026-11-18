<?php

/**
 * TPsrCacheException class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Caching;

use Prado\Exceptions\TConfigurationException;

/**
 * TPsrCacheException class.
 *
 * TPsrCacheException is the {@see TConfigurationException} that {@see TPsrSimpleCache}
 * and {@see TPsrCacheItemPool} throw when their backing cache is missing or is not an
 * {@see ICache}. It implements the PSR-6 {@see \Psr\Cache\CacheException} and PSR-16
 * {@see \Psr\SimpleCache\CacheException} marker interfaces, so a caller written against
 * either standard catches it as a cache error and a Prado caller catches it as a
 * configuration error.
 *
 * On Prado 4.3, TPsrCacheException requires the `psr/cache` and `psr/simple-cache`
 * packages, version 2 or 3.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TPsrCacheException extends TConfigurationException implements \Psr\Cache\CacheException, \Psr\SimpleCache\CacheException
{
}
