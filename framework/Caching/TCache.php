<?php
/**
 * TCache and cache dependency classes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Caching
 */

namespace Prado\Caching;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TNotSupportedException;
use Prado\TPropertyValue;

/**
 * TCache class
 *
 * TCache is the base class for cache classes with different cache storage implementation.
 *
 * TCache implements the interface {@link ICache} with the following methods,
 * - {@link get} : retrieve the value with a key (if any) from cache
 * - {@link set} : store the value with a key into cache
 * - {@link add} : store the value only if cache does not have this key
 * - {@link delete} : delete the value with the specified key from cache
 * - {@link flush} : delete all values from cache
 *
 * Each value is associated with an expiration time. The {@link get} operation
 * ensures that any expired value will not be returned. The expiration time by
 * the number of seconds. A expiration time 0 represents never expire.
 *
 * By definition, cache does not ensure the existence of a value
 * even if it never expires. Cache is not meant to be an persistent storage.
 *
 * Child classes must implement the following methods:
 * - {@link getValue}
 * - {@link setValue}
 * - {@link addValue}
 * - {@link deleteValue}
 * and optionally {@link flush}
 *
 * Since version 3.1.2, TCache implements the \ArrayAccess interface such that
 * the cache acts as an array.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Caching
 * @since 3.0
 */
abstract class TCache extends \Prado\TModule implements ICache, \ArrayAccess
{
	private $_prefix;
	private $_primary = true;

	/**
	 * Initializes the cache module.
	 * This method initializes the cache key prefix and registers the cache module
	 * with the application if the cache is primary.
	 * @param TXmlElement $config the module configuration
	 */
	public function init($config)
	{
		if ($this->_prefix === null) {
			$this->_prefix = $this->getApplication()->getUniqueID();
		}
		if ($this->_primary) {
			if ($this->getApplication()->getCache() === null) {
				$this->getApplication()->setCache($this);
			} else {
				throw new TConfigurationException('cache_primary_duplicated', get_class($this));
			}
		}
	}

	/**
	 * @return bool whether this cache module is used as primary/system cache.
	 * A primary cache is used by PRADO core framework to cache data such as
	 * parsed templates, themes, etc.
	 */
	public function getPrimaryCache()
	{
		return $this->_primary;
	}

	/**
	 * @param bool $value whether this cache module is used as primary/system cache. Defaults to false.
	 * @see getPrimaryCache
	 */
	public function setPrimaryCache($value)
	{
		$this->_primary = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return string a unique prefix for the keys of cached values.
	 * If it is not explicitly set, it will take the value of {@link TApplication::getUniqueID}.
	 */
	public function getKeyPrefix()
	{
		return $this->_prefix;
	}

	/**
	 * @param string $value a unique prefix for the keys of cached values
	 */
	public function setKeyPrefix($value)
	{
		$this->_prefix = $value;
	}

	/**
	 * @param string $key a key identifying a value to be cached
	 * @return string a key generated from the provided key which ensures the uniqueness across applications
	 */
	protected function generateUniqueKey($key)
	{
		return md5($this->_prefix . $key);
	}

	/**
	 * Retrieves a value from cache with a specified key.
	 * @param string $id a key identifying the cached value
	 * @return mixed the value stored in cache, false if the value is not in the cache or expired.
	 */
	public function get($id)
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
	 * @param ICacheDependency $dependency dependency of the cached item. If the dependency changes, the item is labeled invalid.
	 * @return bool true if the value is successfully stored into cache, false otherwise
	 */
	public function set($id, $value, $expire = 0, $dependency = null)
	{
		if (empty($value) && $expire === 0) {
			$this->delete($id);
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
	public function add($id, $value, $expire = 0, $dependency = null)
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
	public function delete($id)
	{
		return $this->deleteValue($this->generateUniqueKey($id));
	}

	/**
	 * Deletes all values from cache.
	 * Be careful of performing this operation if the cache is shared by multiple applications.
	 * Child classes may implement this method to realize the flush operation.
	 * @throws TNotSupportedException if this method is not overridden by child classes
	 */
	public function flush()
	{
		throw new TNotSupportedException('cache_flush_unsupported');
	}

	/**
	 * Retrieves a value from cache with a specified key.
	 * This method should be implemented by child classes to store the data
	 * in specific cache storage. The uniqueness and dependency are handled
	 * in {@link get()} already. So only the implementation of data retrieval
	 * is needed.
	 * @param string $key a unique key identifying the cached value
	 * @return string the value stored in cache, false if the value is not in the cache or expired.
	 */
	abstract protected function getValue($key);

	/**
	 * Stores a value identified by a key in cache.
	 * This method should be implemented by child classes to store the data
	 * in specific cache storage. The uniqueness and dependency are handled
	 * in {@link set()} already. So only the implementation of data storage
	 * is needed.
	 *
	 * @param string $key the key identifying the value to be cached
	 * @param string $value the value to be cached
	 * @param int $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return bool true if the value is successfully stored into cache, false otherwise
	 */
	abstract protected function setValue($key, $value, $expire);

	/**
	 * Stores a value identified by a key into cache if the cache does not contain this key.
	 * This method should be implemented by child classes to store the data
	 * in specific cache storage. The uniqueness and dependency are handled
	 * in {@link add()} already. So only the implementation of data storage
	 * is needed.
	 *
	 * @param string $key the key identifying the value to be cached
	 * @param string $value the value to be cached
	 * @param int $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return bool true if the value is successfully stored into cache, false otherwise
	 */
	abstract protected function addValue($key, $value, $expire);

	/**
	 * Deletes a value with the specified key from cache
	 * This method should be implemented by child classes to delete the data from actual cache storage.
	 * @param string $key the key of the value to be deleted
	 * @return bool if no error happens during deletion
	 */
	abstract protected function deleteValue($key);

	/**
	 * Returns whether there is a cache entry with a specified key.
	 * This method is required by the interface \ArrayAccess.
	 * @param string $id a key identifying the cached value
	 * @return bool
	 */
	public function offsetExists($id)
	{
		return $this->get($id) !== false;
	}

	/**
	 * Retrieves the value from cache with a specified key.
	 * This method is required by the interface \ArrayAccess.
	 * @param string $id a key identifying the cached value
	 * @return mixed the value stored in cache, false if the value is not in the cache or expired.
	 */
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
	public function offsetSet($id, $value)
	{
		$this->set($id, $value);
	}

	/**
	 * Deletes the value with the specified key from cache
	 * This method is required by the interface \ArrayAccess.
	 * @param string $id the key of the value to be deleted
	 * @return bool if no error happens during deletion
	 */
	public function offsetUnset($id)
	{
		$this->delete($id);
	}
}
