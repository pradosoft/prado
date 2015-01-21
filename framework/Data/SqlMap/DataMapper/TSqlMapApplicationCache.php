<?php
/**
 * TSqlMapCache class file contains FIFO, LRU, and GLOBAL cache implementations.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package Prado\Data\SqlMap\DataMapper
 */

namespace Prado\Data\SqlMap\DataMapper;

/**
 * TSqlMapApplicationCache uses the default Prado application cache for
 * caching SqlMap results.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\DataMapper
 * @since 3.1
 */
class TSqlMapApplicationCache implements ICache
{
	protected $_cacheModel=null;

	/**
	 * Create a new cache with limited cache size.
	 * @param TSqlMapCacheModel $cacheModel.
	 */
	public function __construct($cacheModel=null)
	{
		$this->_cacheModel=$cacheModel;
	}

	/**
	 *
	 * @return string a KeyListID for the cache model.
	 */
	protected function getKeyListId()
	{
		$id='keyList';
		if ($this->_cacheModel instanceof TSqlMapCacheModel)
				$id.='_'.$this->_cacheModel->getId();
		return $id;
	}
	/**
	 * Retreive keylist from cache or create it if it doesn't exists
	 * @return TList
	 */
	protected function getKeyList()
	{
		if (($keyList=$this->getCache()->get($this->getKeyListId()))===false)
		{
			$keyList=new TList();
			$this->getCache()->set($this->getKeyListId(), $keyList);
		}
		return $keyList;
	}

	protected function setKeyList($keyList)
	{
		$this->getCache()->set($this->getKeyListId(), $keyList);
	}

	/**
	 * @param string item to be deleted.
	 */
	public function delete($key)
	{
		$keyList=$this->getKeyList();
		$keyList->remove($key);
		$this->getCache()->delete($key);
		$this->setKeyList($keyList);
	}

	/**
	 * Deletes all items in the cache, only for data cached by sqlmap cachemodel
	 */
	public function flush()
	{
		$keyList=$this->getKeyList();
		$cache=$this->getCache();
		foreach ($keyList as $key)
		{
			$cache->delete($key);
		}
		// Remove the old keylist
		$cache->delete($this->getKeyListId());
	}

	/**
	 * @return mixed Gets a cached object with the specified key.
	 */
	public function get($key)
	{
		$result = $this->getCache()->get($key);
		if ($result === false)
		{
			// if the key has not been found in cache (e.g expired), remove from keylist
			$keyList=$this->getKeyList();
			if ($keyList->contains($key))
			{
				$keyList->remove($key);
				$this->setKeyList($keyList);
			}
		}
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
		$keyList=$this->getKeyList();
		if (!$keyList->contains($key))
		{
			$keyList->add($key);
			$this->setKeyList($keyList);
		}
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