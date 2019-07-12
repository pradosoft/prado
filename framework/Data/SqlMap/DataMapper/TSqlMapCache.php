<?php
/**
 * TSqlMapCache class file contains FIFO, LRU, and GLOBAL cache implementations.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\SqlMap\DataMapper
 */

namespace Prado\Data\SqlMap\DataMapper;

use Prado\Caching\ICache;
use Prado\Collections\TList;
use Prado\Collections\TMap;
use Prado\TPropertyValue;

/**
 * Allow different implementation of caching strategy. See <tt>TSqlMapFifoCache</tt>
 * for a first-in-first-out implementation. See <tt>TSqlMapLruCache</tt> for
 * a least-recently-used cache implementation.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\DataMapper
 * @since 3.1
 */
abstract class TSqlMapCache implements ICache
{
	protected $_keyList;
	protected $_cache;
	protected $_cacheSize = 100;
	protected $_cacheModel;

	/**
	 * Create a new cache with limited cache size.
	 * @param TSqlMapCacheModel $cacheModel
	 */
	public function __construct($cacheModel = null)
	{
		$this->_cache = new TMap;
		$this->_keyList = new TList;
		$this->_cacheModel = $cacheModel;
	}

	/**
	 * Maximum number of items to cache. Default size is 100.
	 * @param int $value cache size.
	 */
	public function setCacheSize($value)
	{
		$this->_cacheSize = TPropertyValue::ensureInteger($value, 100);
	}

	/**
	 * @return int cache size.
	 */
	public function getCacheSize()
	{
		return $this->_cacheSize;
	}

	/**
	 * @param mixed $key
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
	 * @param mixed $id
	 * @param mixed $value
	 * @param mixed $expire
	 * @param null|mixed $dependency
	 * @throws TSqlMapException not implemented.
	 */
	public function add($id, $value, $expire = 0, $dependency = null)
	{
		throw new TSqlMapException('sqlmap_use_set_to_store_cache');
	}
}
