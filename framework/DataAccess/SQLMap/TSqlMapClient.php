<?php

require_once(dirname(__FILE__).'/TSqlMapper.php');

/**
 * A DataMapper client class that can load a cached SqlMapper. Give the configuration
 * file, it looks for a .cache file containing serialized TSqlMapClient instance to
 * load. Usage:
 *
 * <code>
 * $client = new TSqlMapClient;
 * $sqlmap = $client->configure($configFile, true); //load from cache.
 * $products = $sqlMap->queryForList('statementName');
 * </code>
 *
 * To save the TSqlMapper instance to cache for later usage, call
 * cacheConfiguration(). 
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @package System.DataAccess.SQLMap
 * @since 3.0
 */
class TSqlMapClient
{
	private $_mapper;
	private $_cache;
	
	public function configure($configFile, $loadFromCache=false)
	{
		if(is_null($this->_mapper))
			$this->initMapper($configFile, $loadFromCache);
		return $this->_mapper;
	}

	public function getInstance()
	{
		return $this->_mapper;
	}

	public function cacheConfiguration()
	{
		if(!is_null($this->_mapper) && $this->_cache !== false)
		{
			if(!is_file($this->_cache))
			{
				file_put_contents($this->_cache,serialize($this->_mapper));
				return true;
			}
		}
		return false;
	}
	
	protected function initMapper($file=null,$loadFromCache=false)
	{
		$this->_cache = $this->getCacheFile($file);
		if($loadFromCache && $this->_cache !== false && is_file($this->_cache))
		{
			$this->_mapper = unserialize(file_get_contents($this->_cache));
		}
		else
		{
			$builder = new TDomSqlMapBuilder();
			$this->_mapper = $builder->configure($file);
		}
	}

	protected function getCacheFile($file)
	{
		$path = realpath($file);
		if($path !== false)
			return substr($path,0, strrpos($path,'.')).'.cache';
		else
			return false;
	}
}

?>