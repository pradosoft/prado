<?php

/**
 * TCacheModuleIDTrait class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Caching;

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TCacheModuleIDTrait class.
 *
 * TCacheModuleIDTrait gives a class a {@see setCacheModuleID CacheModuleID} property
 * and resolves the {@see ICache} it names. An empty CacheModuleID resolves the
 * application's primary cache.
 *
 * {@see claimCacheKey()} stores a key once and reports whether this call stored it.
 * It backs single-use tokens. The claim is atomic on {@see TAPCCache}, {@see TMemCache},
 * {@see TRedisCache}, and {@see TEtcdCache}. {@see TFileCache} and {@see TDbCache}
 * leave a short race window between concurrent requests.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TCacheModuleIDTrait
{
	/** @var string the ID of the cache module; empty for the primary cache. */
	private string $_cacheModuleID = '';

	/**
	 * @return string the ID of the cache module. Defaults to '', the application's primary cache.
	 */
	public function getCacheModuleID(): string
	{
		return $this->_cacheModuleID;
	}

	/**
	 * @param string $value the ID of the cache module. Empty uses the application's primary cache.
	 */
	public function setCacheModuleID($value): void
	{
		$this->_cacheModuleID = TPropertyValue::ensureString($value);
	}

	/**
	 * Resolves the cache named by {@see getCacheModuleID CacheModuleID}.
	 * @param bool $required whether a missing primary cache throws instead of returning null.
	 * @throws TConfigurationException when CacheModuleID names no ICache module, or when
	 *   $required is true and the application has no primary cache.
	 * @return ?ICache the cache, or null when none is configured and $required is false.
	 */
	protected function resolveCacheModule(bool $required = true): ?ICache
	{
		$application = Prado::getApplication();
		if (($id = $this->getCacheModuleID()) !== '') {
			$cache = $application->getModule($id);
			if (!($cache instanceof ICache)) {
				throw new TConfigurationException('cachemoduleid_invalid', static::class, $id);
			}
			return $cache;
		}
		$cache = $application->getCache();
		if ($cache === null && $required) {
			throw new TConfigurationException('cachemoduleid_cache_required', static::class);
		}
		return $cache;
	}

	/**
	 * Stores $key in $cache when it is absent.
	 * @param ICache $cache the cache that records the claim.
	 * @param string $key the key to claim.
	 * @param int $ttl the seconds the claim lasts; values below 1 become 1.
	 * @return bool whether this call claimed the key; false when an earlier claim holds it.
	 */
	protected function claimCacheKey(ICache $cache, string $key, int $ttl): bool
	{
		return $cache->add($key, 1, max(1, $ttl));
	}
}
