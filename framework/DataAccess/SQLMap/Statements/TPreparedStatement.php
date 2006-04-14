<?php

class TPreparedStatement extends TComponent
{
	private $_sqlString='';
	private $_parameterNames;
	private $_parameterValues;

	public function __construct()
	{
		$this->_parameterNames=new TList;
		$this->_parameterValues=new TMap;
	}

	public function getPreparedSql(){ return $this->_sqlString; }
	public function setPreparedSql($value){ $this->_sqlString = $value; }

	public function getParameterNames(){ return $this->_parameterNames; }
	public function setParameterNames($value){ $this->_parameterNames = $value; }

	public function getParameterValues(){ return $this->_parameterValues; }
	public function setParameterValues($value){ $this->_parameterValues = $value; }

}

?>