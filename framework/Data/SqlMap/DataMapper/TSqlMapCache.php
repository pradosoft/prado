<?php
/**
 * TSqlMapCache class file contains FIFO, LRU, and GLOBAL cache implementations.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.SqlMap.DataMapper
 */

/**
 * Allow different implementation of caching strategy. See <tt>TSqlMapFifoCache</tt>
 * for a first-in-first-out implementation. See <tt>TSqlMapLruCache</tt> for
 * a least-recently-used cache implementation.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.SqlMap.DataMapper
 * @since 3.1
 */
abstract class TSqlMapCache implements ICache
{
	protected $_keyList;
	protected $_cache;
	protected $_cacheSize = 100;

	/**
	 * Create a new cache with limited cache size.
	 * @param integer maxium number of items to cache.
	 */
	public function __construct($cacheSize=100)
	{
		$this->_cache = new TMap;
		$this->_cacheSize = intval($cacheSize);
		$this->_keyList = new TList;
	}

	/**
	 * Maximum number of items to cache. Default size is 100.
	 * @param int cache size.
	 */
	public function setCacheSize($value)
	{
		$this->_cacheSize=TPropertyValue::ensureInteger($value,100);
	}

	/**
	 * @return int cache size.
	 */
	public function getCacheSize()
	{
		return $this->_cacheSize;
	}

	/**
	 * @return object the object removed if exists, null otherwise.
	 */
	public function delete($key)
	{
		$object = $this->get($key);
		$this->_cache->remove($key);
		$this->_keyList->remove($key);
		return $object;
	}

	/**
	 * Clears the cache.
	 */
	public function flush()
	{
		$this->_keyList->clear();
		$this->_cache->clear();
	}

	/**
	 * @throws TSqlMapException not implemented.
	 */
	public function add($id,$value,$expire=0,$dependency=null)
	{
		throw new TSqlMapException('sqlmap_use_set_to_store_cache');
	}
}

/**
 * First-in-First-out cache implementation, removes
 * object that was first added when the cache is full.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.SqlMap.DataMapper
 * @since 3.1
 */
class TSqlMapFifoCache extends TSqlMapCache
{
	/**
	 * @return mixed Gets a cached object with the specified key.
	 */
	public function get($key)
	{
		return $this->_cache->itemAt($key);
	}

	/**
	 * Stores a value identified by a key into cache.
	 * The expire and dependency parameters are ignored.
	 * @param string cache key
	 * @param mixed value to cache.
	 */
	public function set($key, $value,$expire=0,$dependency=null)
	{
		$this->_cache->add($key, $value);
		$this->_keyList->add($key);
		if($this->_keyList->getCount() > $this->_cacheSize)
		{
			$oldestKey = $this->_keyList->removeAt(0);
			$this->_cache->remove($oldestKey);
		}
	}
}

/**
 * Least recently used cache implementation, removes
 * object that was accessed last when the cache is full.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.SqlMap.DataMapper
 * @since 3.1
 */
class TSqlMapLruCache extends TSqlMapCache
{
	/**
	 * @return mixed Gets a cached object with the specified key.
	 */
	public function get($key)
	{
		if($this->_keyList->contains($key))
		{
			$this->_keyList->remove($key);
			$this->_keyList->add($key);
			return $this->_cache->itemAt($key);
		}
	}

	/**
	 * Stores a value identified by a key into cache.
	 * The expire and dependency parameters are ignored.
	 * @param string the key identifying the value to be cached
	 * @param mixed the value to be cached
	 */
	public function set($key, $value,$expire=0,$dependency=null)
	{
		$this->_cache->add($key, $value);
		$this->_keyList->add($key);
		if($this->_keyList->getCount() > $this->_cacheSize)
		{
			$oldestKey = $this->_keyList->removeAt(0);
			$this->_cache->remove($oldestKey);
		}
	}
}

/**
 * TSqlMapApplicationCache uses the default Prado application cache for
 * caching SqlMap results.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.SqlMap.DataMapper
 * @since 3.1
 */
class TSqlMapApplicationCache implements ICache
{
	/**
	 * @param string item to be deleted.
	 */
	public function delete($key)
	{
		$this->getCache()->delete($key);
	}

	/**
	 * Deletes all items in the cache.
	 */
	public function flush()
	{
		$this->getCache()->flush();
	}

	/**
	 * @return mixed Gets a cached object with the specified key.
	 */
	public function get($key)
	{
		$result = $this->getCache()->get($key);
		return $result === false ? null : $result;
	}

	/**
	 * Stores a value identified by a key into cache.
	 * @param string the key identifying the value to be cached
	 * @param mixed the value to be cached
	 */
	public function set($key, $value,$expire=0,$dependency=null)
	{
		$this->getCache()->set($key, $value, $expire,$dependency);
	}

	/**
	 * @return ICache Application cache instance.
	 */
	protected function getCache()
	{
		return Prado::getApplication()->getCache();
	}

	/**
	 * @throws TSqlMapException not implemented.
	 */
	public function add($id,$value,$expire=0,$dependency=null)
	{
		throw new TSqlMapException('sqlmap_use_set_to_store_cache');
	}
}

?>