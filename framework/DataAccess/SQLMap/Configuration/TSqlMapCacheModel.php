<?php

class TSqlMapCacheModel extends TComponent
{
	private $_cache;
	private $_flushInterval = -1;
	private $_hits = 0;
	private $_requests = 0;
	private $_id;
	private $_lastFlush;
	private $_implementation;
	private $_properties = array();


	public function getID(){ return $this->_id; }
	public function setID($value){ $this->_id = $value; }

	public function getImplementation(){ return $this->_implementation; }
	public function setImplementation($value){ $this->_implementation = $value; }

	public function getFlushInterval(){ return $this->_flushInterval; }
	public function setFlushInterval($value){ $this->_flushInterval = $value; }

	public function initialize($sqlMap)
	{
		$implementation = $this->getImplementationClass(
				$sqlMap->getTypeHandlerFactory());
		$this->_cache = new $implementation;
		$this->_cache->configure($this->_properties);
	}

	protected function getImplementationClass($typeFactory)
	{
		switch(strtolower($this->_implementation))
		{
			case 'fifo': return 'TSqlMapFifoCache';
			case 'lru' : return 'TSqlMapLruCache';
		}

		if(class_exists($this->_implementation, false))
			$class = $this->_implementation;
		else
			$class  = $typeFactory->getTypeHandler($this->_implementation);
		if(!is_null($class)) 
			return $class;
		else
			throw new TSqlMapConfigurationException(
				'sqlmap_unable_to_find_implemenation', $this->_implementation);
	}

	public function addProperty($name, $value)
	{
		$this->_properties[strtolower($name)] = $value;
	}

	public function registerTriggerStatement($mappedStatement)
	{
		$mappedStatement->attachEventHandler('OnExecuteQuery', 
				array($this, 'flushHandler'));
	}

	protected function flushHandler($sender, $param)
	{
		$this->flush();
	}

	public function flush()
	{
		var_dump("flush!");
		$this->_cache->flush();
	}

	public function get($key)
	{
		if($key instanceof TSqlMapCacheKey)
			$key = $key->getHash();

		//if flush ?
		$value = $this->_cache->get($key);
		$this->_requests++;
		if(!is_null($value))
			$this->_hits++;
		return $value;
	}

	public function set($key, $value)
	{
		if($key instanceof TSqlMapCacheKey)
			$key = $key->getHash();

		if(!is_null($value))
			$this->_cache->set($key, $value);
	}

	public function getHitRatio()
	{
		if($this->_requests != 0)
			return $this->_hits / $this->_requests;
		else
			return 0;
	}
}


class TSqlMapCacheKey
{
	private $_key;

	public function __construct($object)
	{
		$this->_key = $this->generateKey(serialize($object));
	}

	protected function generateKey($string)
	{
		return sprintf('%x',crc32($string));
	}

	public function getHash()
	{
		return $this->_key;
	}

	public function __toString()
	{
		return $this->getHash();
	}
}


?>