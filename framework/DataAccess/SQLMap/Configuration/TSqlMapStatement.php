<?php

class TSqlMapStatement extends TComponent
{
	private $_ID='';
	private $_parameterMapName='';
	private $_parameterMap;
	private $_parameterClassName='';
//	private $_parameterClass;
	private $_resultMapName='';
	private $_resultMap;
	private $_resultClassName='';
//	private $_resultClass;
	private $_cacheModelName='';
	private $_remapResults=false;
	private $_SQL='';
	private $_listClass='';
	private $_typeHandler;
	private $_extendStatement='';
	private $_cache;

	public function getID(){ return $this->_ID; }
	public function setID($value){ $this->_ID = $value; }

	public function getParameterMap(){ return $this->_parameterMapName; }
	public function setParameterMap($value){ $this->_parameterMapName = $value; }

	public function getParameterClass(){ return $this->_parameterClassName; }
	public function setParameterClass($value){ $this->_parameterClassName = $value; }

	public function getResultMap(){ return $this->_resultMapName; }
	public function setResultMap($value){ $this->_resultMapName = $value; }

	public function getResultClass(){ return $this->_resultClassName; }
	public function setResultClass($value){ $this->_resultClassName = $value; }

	public function getCacheModel(){ return $this->_cacheModelName; }
	public function setCacheModel($value){ $this->_cacheModelName = $value; }

	public function getCache(){ return $this->_cache; }
	public function setCache($value){ $this->_cache = $value; }

	public function getRemapResults(){ return $this->_remapResults; }
	public function setRemapResults($value){ $this->_remapResults = TPropertyValue::ensureBoolean($value,false); }

	public function getSQL(){ return $this->_SQL; }
	public function setSQL($value){ $this->_SQL = $value; }

	public function getListClass(){ return $this->_listClass; }
	public function setListClass($value){ $this->_listClass = $value; }

	public function getExtends(){ return $this->_extendStatement; }
	public function setExtends($value){ $this->_extendStatement = $value; }
	
	public function resultMap(){ return $this->_resultMap; }
	public function parameterMap(){ return $this->_parameterMap; }

	public function setInlineParameterMap($map)
	{
		$this->_parameterMap = $map;
	}
//	public function parameterClass(){ return $this->_parameterClass; }
//	public function resultClass(){ return $this->_resultClass; }

	public function initialize($sqlMap)
	{
		$this->_typeHandler = $sqlMap->getTypeHandlerFactory();
		if(strlen($this->_resultMapName) > 0)
			$this->_resultMap = $sqlMap->getResultMap($this->_resultMapName);
		if(strlen($this->_parameterMapName) > 0)
			$this->_parameterMap = $sqlMap->getParameterMap($this->_parameterMapName);
	}


	public function createInstanceOfListClass()
	{
		if(strlen($type = $this->getListClass()) > 0)
			return $this->createInstanceOf($type);
		return array(); //new TList;
	}

	protected function createInstanceOf($type,$row=null)
	{
		$handler = $this->_typeHandler->getTypeHandler($type);
		
		try
		{
			if(!is_null($handler))
					return $handler->createNewInstance($row);		
			else
				return TTypeHandlerFactory::createInstanceOf($type);
		}
		catch (TDataMapperException $e)
		{
			throw new TSqlMapExecutionException(
				'sqlmap_unable_to_create_new_instance', 
					$type, get_class($handler), $this->getID());
		}
					
	}

	public function createInstanceOfResultClass($row)
	{
		if(strlen($type= $this->getResultClass()) > 0)
			return $this->createInstanceOf($type,$row);
	}
}

?>