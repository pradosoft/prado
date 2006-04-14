<?php

class TParameterProperty extends TComponent
{
	private $_typeHandler=null;
	private $_type=null;
	private $_column='';
	private $_dbType='';
	private $_property='';
	private $_nullValue=null;
	private $_typeHandlerFactory;

	private $_size;
	private $_precision;
	private $_scale;
	private $_direction;

	public function getTypeHandler()
	{
		if(is_null($this->_typeHandlerFactory)) return null;
		if(!is_null($this->_typeHandler))
			return $this->_typeHandlerFactory->getTypeHandler($this->_typeHandler);
		else if(!is_null($this->getType()))
			return $this->_typeHandlerFactory->getTypeHandler($this->getType());
		else
			return null;
	}
	public function setTypeHandler($value){ $this->_typeHandler = $value; }

	public function getType(){ return $this->_type; }
	public function setType($value){ $this->_type = $value; }

	public function getColumn(){ return $this->_column; }
	public function setColumn($value){ $this->_column = $value; }

	public function getDbType(){ return $this->_dbType; }
	public function setDbType($value){ $this->_dbType = $value; }

	public function getProperty(){ return $this->_property; }
	public function setProperty($value){ $this->_property = $value; }

	public function getNullValue(){ return $this->_nullValue; }
	public function setNullValue($value){ $this->_nullValue = $value; }

	public function getSize(){ return $this->_size; }
	public function setSize($value){ $this->_size = $value; }

	public function getPrecision(){ return $this->_precision; }
	public function setPrecision($value){ $this->_precision = $value; }
	
	public function getScale(){ return $this->_scale; }
	public function setScale($value){ $this->_scale = $value; }

	
	public function getDirection(){ return $this->_direction; }
	public function setDirection($value){ $this->_direction = $value; }

	public function initialize($sqlMap)
	{
		$this->_typeHandlerFactory = $sqlMap->getTypeHandlerFactory();
	//	$type = !is_null($this->_typeHandler) ? $this->_typeHandler: $this->_type;
	//	$this->setTypeHandler($sqlMap->getTypeHandlerFactory()->getTypeHandler($type));
	//	if(!is_null($type))
	//		var_dump($sqlMap->getTypeHandlerFactory());
	}
}

?>