<?php
/**
 * TFastSqlMapApplicationCache class file contains Fast SqlMap cache implementation.
 *
 * @author Berczi Gabor <gabor.berczi@devworx.hu>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2013 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id: TFastSqlMapApplicationCache.php 2996 2011-06-20 15:24:57Z ctrlaltca@gmail.com $
 * @package System.Data.SqlMap
 */

/**
 * TFastSqlMapApplicationCache class file
 *  
 * Fast SqlMap result cache class with minimal-concurrency get/set and atomic flush operations
 *  
 * @author Berczi Gabor <gabor.berczi@devworx.hu>
 * @version $Id: TFastSqlMapApplicationCache.php 2996 2011-06-20 15:24:57Z ctrlaltca@gmail.com $
 * @package System.Data.SqlMap
 * @since 3.2
 */

class TFastSqlMapApplicationCache implements ICache
{
	protected $_cacheModel=null;
	protected $_cache=null;

	public function __construct($cacheModel=null)
	{
		$this->_cacheModel = $cacheModel;
	}
	
	protected function getBaseKeyKeyName()
	{
		return 'SqlMapCacheBaseKey::'.$this->_cacheModel->getId();
	}
	
	protected function getBaseKey()
	{
		$cache = $this->getCache();
		$keyname = $this->getBaseKeyKeyName();
		$basekey = $cache->get($keyname);
		if (!$basekey)
		{
			$basekey = DxUtil::generateRandomHash(8);
			$cache->set($keyname,$basekey);
		}
		return $basekey;
	}
	
	protected function getCacheKey($key)
	{
		return $this->getBaseKey().'###'.$key;
	}

	public function delete($key)
	{
		$this->getCache()->delete($this->getCacheKey($key));
	}

	public function flush()
	{
		$this->getCache()->delete($this->getBaseKeyKeyName());
	}
	
	public function get($key)
	{
		$result = $this->getCache()->get($this->getCacheKey($key));
		return $result === false ? null : $result;
	}

	public function set($key, $value,$expire=0,$dependency=null)
	{
		$this->getCache()->set($this->getCacheKey($key), $value, $expire,$dependency);
	}

	protected function getCache()
	{
		if (!$this->_cache)
			$this->_cache = Prado::getApplication()->getCache();
		return $this->_cache;
	}

	public function add($id,$value,$expire=0,$dependency=null)
	{
		throw new TSqlMapException('sqlmap_use_set_to_store_cache');
	}
}
