<?php
/**
 * TCache class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Caching
 */

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
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Caching
 * @since 3.0
 */
abstract class TCache extends TModule implements ICache
{
	private $_prefix=null;
	private $_primary=true;

	/**
	 * Initializes the cache module.
	 * This method initializes the cache key prefix and registers the cache module
	 * with the application if the cache is primary.
	 * @param TXmlElement the module configuration
	 */
	public function init($config)
	{
		if($this->_prefix===null)
			$this->_prefix=$this->getApplication()->getUniqueID();
		if($this->_primary)
		{
			if($this->getApplication()->getCache()===null)
				$this->getApplication()->setCache($this);
			else
				throw new TConfigurationException('cache_primary_duplicated',get_class($this));
		}
	}

	/**
	 * @return boolean whether this cache module is used as primary/system cache.
	 * A primary cache is used by PRADO core framework to cache data such as
	 * parsed templates, themes, etc.
	 */
	public function getPrimaryCache()
	{
		return $this->_primary;
	}

	/**
	 * @param boolean whether this cache module is used as primary/system cache. Defaults to false.
	 * @see getPrimaryCache
	 */
	public function setPrimaryCache($value)
	{
		$this->_primary=TPropertyValue::ensureBoolean($value);
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
	 * @param string a unique prefix for the keys of cached values
	 */
	public function setKeyPrefix($value)
	{
		$this->_prefix=$value;
	}

	/**
	 * @param string a key identifying a value to be cached
	 * @return sring a key generated from the provided key which ensures the uniqueness across applications
	 */
	protected function generateUniqueKey($key)
	{
		return md5($this->_prefix.$key);
	}

	/**
	 * Retrieves a value from cache with a specified key.
	 * @param string a key identifying the cached value
	 * @return mixed the value stored in cache, false if the value is not in the cache or expired.
	 */
	public function get($id)
	{
		if(($value=$this->getValue($this->generateUniqueKey($id)))!==false)
		{
			$data=unserialize($value);
			if(!($data[1] instanceof ICacheDependency) || !$data[1]->getHasChanged())
				return $data[0];
		}
		return false;
	}

	/**
	 * Stores a value identified by a key into cache.
	 * If the cache already contains such a key, the existing value and
	 * expiration time will be replaced with the new ones.
	 *
	 * @param string the key identifying the value to be cached
	 * @param mixed the value to be cached
	 * @param integer the number of seconds in which the cached value will expire. 0 means never expire.
	 * @param ICacheDependency dependency of the cached item. If the dependency changes, the item is labelled invalid.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	public function set($id,$value,$expire=0,$dependency=null)
	{
		$data=array($value,$dependency);
		$expire=($expire<=0)?0:time()+$expire;
		return $this->setValue($this->generateUniqueKey($id),serialize($data),$expire);
	}

	/**
	 * Stores a value identified by a key into cache if the cache does not contain this key.
	 * Nothing will be done if the cache already contains the key.
	 * @param string the key identifying the value to be cached
	 * @param mixed the value to be cached
	 * @param integer the number of seconds in which the cached value will expire. 0 means never expire.
	 * @param ICacheDependency dependency of the cached item. If the dependency changes, the item is labelled invalid.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	public function add($id,$value,$expire=0,$dependency=null)
	{
		$data=array($value,$dependency);
		$expire=($expire<=0)?0:time()+$expire;
		return $this->addValue($this->generateUniqueKey($id),serialize($data),$expire);
	}

	/**
	 * Deletes a value with the specified key from cache
	 * @param string the key of the value to be deleted
	 * @return boolean if no error happens during deletion
	 */
	public function delete($id)
	{
		return $this->deleteValue($this->generateUniqueKey($id));
	}

	/**
	 * Deletes all values from cache.
	 * Be careful of performing this operation if the cache is shared by multiple applications.
	 * Child classes may implement this method to realize the flush operation.
	 * @throws TNotSupportedException if this method is not overriden by child classes
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
	 * @param string a unique key identifying the cached value
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
	 * @param string the key identifying the value to be cached
	 * @param string the value to be cached
	 * @param integer the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	abstract protected function setValue($key,$value,$expire);

	/**
	 * Stores a value identified by a key into cache if the cache does not contain this key.
	 * This method should be implemented by child classes to store the data
	 * in specific cache storage. The uniqueness and dependency are handled
	 * in {@link add()} already. So only the implementation of data storage
	 * is needed.
	 *
	 * @param string the key identifying the value to be cached
	 * @param string the value to be cached
	 * @param integer the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	abstract protected function addValue($key,$value,$expire);

	/**
	 * Deletes a value with the specified key from cache
	 * This method should be implemented by child classes to delete the data from actual cache storage.
	 * @param string the key of the value to be deleted
	 * @return boolean if no error happens during deletion
	 */
	abstract protected function deleteValue($key);
}

?>