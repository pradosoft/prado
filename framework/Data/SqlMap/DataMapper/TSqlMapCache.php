<?php

/**
 * TSqlMapCache class file contains FIFO, LRU, and GLOBAL cache implementations.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\SqlMap\DataMapper;

use Prado\Caching\ICache;
use Prado\Collections\TList;
use Prado\Collections\TMap;
use Prado\TPropertyValue;

/**
 * TSqlMapCache class
 *
 * Allow different implementation of caching strategy. See <tt>TSqlMapFifoCache</tt>
 * for a first-in-first-out implementation. See <tt>TSqlMapLruCache</tt> for
 * a least-recently-used cache implementation.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @since 3.1
 */
abstract class TSqlMapCache implements ICache
{
	protected $_keyList;
	protected $_cache;
	protected $_cacheSize = 100;
	protected $_cacheModel;

	/**
	 * @return bool whether this cache is always available (in-memory, no external dependencies).
	 * @since 4.4.0
	 */
	public static function getIsAvailable(): bool
	{
		return true;
	}

	/**
	 * Create a new cache with limited cache size.
	 * @param \Prado\Data\SqlMap\Configuration\TSqlMapCacheModel $cacheModel
	 */
	public function __construct($cacheModel = null)
	{
		$this->_cache = new TMap();
		$this->_keyList = new TList();
		$this->_cacheModel = $cacheModel;
	}

	/**
	 * @return int cache size.
	 */
	public function getCacheSize()
	{
		return $this->_cacheSize;
	}

	/**
	 * Maximum number of items to cache. Default size is 100.
	 * @param int $value cache size.
	 */
	public function setCacheSize($value)
	{
		$this->_cacheSize = TPropertyValue::ensureInteger($value);
		if ($this->_cacheSize == 0) {
			$this->_cacheSize = 100;
		}
	}

	/**
	 * Stores a value identified by a key into cache if the cache does not contain this key.
	 * Nothing will be done if the cache already contains the key.
	 * @param string $id the key identifying the value to be cached
	 * @param mixed $value the value to be cached
	 * @param int $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @param ?\Prado\Caching\ICacheDependency $dependency dependency of the cached item. If the dependency changes, the item is labeled invalid.
	 * @throws TSqlMapException not implemented.
	 */
	public function add($id, $value, $expire = 0, $dependency = null)
	{
		throw new TSqlMapException('sqlmap_use_set_to_store_cache');
	}

	/**
	 * Deletes a value with the specified key from cache
	 * @param string $key the key of the value to be deleted
	 * @return bool if no error happens during deletion
	 */
	public function delete($key)
	{
		$this->_cache->remove($key);
		$this->_keyList->remove($key);
		return true;
	}

	/**
	 * Clears the cache.
	 */
	public function flush()
	{
		$this->_keyList->clear();
		$this->_cache->clear();
	}
}
