<?php

namespace Prado\Data\SqlMap\DataMapper;

/**
 * TSqlMapCache class file contains FIFO, LRU, and GLOBAL cache implementations.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\SqlMap\DataMapper
 */

/**
 * First-in-First-out cache implementation, removes
 * object that was first added when the cache is full.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\DataMapper
 * @since 3.1
 */
class TSqlMapFifoCache extends TSqlMapCache
{
	/**
	 * @param mixed $key
	 * @return mixed Gets a cached object with the specified key.
	 */
	public function get($key)
	{
		return $this->_cache->itemAt($key);
	}

	/**
	 * Stores a value identified by a key into cache.
	 * The expire and dependency parameters are ignored.
	 * @param string $key cache key
	 * @param mixed $value value to cache.
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
	}
}
