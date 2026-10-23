<?php

/**
 * TPsrSimpleCache class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Caching;

use Prado\IModuleDependency;
use Prado\TModule;
use Psr\SimpleCache\CacheInterface;

/**
 * TPsrSimpleCache class.
 *
 * TPsrSimpleCache adapts a Prado {@see ICache} to the PSR-16 {@see CacheInterface}.
 * A library that accepts a PSR-16 cache reads and writes a Prado cache module through
 * this module. {@see TPsrCacheItemPool} is the PSR-6 adapter of the same backing.
 *
 * {@see TPsrCacheBackingTrait} resolves the backing: {@see setBackingCache BackingCache}
 * when set, otherwise the module named by {@see setBackingCacheId BackingCacheId},
 * otherwise the application's primary cache. The trait also documents the key rules,
 * {@see setKeyPrefix KeyPrefix}, {@see setDefaultExpire DefaultExpire}, and the value
 * envelope that keeps empty values.
 *
 * Configure in `application.xml`:
 * ```xml
 * <module id="cache" class="Prado\Caching\TFileCache" />
 * <module id="psrcache" class="Prado\Caching\TPsrSimpleCache" BackingCacheId="cache" KeyPrefix="lib." DefaultExpire="3600" />
 * ```
 *
 * ```php
 * return [
 *     'modules' => [
 *         'psrcache' => [
 *             'class' => 'Prado\Caching\TPsrSimpleCache',
 *             'properties' => ['BackingCacheId' => 'cache', 'KeyPrefix' => 'lib.', 'DefaultExpire' => '3600'],
 *         ],
 *     ],
 * ];
 * ```
 *
 * Then hand the module to the library:
 * ```php
 * $library->setCache($app->getModule('psrcache'));
 * ```
 *
 * Or instantiate directly:
 * ```php
 * $psr = new TPsrSimpleCache();
 * $psr->setBackingCache($app->getCache());
 * $psr->set('token', $value, 300);
 * ```
 *
 * {@see clear()} flushes the whole backing cache, including entries written by its
 * other users, and returns false when the backing does not support {@see ICache::flush()}.
 *
 * ## Prado 5
 *
 * All caching in Prado 5 is PSR compliant. Code that reaches the cache through this
 * module carries over unchanged. A new application accesses its cache through this
 * module or {@see TPsrCacheItemPool}, and an application that intends to upgrade
 * moves its cache access to them.
 *
 * {@see TPsrCache} is the reverse adapter. It is a {@see TCache} stored in a PSR-16 or
 * PSR-6 cache.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TPsrSimpleCache extends TModule implements CacheInterface, IModuleDependency
{
	use TPsrCacheBackingTrait;

	// ----------------------------------------------------------------- CacheInterface

	/**
	 * Fetches a value from the backing cache.
	 * @param string $key the key
	 * @param mixed $default the value returned on a miss
	 * @throws TPsrCacheInvalidArgumentException when the key is illegal
	 * @return mixed the cached value, or `$default` on a miss
	 */
	public function get(string $key, mixed $default = null): mixed
	{
		$entry = $this->readEntry($this->validateKey($key));
		return $entry === null ? $default : $entry[0];
	}

	/**
	 * Stores a value in the backing cache. A `null` TTL applies {@see getDefaultExpire DefaultExpire};
	 * a TTL of zero or less deletes the key.
	 * @param string $key the key
	 * @param mixed $value the value to store
	 * @param null|\DateInterval|int $ttl the TTL
	 * @throws TPsrCacheInvalidArgumentException when the key is illegal
	 * @return bool whether the backing cache stored the value
	 */
	public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
	{
		return $this->storeEntry($this->validateKey($key), $value, $ttl);
	}

	/**
	 * Deletes a key from the backing cache.
	 * @param string $key the key
	 * @throws TPsrCacheInvalidArgumentException when the key is illegal
	 * @return bool whether the backing cache deleted the key
	 */
	public function delete(string $key): bool
	{
		return $this->deleteEntry($this->validateKey($key));
	}

	/**
	 * Flushes the backing cache, including entries written by its other users.
	 * @return bool whether the backing cache flushed; false when it does not support {@see ICache::flush()}
	 */
	public function clear(): bool
	{
		return $this->clearEntries();
	}

	/**
	 * Fetches several values. Every key is validated before any is read.
	 * @param iterable $keys the keys
	 * @param mixed $default the value returned for each miss
	 * @throws TPsrCacheInvalidArgumentException when a key is illegal
	 * @return array<string, mixed> the values by key
	 */
	public function getMultiple(iterable $keys, mixed $default = null): iterable
	{
		$values = [];
		foreach ($this->validateKeys($keys) as $key) {
			$entry = $this->readEntry($key);
			$values[$key] = $entry === null ? $default : $entry[0];
		}
		return $values;
	}

	/**
	 * Stores several values with one TTL. Every key is validated before any is written.
	 * @param iterable $values the values by key
	 * @param null|\DateInterval|int $ttl the TTL, as for {@see set()}
	 * @throws TPsrCacheInvalidArgumentException when a key is illegal
	 * @return bool whether the backing cache stored every value
	 */
	public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
	{
		$entries = [];
		foreach ($values as $key => $value) {
			$entries[] = [$this->validateKey($key), $value];
		}
		$ok = true;
		foreach ($entries as [$key, $value]) {
			$ok = $this->storeEntry($key, $value, $ttl) && $ok;
		}
		return $ok;
	}

	/**
	 * Deletes several keys. Every key is validated before any is deleted.
	 * @param iterable $keys the keys
	 * @throws TPsrCacheInvalidArgumentException when a key is illegal
	 * @return bool whether the backing cache deleted every key
	 */
	public function deleteMultiple(iterable $keys): bool
	{
		$ok = true;
		foreach ($this->validateKeys($keys) as $key) {
			$ok = $this->deleteEntry($key) && $ok;
		}
		return $ok;
	}

	/**
	 * Returns whether the backing cache holds a key.
	 * @param string $key the key
	 * @throws TPsrCacheInvalidArgumentException when the key is illegal
	 * @return bool whether the key is cached
	 */
	public function has(string $key): bool
	{
		return $this->readEntry($this->validateKey($key)) !== null;
	}

	// -------------------------------------------------- serialization

	/**
	 * Excludes the backing properties at their defaults, and a backing cache resolved
	 * from {@see getBackingCacheId BackingCacheId}, from serialization.
	 * @param array $exprops excluded-properties list, passed by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$this->_addPsrCacheBackingZappable($exprops);
	}
}
