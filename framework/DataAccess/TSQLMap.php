<?php

class TSQLMap extends TModule
{
	private $_configFile;
	private $_sqlmap;
	private $_enableCache=false;

	/**
	 * File extension of external configuration file
	 */
	const CONFIG_FILE_EXT='.xml';
	
	protected function getCacheKey()
	{
		return $this->getID().$this->getConfigFile();
	}
	
	/**
	 * Saves the current sqlmap instance to cache.
	 * @return boolean true if sqlmap was cached, false otherwise.
	 */
	protected function cacheSqlMap()
	{
		if($this->getEnableConfigCache())
		{
			$cache = $this->getApplication()->getCache();
			if(!is_null($cache))
				return $cache->add($this->getCacheKey(), $this->_sqlmap);
		}
		return false;
	}

	/**
	 * Loads sqlmap data mapper instance from cache.
	 * @return boolean true if load was successful, false otherwise.
	 */
	protected function loadSqlMapCache()
	{
		if($this->getEnableConfigCache())
		{
			$cache = $this->getApplication()->getCache();			
			Prado::using('System.DataAccess.SQLMap.TSqlMapper');		
			if(!is_null($cache))
				$this->_sqlmap = $cache->get($this->getCacheKey());
			return $this->_sqlmap instanceof TSqlMapper;
		}
		return false;
	}
	
	/**
	 * @return string sqlmap configuration file.
	 */
	public function getConfigFile()
	{
		return $this->_configFile;
	}	

	/**
	 * @param string external configuration file in namespace format. The file
	 * must be suffixed with '.xml'.
	 * @throws TInvalidDataValueException if the file is invalid.
	 */
	public function setConfigFile($value)
	{
		$file = Prado::getPathOfNamespace($value,self::CONFIG_FILE_EXT);
		if(is_null($file))
			throw new TConfigurationException('sqlmap_configfile_invalid',$value);
		else
			$this->_configFile = $file;
	}
	
	/**
	 * Set true to cache sqlmap instances. 
	 * @param boolean true to cache sqlmap instance.
	 */
	public function setEnableConfigCache($value)
	{
		$this->_enableCache = TPropertyValue::ensureBoolean($value, false);
	}
	
	/**
	 * @return boolean true if configuration should be cached, false otherwise.
	 */
	public function getEnableConfigCache()
	{
		return $this->_enableCache;
	}

	/**
	 * Configure the data mapper using sqlmap configuration file.
	 * If cache is enabled, the data mapper instance is cached.
	 * @param string sqlmap configuration file.
	 * @return TSqlMapper sqlmap instance.
	 */
	protected function configure($configFile)
	{
		Prado::using('System.DataAccess.SQLMap.TSqlMapper');
		$builder = new TDomSqlMapBuilder();
		$this->_sqlmap = $builder->configure($configFile);
		$this->cacheSqlMap();
		return $this->_sqlmap;			
	}

	/**
	 * Initialize the sqlmap if necessary, returns the TSqlMapper instance.
	 * @return TSqlMapper data mapper for this module.
	 */
	public function getClient()
	{
		if(is_null($this->_sqlmap) && !$this->loadSqlMapCache())
			$this->configure($this->getConfigFile());
		return $this->_sqlmap;
	}
	
	/**
	 * This magic method allows this TSQLMap module to be treated like
	 * TSqlMapper instance.
	 * @param string calling method name
	 * @param array calling parameters
	 * @return mixed data obtained from TSqlMapper method call.
	 */
	public function __call($method, $params)
	{
		$client = $this->getClient();
		return call_user_func_array(array($client,$method),$params);
	}	
}

?>