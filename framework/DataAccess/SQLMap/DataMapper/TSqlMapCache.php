<?php
/**
 * TSqlMapCache class file contains FIFO, LRU, and GLOBAL cache implementations.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.DataAccess.SQLMap
 */

interface ISqLMapCache
{
	public function remove($key);

	public function flush();

	public function get($key);

	public function set($key, $value);

	public function configure($model, $properties);
}

/**
 * Allow different implementation of caching strategy. See <tt>TSqlMapFifoCache</tt>
 * for a first-in-first-out implementation. See <tt>TSqlMapLruCache</tt> for
 * a least-recently-used cache implementation.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.DataAccess.SQLMap
 * @since 3.0
 */
abstract class TSqlMapCache implements ISqlMapCache
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
	 * Configures the Cache Size.
	 * @param array list of properties
	 */
	public function configure($model, $properties)
	{
		if(isset($properties['size']))
			$this->_cacheSize = intval($properties['size']);
	}

	/**
	 * @return object the object removed if exists, null otherwise.
	 */
	public function remove($key)
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

}

/**
 * First-in-First-out cache implementation, removes
 * object that was first added when the cache is full.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.DataAccess.SQLMap
 * @since 3.0
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
	 * Adds an item with the specified key and value into cached data.
	 * @param string cache key
	 * @param mixed value to cache.
	 */
	public function set($key, $value)
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
 * @version $Revision: $  $Date: $
 * @package System.DataAccess.SQLMap
 * @since 3.0
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
		else
			return null;
	}

	/**
	 * Adds an item with the specified key and value into cached data.
	 * @param string cache key
	 * @param mixed value to cache.
	 */
	public function set($key, $value)
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

class TSqlMapApplicationCache implements ISqlMapCache
{ 
	private $_cache;
	private $_expiry=0;
	private $_property=array();	
	private $_cacheModelID;
	
	public function __sleep()
	{
		$this->_cache = null;
		return array_keys(get_object_vars($this));
	}
				
	public function remove($key)
	{
		$this->getCache()->delete($key);
	}

	public function flush()
	{
		$this->getCache()->flush();
	}

	public function get($key)
	{
		$result = $this->getCache()->get($key);
		return $result === false ? null : $result;			
	}

	public function set($key, $value)
	{
		$this->getCache()->set($key, $value, $this->_expiry);
	}

	public function configure($model, $properties)
	{
		$this->_property = $properties;
		$this->_cacheModelID = $model->getID();
	}		
	
	protected function getCache()
	{
		if(is_null($this->_cache))
			$this->initialize();
		return $this->_cache;
	}
	
	protected function initialize()
	{
		if(isset($this->_property['expiry']))
			$this->_expiry = intval($this->_property['expiry']);
			
		if(isset($this->_property['cacheModule']))
		{
			$id = $this->_property['cacheModule'];
			$this->_cache = Prado::getApplication()->getModule($id);
		}	
		else
		{
			$this->_cache = Prado::getApplication()->getCache();
		}
			
		if(!($this->_cache instanceof ICache))
			throw new TSqlMapConfigurationException(
				'sqlmap_invalid_prado_cache', $this->_cacheModelID);			
	}
}

?>