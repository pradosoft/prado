<?php
/**
 * TFastSqlMapApplicationCache class file contains Fast SqlMap cache implementation.
 *
 * @author Berczi Gabor <gabor.berczi@devworx.hu>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\SqlMap\DataMapper
 */

namespace Prado\Data\SqlMap\DataMapper;

use Prado\Caching\ICache;
use Prado\Prado;

/**
 * TFastSqlMapApplicationCache class file
 *
 * Fast SqlMap result cache class with minimal-concurrency get/set and atomic flush operations
 *
 * @author Berczi Gabor <gabor.berczi@devworx.hu>
 * @package Prado\Data\SqlMap\DataMapper
 * @since 3.2
 */

class TFastSqlMapApplicationCache implements ICache
{
	protected $_cacheModel;
	protected $_cache;

	public function __construct($cacheModel = null)
	{
		$this->_cacheModel = $cacheModel;
	}

	protected function getBaseKeyKeyName()
	{
		return 'SqlMapCacheBaseKey::' . $this->_cacheModel->getId();
	}

	protected function getBaseKey()
	{
		$cache = $this->getCache();
		$keyname = $this->getBaseKeyKeyName();
		$basekey = $cache->get($keyname);
		if (!$basekey) {
			$basekey = DxUtil::generateRandomHash(8);
			$cache->set($keyname, $basekey);
		}
		return $basekey;
	}

	protected function getCacheKey($key)
	{
		return $this->getBaseKey() . '###' . $key;
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

	public function set($key, $value, $expire = 0, $dependency = null)
	{
		$this->getCache()->set($this->getCacheKey($key), $value, $expire, $dependency);
	}

	protected function getCache()
	{
		if (!$this->_cache) {
			$this->_cache = Prado::getApplication()->getCache();
		}
		return $this->_cache;
	}

	public function add($id, $value, $expire = 0, $dependency = null)
	{
		throw new TSqlMapException('sqlmap_use_set_to_store_cache');
	}
}
