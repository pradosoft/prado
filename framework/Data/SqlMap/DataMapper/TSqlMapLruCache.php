<?php

namespace Prado\Data\SqlMap\DataMapper;

/**
 * TSqlMapCache class file contains FIFO, LRU, and GLOBAL cache implementations.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

/**
 * Least recently used cache implementation, removes
 * object that was accessed last when the cache is full.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @since 3.1
 */
class TSqlMapLruCache extends TSqlMapCache
{
	/**
	 * @param mixed $key
	 * @return mixed Gets a cached object with the specified key.
	 */
	public function get($key)
	{
		if ($this->_keyList->contains($key)) {
			$this->_keyList->remove($key);
			$this->_keyList->add($key);
			return $this->_cache->itemAt($key);
		}
	}

	/**
	 * Stores a value identified by a key into cache.
	 * The expire and dependency parameters are ignored.
	 * @param string $key the key identifying the value to be cached
	 * @param mixed $value the value to be cached
	 * @param mixed $expire
	 * @param null|mixed $dependency
	 */
	public function set($key, $value, $expire = 0, $dependency = null)
	{
		$this->_cache->add($key, $value);
		$this->_keyList->add($key);
		if ($this->_keyList->getCount() > $this->_cacheSize) {
			$oldestKey = $this->_keyList->removeAt(0);
			$this->_cache->remove($oldestKey);
		}
		return true;
	}
}
