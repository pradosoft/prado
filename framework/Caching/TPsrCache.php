<?php

/**
 * TPsrCache class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Caching;

use Prado\Exceptions\TConfigurationException;
use Prado\IModuleDependency;
use Prado\Prado;
use Prado\Util\Log\TLogger;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * TPsrCache class.
 *
 * TPsrCache is a {@see TCache} module whose storage is a PSR-16 {@see CacheInterface}
 * or a PSR-6 {@see CacheItemPoolInterface}, such as a Symfony or Laminas cache adapter.
 * The PSR cache then serves Prado as the primary cache or as any other cache module,
 * with {@see TCache}'s key prefixing, dependencies, and `ArrayAccess` on top.
 *
 * ## Configuration
 *
 * {@see setBackingCacheId BackingCacheId} names an application module that implements
 * either interface. TPsrCache declares that module as a required {@see IModuleDependency},
 * so the application initializes it first. {@see setBackingCache BackingCache} injects an
 * instance instead. {@see init()} requires one of the two.
 *
 * Replacing a non-empty `BackingCacheId` logs a {@see TLogger::WARNING} and drops the
 * current backing. A backing that is a {@see TPsrSimpleCache} or {@see TPsrCacheItemPool}
 * of this module is rejected, since it would read and write back into the same cache.
 *
 * ```xml
 * <module id="symfonyCache" class="App\Caching\SymfonyCacheModule" />
 * <module id="cache" class="Prado\Caching\TPsrCache" BackingCacheId="symfonyCache" />
 * ```
 *
 * ```php
 * $cache = new TPsrCache();
 * $cache->setBackingCache(new \Symfony\Component\Cache\Psr16Cache($adapter));
 * $cache->setPrimaryCache(false);
 * $cache->init(null);
 * ```
 *
 * ## Storage
 *
 * | Operation | PSR-16 | PSR-6 |
 * |---|---|---|
 * | {@see getValue()} | `get($key, false)` | `getItem($key)`; the value on a hit, `false` otherwise |
 * | {@see setValue()} | `set($key, $data, $ttl)` | `getItem($key)->set($data)->expiresAfter($ttl)`, then `save()` |
 * | {@see deleteValue()} | `delete($key)` | `deleteItem($key)` |
 * | {@see flush()} | `clear()` | `clear()` |
 *
 * A Prado expiry of `0` becomes a `null` TTL, the PSR cache's own default. The keys sent
 * to the PSR cache are the hashed {@see TCache::generateUniqueKey()} keys, which both
 * standards accept. {@see addValue()} reads before it writes, so a concurrent request can
 * win the race, as with {@see TFileCache} and {@see TDbCache}.
 *
 * {@see TPsrSimpleCache} and {@see TPsrCacheItemPool} are the reverse adapters. They
 * present a Prado cache to a PSR-16 or PSR-6 consumer, and they are the cache access
 * layer for a new application and for one that intends to upgrade, since all caching
 * in Prado 5 is PSR compliant.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TPsrCache extends TCache implements IModuleDependency
{
	/** @var string module ID of the backing PSR cache; empty until configured */
	private string $_backingCacheId = '';

	/** @var null|CacheInterface|CacheItemPoolInterface the backing PSR cache; null until set or resolved */
	private CacheInterface|CacheItemPoolInterface|null $_backingCache = null;

	// ----------------------------------------------------------------- lifecycle

	/**
	 * Declares the backing module as a required dependency so that
	 * {@see \Prado\TApplication} initializes it before this cache, in every pass.
	 * @param bool $isPreInit `true` for the dyPreInit pass, `false` for init(); not used
	 * @return ?string the backing module ID, or null when none is configured
	 */
	public function getModuleDependencies(bool $isPreInit = false): ?string
	{
		$id = $this->getBackingCacheId();
		return $id === '' ? null : $id;
	}

	/**
	 * Initializes the cache module.
	 * @param null|array|\Prado\Xml\TXmlElement $config module configuration
	 * @throws TConfigurationException when neither {@see getBackingCacheId BackingCacheId} nor {@see getBackingCache BackingCache} is set
	 */
	public function init($config)
	{
		if ($this->getBackingCacheId() === '' && $this->getBackingCacheDirect() === null) {
			throw new TConfigurationException('psrcache_backing_cache_required');
		}
		parent::init($config);
	}

	/**
	 * @return bool whether the PSR-16 or PSR-6 interfaces are installed
	 */
	public static function getIsAvailable(): bool
	{
		return interface_exists(CacheInterface::class) || interface_exists(CacheItemPoolInterface::class);
	}

	// --------------------------------------------------------------- accessors

	/**
	 * @return string the stored module ID of the backing PSR cache
	 */
	protected function getBackingCacheIdDirect(): string
	{
		return $this->_backingCacheId;
	}

	/**
	 * @param string $value the module ID to store
	 */
	protected function setBackingCacheIdDirect(string $value): void
	{
		$this->_backingCacheId = $value;
	}

	/**
	 * @return string the module ID of the backing PSR cache
	 */
	public function getBackingCacheId(): string
	{
		return $this->getBackingCacheIdDirect();
	}

	/**
	 * Sets the module ID of the backing PSR cache. Replacing a non-empty ID logs a
	 * {@see TLogger::WARNING} and drops the current backing so the next operation
	 * resolves the new module.
	 * @param string $value the module ID of a module implementing {@see CacheInterface} or {@see CacheItemPoolInterface}
	 */
	public function setBackingCacheId(string $value): void
	{
		$current = $this->getBackingCacheIdDirect();
		if ($value === $current) {
			return;
		}
		if ($current !== '') {
			Prado::log(
				sprintf("TPsrCache.BackingCacheId changed from '%s' to '%s'.", $current, $value),
				TLogger::WARNING,
				'prado.caching'
			);
		}
		$this->setBackingCacheIdDirect($value);
		$this->setBackingCacheDirect(null);
	}

	/**
	 * @return null|CacheInterface|CacheItemPoolInterface the stored backing PSR cache, or null when not yet set or resolved
	 */
	protected function getBackingCacheDirect(): CacheInterface|CacheItemPoolInterface|null
	{
		return $this->_backingCache;
	}

	/**
	 * @param null|CacheInterface|CacheItemPoolInterface $value the backing PSR cache to store
	 */
	protected function setBackingCacheDirect(CacheInterface|CacheItemPoolInterface|null $value): void
	{
		$this->_backingCache = $value;
	}

	/**
	 * Returns the backing PSR cache, resolving it through {@see resolveBackingCache()}
	 * on first call.
	 * @throws TConfigurationException when no backing is configured, the module is missing or of the wrong type, or the backing adapts this cache
	 * @return CacheInterface|CacheItemPoolInterface the backing PSR cache
	 */
	public function getBackingCache(): CacheInterface|CacheItemPoolInterface
	{
		$cache = $this->getBackingCacheDirect();
		if ($cache === null) {
			$cache = $this->resolveBackingCache();
			$this->setBackingCacheDirect($cache);
		}
		$this->ensureNotRecursive($cache);
		return $cache;
	}

	/**
	 * Sets the backing PSR cache directly, replacing a resolved one. Null drops it so
	 * the next operation resolves the backing again.
	 * @param null|CacheInterface|CacheItemPoolInterface $value the PSR cache to store in
	 */
	public function setBackingCache(CacheInterface|CacheItemPoolInterface|null $value): void
	{
		$this->setBackingCacheDirect($value);
	}

	/**
	 * Resolves the backing PSR cache from {@see getBackingCacheId BackingCacheId}.
	 * @throws TConfigurationException when the ID is empty
	 * @throws TConfigurationException when the ID names no application module
	 * @throws TConfigurationException when the module implements neither PSR interface
	 * @return CacheInterface|CacheItemPoolInterface the backing PSR cache
	 */
	protected function resolveBackingCache(): CacheInterface|CacheItemPoolInterface
	{
		$id = $this->getBackingCacheId();
		if ($id === '') {
			throw new TConfigurationException('psrcache_backing_cache_required');
		}
		$cache = $this->getApplication()?->getModule($id);
		if ($cache === null) {
			throw new TConfigurationException('psrcache_backing_cache_not_found', $id);
		}
		if (!($cache instanceof CacheInterface) && !($cache instanceof CacheItemPoolInterface)) {
			throw new TConfigurationException('psrcache_invalid_backing_cache_type', $id);
		}
		return $cache;
	}

	/**
	 * Rejects a backing that is a PSR adapter of this cache.
	 * @param CacheInterface|CacheItemPoolInterface $cache the backing PSR cache
	 * @throws TConfigurationException when the backing's own backing cache is this cache
	 */
	protected function ensureNotRecursive(CacheInterface|CacheItemPoolInterface $cache): void
	{
		if (($cache instanceof TPsrSimpleCache || $cache instanceof TPsrCacheItemPool) && $cache->getBackingCache() === $this) {
			throw new TConfigurationException('psrcache_backing_cache_recursive', $cache::class);
		}
	}

	// ----------------------------------------------------------------- TCache storage

	/**
	 * Reads a key from the PSR cache.
	 * @param string $key the unique key
	 * @return false|mixed the stored value, or false on a miss
	 */
	protected function getValue($key)
	{
		$cache = $this->getBackingCache();
		if ($cache instanceof CacheInterface) {
			return $cache->get($key, false);
		}
		$item = $cache->getItem($key);
		return $item->isHit() ? $item->get() : false;
	}

	/**
	 * Writes a key to the PSR cache.
	 * @param string $key the unique key
	 * @param mixed $value the value to store
	 * @param int $expire the expiry in seconds; 0 for the PSR cache's default
	 * @return bool whether the PSR cache stored the value
	 */
	protected function setValue($key, $value, $expire)
	{
		$ttl = (int) $expire > 0 ? (int) $expire : null;
		$cache = $this->getBackingCache();
		if ($cache instanceof CacheInterface) {
			return $cache->set($key, $value, $ttl);
		}
		$item = $cache->getItem($key);
		$item->set($value)->expiresAfter($ttl);
		return $cache->save($item);
	}

	/**
	 * Writes a key to the PSR cache when it is absent. The read and the write are
	 * separate operations, so a concurrent request can win the race.
	 * @param string $key the unique key
	 * @param mixed $value the value to store
	 * @param int $expire the expiry in seconds; 0 for the PSR cache's default
	 * @return bool whether the PSR cache stored the value; false when the key exists
	 */
	protected function addValue($key, $value, $expire)
	{
		if ($this->getValue($key) !== false) {
			return false;
		}
		return $this->setValue($key, $value, $expire);
	}

	/**
	 * Deletes a key from the PSR cache.
	 * @param string $key the unique key
	 * @return bool whether the PSR cache deleted the key
	 */
	protected function deleteValue($key)
	{
		$cache = $this->getBackingCache();
		return $cache instanceof CacheInterface ? $cache->delete($key) : $cache->deleteItem($key);
	}

	/**
	 * Clears the PSR cache, including entries written by its other users.
	 * @return bool whether the PSR cache cleared
	 */
	public function flush()
	{
		return $this->getBackingCache()->clear();
	}

	// -------------------------------------------------- serialization

	/**
	 * Excludes a backing resolved from {@see getBackingCacheId BackingCacheId} from
	 * serialization, since it is re-resolved from the module registry, and otherwise
	 * excludes the empty ID.
	 * @param array $exprops excluded-properties list, passed by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		if ($this->getBackingCacheIdDirect() === '') {
			$exprops[] = "\0" . __CLASS__ . "\0_backingCacheId";
		} else {
			$exprops[] = "\0" . __CLASS__ . "\0_backingCache";
		}
	}
}
