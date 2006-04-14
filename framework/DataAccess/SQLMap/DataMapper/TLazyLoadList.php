<?php

class TLazyLoadList implements IInterceptor
{
	private $_param;
	private $_target;
	private $_propertyName='';
	private $_sqlMap;
	private $_statementName='';
	private $_loaded=false;
	private $_innerList;

	protected function __construct($mappedStatement, $param, $target, $propertyName)
	{
		$this->_param = $param;
		$this->_target = $target;
		$this->_statementName = $mappedStatement->getID();
		$this->_sqlMap = $mappedStatement->getSqlMap();
		$this->_propertyName = $propertyName;
	}

	public static function newInstance($mappedStatement, $param, $target, $propertyName)
	{
		$handler = new self($mappedStatement, $param, $target, $propertyName);
		$statement = $mappedStatement->getStatement();
		$list = $statement->createInstanceOfListClass();
		if(!is_object($list))
			throw new TSqlMapExecutionException('sqlmap_invalid_lazyload_list',
							$statement->getID());
		return new TObjectProxy($handler, $list);
	}

	public function intercept($method, $arguments)
	{
		return call_user_func_array(array($this->_innerList, $method), $arguments);
	}

	protected function fetchListData()
	{

		if($this->_loaded == false)
		{
			$this->_innerList = $this->_sqlMap->queryForList(
					$this->_statementName, $this->_param);
			$this->_loaded = true;
			//replace the target property with real list
			TPropertyAccess::set($this->_target, 
				$this->_propertyName, $this->_innerList);
		}
	}

	public function hasMethod($method)
	{
		$this->fetchListData();
		if(is_object($this->_innerList))
			return in_array($method, get_class_methods($this->_innerList));
		return false;
	}
}

interface IInterceptor
{
	public function intercept($method, $params);
	public function hasMethod($method);
}

class TObjectProxy
{
	private $_object;
	private $_handler;

	public function __construct(IInterceptor $handler, $object)
	{
		$this->_handler = $handler;
		$this->_object = $object;
	}

	public function __call($method,$params)
	{
		if($this->_handler->hasMethod($method))
			return $this->_handler->intercept($method, $params);
		else
			return call_user_func_array(array($this->_object, $method), $params);
	}
}

?>