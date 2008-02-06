<?php
/**
 * TSqlMapConfig class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.SqlMap
 */

Prado::using('System.Data.TDataSourceConfig');

/**
 * TSqlMapConfig module configuration class.
 *
 * Database connection and TSqlMapManager configuration.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.SqlMap
 * @since 3.1
 */
class TSqlMapConfig extends TDataSourceConfig
{
	private $_configFile;
	private $_sqlmap;
	private $_enableCache=false;

	/**
	 * File extension of external configuration file
	 */
	const CONFIG_FILE_EXT='.xml';

	/**
	 * @return string module ID + configuration file path.
	 */
	private function getCacheKey()
	{
		return $this->getID().$this->getConfigFile();
	}

	/**
	 * Deletes the configuration cache.
	 */
	public function clearCache()
	{
		$cache = $this->getApplication()->getCache();
		if(!is_null($cache))
			$cache->delete($this->getCacheKey());
	}

	/**
	 * Saves the current SqlMap manager to cache.
	 * @return boolean true if SqlMap manager was cached, false otherwise.
	 */
	protected function cacheSqlMapManager($manager)
	{
		if($this->getEnableCache())
		{
			$cache = $this->getApplication()->getCache();
			if(!is_null($cache))
				return $cache->set($this->getCacheKey(), $manager);
		}
		return false;
	}

	/**
	 * Loads SqlMap manager from cache.
	 * @return TSqlMapManager SqlMap manager intance if load was successful, null otherwise.
	 */
	protected function loadCachedSqlMapManager()
	{
		if($this->getEnableCache())
		{
			$cache = $this->getApplication()->getCache();
			if(!is_null($cache))
			{
				$manager = $cache->get($this->getCacheKey());
				if($manager instanceof TSqlMapManager)
					return $manager;
			}
		}
	}

	/**
	 * @return string SqlMap configuration file.
	 */
	public function getConfigFile()
	{
		return $this->_configFile;
	}

	/**
	 * @param string external configuration file in namespace format. The file
	 * extension must be '.xml'.
	 * @throws TConfigurationException if the file is invalid.
	 */
	public function setConfigFile($value)
	{
		if(is_file($value))
			$this->_configFile=$value;
		else
		{
			$file = Prado::getPathOfNamespace($value,self::CONFIG_FILE_EXT);
			if(is_null($file) || !is_file($file))
				throw new TConfigurationException('sqlmap_configfile_invalid',$value);
			else
				$this->_configFile = $file;
		}
	}

	/**
	 * Set true to cache sqlmap instances.
	 * @param boolean true to cache sqlmap instance.
	 */
	public function setEnableCache($value)
	{
		$this->_enableCache = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return boolean true if configuration should be cached, false otherwise.
	 */
	public function getEnableCache()
	{
		return $this->_enableCache;
	}

	/**
	 * Configure the data mapper using sqlmap configuration file.
	 * If cache is enabled, the data mapper instance is cached.
	 * @return TSqlMapGateway SqlMap gateway instance.
	 */
	protected function createSqlMapGateway()
	{
		Prado::using('System.Data.SqlMap.TSqlMapManager');
		if(($manager = $this->loadCachedSqlMapManager())===null)
		{
			$manager = new TSqlMapManager($this->getDbConnection());
			if(strlen($file=$this->getConfigFile()) > 0)
			{
				$manager->configureXml($file);
				$this->cacheSqlMapManager($manager);
			}
		}
		return $manager->getSqlmapGateway();
	}

	/**
	 * Initialize the sqlmap if necessary, returns the TSqlMapGateway instance.
	 * @return TSqlMapGateway SqlMap gateway instance.
	 */
	public function getClient()
	{
		if($this->_sqlmap===null )
			$this->_sqlmap=$this->createSqlMapGateway();
		return $this->_sqlmap;
	}
}

?>