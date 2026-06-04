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
use Prado\Data\SqlMap\Configuration\TSqlMapCacheModel;
use Prado\Prado;

/**
 * TSqlMapApplicationCache class
 *
 * TSqlMapApplicationCache uses the default Prado application cache for
 * caching SqlMap results.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @since 3.1
 */
class TSqlMapApplicationCache implements ICache
{
	protected $_cacheModel;

	/**
	 * Create a new cache with limited cache size.
	 * @param TSqlMapCacheModel $cacheModel
	 */
	public function __construct($cacheModel = null)
	{
		$this->_cacheModel = $cacheModel;
	}

	/**
	 * @return bool whether the application cache is available.
	 * @since 4.4.0
	 */
	public static function getIsAvailable(): bool
	{
		return Prado::getApplication()?->getCache() !== null;
	}

	/**
	 * @return ICache Application cache instance.
	 */
	protected function getCache()
	{
		return Prado::getApplication()->getCache();
	}

	/**
	 *
	 * @return string a KeyListID for the cache model.
	 */
	protected function getKeyListId()
	{
		$id = 'keyList';
		if ($this->_cacheModel instanceof TSqlMapCacheModel) {
			$id .= '_' . $this->_cacheModel->getId();
		}
		return $id;
	}
	/**
	 * Retreive keylist from cache or create it if it doesn't exists
	 * @return TList
	 */
	protected function getKeyList()
	{
		if (($keyList = $this->getCache()->get($this->getKeyListId())) === false) {
			$keyList = new TList();
			$this->getCache()->set($this->getKeyListId(), $keyList);
		}
		return $keyList;
	}

	protected function setKeyList($keyList)
	{
		$this->getCache()->set($this->getKeyListId(), $keyList);
	}

	/**
	 * Retrieves a value from cache with a specified key.
	 * @param string $key a key identifying the cached value
	 * @return false|mixed the value stored in cache, false if the value is not in the cache or expired.
	 */
	public function get($key)
	{
		$result = $this->getCache()->get($key);
		if ($result === false) {
			// if the key has not been found in cache (e.g expired), remove from keylist
			$keyList = $this->getKeyList();
			if ($keyList->contains($key)) {
				$keyList->remove($key);
				$this->setKeyList($keyList);
			}
		}
		return $result === false ? null : $result;
	}

	/**
	 * Stores a value identified by a key into cache.
	 * If the cache already contains such a key, the existing value and
	 * expiration time will be replaced with the new ones.
	 *
	 * @param string $key the key identifying the value to be cached
	 * @param mixed $value the value to be cached
	 * @param int $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @param ?\Prado\Caching\ICacheDependency $dependency dependency of the cached item. If the dependency changes, the item is labeled invalid.
	 * @return bool true if the value is successfully stored into cache, false otherwise
	 */
	public function set($key, $value, $expire = 0, $dependency = null)
	{
		$this->getCache()->set($key, $value, $expire, $dependency);
		$keyList = $this->getKeyList();
		if (!$keyList->contains($key)) {
			$keyList->add($key);
			$this->setKeyList($keyList);
		}
		return true;
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
		$keyList = $this->getKeyList();
		$keyList->remove($key);
		$this->getCache()->delete($key);
		$this->setKeyList($keyList);
		return true;
	}

	/**
	 * Deletes all items in the cache, only for data cached by sqlmap cachemodel
	 */
	public function flush()
	{
		$keyList = $this->getKeyList();
		$cache = $this->getCache();
		foreach ($keyList as $key) {
			$cache->delete($key);
		}
		// Remove the old keylist
		$cache->delete($this->getKeyListId());
	}
}
