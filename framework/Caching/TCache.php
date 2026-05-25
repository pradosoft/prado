<?php

/**
 * TCache and cache dependency classes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Caching;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TNotSupportedException;
use Prado\TModule;
use Prado\TPropertyValue;

/**
 * TCache class
 *
 * TCache is the base class for cache classes with different cache storage implementation.
 *
 * TCache implements the interface {@see \Prado\Caching\ICache} with the following methods,
 * - {@see get()} : retrieve the value with a key (if any) from cache
 * - {@see set()} : store the value with a key into cache
 * - {@see add()} : store the value only if cache does not have this key
 * - {@see delete()} : delete the value with the specified key from cache
 * - {@see flush()} : delete all values from cache
 *
 * Each value is associated with an expiration time. The {@see get()} operation
 * ensures that any expired value will not be returned. The expiration time is
 * specified by the number of seconds. An expiration time of 0 means never expire.
 *
 * By definition, cache does not ensure the existence of a value
 * even if it never expires. Cache is not meant to be a persistent storage.
 *
 * Child classes must implement the following methods:
 * - {@see getValue()}
 * - {@see setValue()}
 * - {@see addValue()}
 * - {@see deleteValue()}
 *
 * and optionally {@see flush()}.
 *
 * Since version 3.1.2, TCache implements the \ArrayAccess interface such that
 * the cache acts as an array.
 *
 * When loaded as a module, a TCache subclass registers itself with
 * {@see \Prado\TApplication} as the application cache (accessible via
 * {@see \Prado\TApplication::getCache()}) unless {@see setPrimaryCache PrimaryCache}
 * is set to `false`. Only one primary cache module may be loaded at a time.
 *
 * The {@see setKeyPrefix KeyPrefix} property may be used to namespace cache keys,
 * which is useful when multiple applications share the same cache backend.
 * By default it is set to {@see \Prado\TApplication::getUniqueID()}.
 *
 * XML configuration style, using {@see \Prado\Caching\TAPCCache} as a representative example:
 * ```xml
 * <modules>
 *   <module id="cache" class="Prado\Caching\TMemCache"
 *       PrimaryCache="true" KeyPrefix="myapp-" />
 * </modules>
 * ```
 * where {@see getPrimaryCache PrimaryCache} and {@see getKeyPrefix KeyPrefix} are
 * configurable properties inherited by all TCache subclasses.
 *
 * PHP configuration style:
 * ```php
 * return [
 *     'modules' => [
 *         'cache' => [
 *             'class' => 'Prado\Caching\TMemCache',
 *             'properties' => [
 *                 'PrimaryCache' => 'true',
 *                 'KeyPrefix' => 'myapp-',
 *             ],
 *         ],
 *     ],
 * ];
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
abstract class TCache extends TModule implements ICache, \ArrayAccess
{
	public const DEFAULT_PREFIX = '';

	/** @var ?string unique key prefix for cached values, or null before initialization */
	private ?string $_prefix = null;
	/** @var bool whether this module is used as the primary application cache */
	private bool $_primary = true;

	// =========================================================================
	// Lifecycle
	// =========================================================================

	public function __construct()
	{
		$this->setKeyPrefix(static::DEFAULT_PREFIX);
		parent::__construct();
	}

	/**
	 * Initializes the cache module.
	 * This method initializes the cache key prefix and registers the cache module
	 * with the application if the cache is primary.
	 * @param \Prado\Xml\TXmlElement $config the module configuration
	 */
	public function init($config)
	{
		if ($this->getKeyPrefixDirect() === null) {
			$this->setKeyPrefix($this->getApplication()?->getUniqueID() ?? '');
		}
		if ($this->getPrimaryCache()) {
			if ($this->getApplication()?->getCache() !== null) {
				throw new TConfigurationException('cache_primary_duplicated', static::class);
			}
			$this->setAppCache();
		}
		parent::init($config);
	}

	/**
	 * Registers this module as the application cache when an application is available.
	 * Called during {@see init()}; may also be called by behaviors or subclasses.
	 * @since 4.4.0
	 */
	protected function setAppCache(): void
	{
		$this->getApplication()?->setCache($this);
	}

	// =========================================================================
	// Property Getters/Setters
	// =========================================================================

	/**
	 * @return bool whether this cache module is used as primary/system cache.
	 * A primary cache is used by PRADO core framework to cache data such as
	 * parsed templates, themes, etc.
	 */
	public function getPrimaryCache(): bool
	{
		return $this->_primary;
	}

	/**
	 * @param bool $value whether this cache module is used as primary/system cache. Defaults to true.
	 */
	public function setPrimaryCache(bool $value): void
	{
		$this->_primary = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return string a unique prefix for the keys of cached values.
	 * If it is not explicitly set, it will take the value of {@see \Prado\TApplication::getUniqueID}.
	 */
	public function getKeyPrefix(): string
	{
		return $this->getKeyPrefixDirect() ?? '';
	}

	/**
	 * @param string $value a unique prefix for the keys of cached values
	 */
	public function setKeyPrefix(string $value): void
	{
		$this->setKeyPrefixDirect($value);
	}

	/**
	 * @return string a unique prefix for the keys of cached values.
	 * If it is not explicitly set, it will take the value of {@see \Prado\TApplication::getUniqueID}.
	 * @since 4.4.0
	 */
	protected function getKeyPrefixDirect(): ?string
	{
		return $this->_prefix;
	}

	/**
	 * @param string $value a unique prefix for the keys of cached values
	 * @since 4.4.0
	 */
	protected function setKeyPrefixDirect(string $value): void
	{
		$this->_prefix = $value;
	}

	/**
	 * @param string $key a key identifying a value to be cached
	 * @return string a key generated from the provided key which ensures the uniqueness across applications
	 */
	protected function generateUniqueKey(string $key): string
	{
		return $this->hashToken($this->generateToken($key));
	}

	/**
	 * @param string $key a key identifying a value to be cached
	 * @return string a key generated from the provided key which ensures the uniqueness across applications
	 * @since 4.4.0
	 */
	protected function generateToken(string $key): string
	{
		return $this->getKeyPrefix() . $key;
	}

	/**
	 * Hashes a token string for use as a cache key.
	 * Override in a subclass to substitute a different hashing algorithm.
	 * @param string $token the raw token to hash
	 * @return string the hashed token
	 * @since 4.4.0
	 */
	protected function hashToken(string $token): string
	{
		return sha1($token);
	}

	// =========================================================================
	// ICache implementation
	// =========================================================================

	/**
	 * Retrieves a value from cache with a specified key.
	 * @param string $id a key identifying the cached value
	 * @return mixed the value stored in cache, false if the value is not in the cache or expired.
	 */
	public function get(string $id): mixed
	{
		if (($data = $this->getValue($this->generateUniqueKey($id))) !== false) {
			if (!is_array($data)) {
				return false;
			}
			if (!($data[1] instanceof ICacheDependency) || !$data[1]->getHasChanged()) {
				return $data[0];
			}
		}
		return false;
	}

	/**
	 * Stores a value identified by a key into cache.
	 * If the cache already contains such a key, the existing value and
	 * expiration time will be replaced with the new ones. If the value is
	 * empty, the cache key will be deleted.
	 *
	 * @param string $id the key identifying the value to be cached
	 * @param mixed $value the value to be cached
	 * @param int $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @param ?ICacheDependency $dependency dependency of the cached item. If the dependency changes, the item is labeled invalid.
	 * @return bool true if the value is successfully stored into cache, false otherwise
	 */
	public function set(string $id, mixed $value, int $expire = 0, ?ICacheDependency $dependency = null): bool
	{
		if (empty($value) && $expire === 0) {
			return $this->delete($id);
		} else {
			$data = [$value, $dependency];
			return $this->setValue($this->generateUniqueKey($id), $data, $expire);
		}
	}

	/**
	 * Stores a value identified by a key into cache if the cache does not contain this key.
	 * Nothing will be done if the cache already contains the key or if value is empty.
	 * @param string $id the key identifying the value to be cached
	 * @param mixed $value the value to be cached
	 * @param int $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @param ICacheDependency $dependency dependency of the cached item. If the dependency changes, the item is labeled invalid.
	 * @return bool true if the value is successfully stored into cache, false otherwise
	 */
	public function add(string $id, mixed $value, int $expire = 0, $dependency = null): bool
	{
		if (empty($value) && $expire === 0) {
			return false;
		}
		$data = [$value, $dependency];
		return $this->addValue($this->generateUniqueKey($id), $data, $expire);
	}

	/**
	 * Deletes a value with the specified key from cache
	 * @param string $id the key of the value to be deleted
	 * @return bool if no error happens during deletion
	 */
	public function delete(string $id): bool
	{
		return $this->deleteValue($this->generateUniqueKey($id));
	}

	/**
	 * Deletes all values from cache.
	 * Be careful of performing this operation if the cache is shared by multiple applications.
	 * @throws TNotSupportedException if this method is not overridden by child classes
	 */
	public function flush(): void
	{
		throw new TNotSupportedException('cache_flush_unsupported');
	}

	// =========================================================================
	// Subclass Provider Methods
	// =========================================================================

	/*
	 * Returns whether this cache backend's prerequisites (extensions, services) are met.
	 * @since 4.4.0
	 */
	abstract public static function getIsAvailable(): bool;

	/**
	 * Retrieves a value from cache with a specified key.
	 * Uniqueness and dependency are handled by {@see get()}; implement storage retrieval only.
	 * Returns `false` on a cache miss; returns the stored mixed value (an array of
	 * `[$value, $dependency]`) on a hit.
	 * @param string $key a unique key identifying the cached value
	 * @return false|mixed the stored value on a hit, or `false` on a miss or expiry.
	 */
	abstract protected function getValue(string $key): mixed;

	/**
	 * Stores a value identified by a key in cache.
	 * Uniqueness and dependency are handled by {@see set()}; implement storage write only.
	 * The `$value` parameter receives a `[$value, $dependency]` array; backends are
	 * responsible for any serialization required by their storage layer.
	 * @param string $key the key identifying the value to be cached
	 * @param mixed $value the value to be cached (a two-element array of value + dependency)
	 * @param int $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return bool true if the value is successfully stored into cache, false otherwise
	 */
	abstract protected function setValue(string $key, mixed $value, int $expire): bool;

	/**
	 * Stores a value identified by a key into cache if the cache does not contain this key.
	 * Uniqueness and dependency are handled by {@see add()}; implement storage write only.
	 * The `$value` parameter receives a `[$value, $dependency]` array; backends are
	 * responsible for any serialization required by their storage layer.
	 * @param string $key the key identifying the value to be cached
	 * @param mixed $value the value to be cached (a two-element array of value + dependency)
	 * @param int $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return bool true if the value is successfully stored into cache, false otherwise
	 */
	abstract protected function addValue(string $key, mixed $value, int $expire): bool;

	/**
	 * Deletes a value with the specified key from cache.
	 * @param string $key the key of the value to be deleted
	 * @return bool true if no error happens during deletion
	 */
	abstract protected function deleteValue(string $key): bool;

	// =========================================================================
	// \ArrayAccess
	// =========================================================================

	/**
	 * Returns whether there is a cache entry with a specified key.
	 * This method is required by the interface \ArrayAccess.
	 * @param string $id a key identifying the cached value
	 * @return bool
	 */
	public function offsetExists($id): bool
	{
		return $this->get($id) !== false;
	}

	/**
	 * Retrieves the value from cache with a specified key.
	 * This method is required by the interface \ArrayAccess.
	 * @param string $id a key identifying the cached value
	 * @return false|mixed the value stored in cache, false if the value is not in the cache or expired.
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet($id)
	{
		return $this->get($id);
	}

	/**
	 * Stores the value identified by a key into cache.
	 * If the cache already contains such a key, the existing value will be
	 * replaced with the new ones. To add expiration and dependencies, use the set() method.
	 * This method is required by the interface \ArrayAccess.
	 * @param string $id the key identifying the value to be cached
	 * @param mixed $value the value to be cached
	 */
	public function offsetSet($id, $value): void
	{
		$this->set($id, $value);
	}

	/**
	 * Deletes the value with the specified key from cache
	 * This method is required by the interface \ArrayAccess.
	 * @param string $id the key of the value to be deleted
	 */
	public function offsetUnset($id): void
	{
		$this->delete($id);
	}
}
