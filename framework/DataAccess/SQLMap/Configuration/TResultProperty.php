<?php

class TResultProperty extends TComponent
{
	private $_nullValue=null;
	private $_propertyName='';
	private $_columnName='';
	private $_columnIndex=-1;
	private $_nestedResultMapName='';
	private $_nestedResultMap=null;
	private $_valueType=null;
	private $_typeHandler=null;
	private $_isLazyLoad=false;
	private $_select='';
	private $_dbType='';
	private $_typeHandlerFactory;
	private $_hostResultMapID='inplicit internal mapping';

	const LIST_TYPE = 0;
	const ARRAY_TYPE = 1;
	const OBJECT_TYPE = 2;

	public function getNullValue(){ return $this->_nullValue; }
	public function setNullValue($value){ $this->_nullValue = $value; }

	public function getProperty(){ return $this->_propertyName; }
	public function setProperty($value){ $this->_propertyName = $value; }

	public function getColumn(){ return $this->_columnName; }
	public function setColumn($value){ $this->_columnName = $value; }

	public function getColumnIndex(){ return $this->_columnIndex; }
	public function setColumnIndex($value){ $this->_columnIndex = TPropertyValue::ensureInteger($value,-1); }

	public function getResultMapping(){ return $this->_nestedResultMapName; }
	public function setResultMapping($value){ $this->_nestedResultMapName = $value; }

	public function getNestedResultMap(){ return $this->_nestedResultMap; }
	public function setNestedResultMap($value){ $this->_nestedResultMap = $value; }

	public function getType(){ return $this->_valueType; }
	public function setType($value) { $this->_valueType = $value; }

	public function getTypeHandler()
	{ 
		if(is_null($this->_typeHandlerFactory)) return null;
		if(!is_null($this->_typeHandler))
			return $this->_typeHandlerFactory->getTypeHandler(
					$this->_typeHandler, $this->_dbType);
		else if(!is_null($this->getType()))
			return $this->_typeHandlerFactory->getTypeHandler(
					$this->getType(), $this->_dbType);
		else
			return null;
	}
	public function setTypeHandler($value) { $this->_typeHandler = $value; }

	public function getSelect(){ return $this->_select; }
	public function setSelect($value){ $this->_select = $value; }

	public function getLazyLoad(){ return $this->_isLazyLoad; }
	public function setLazyLoad($value){ $this->_isLazyLoad = TPropertyValue::ensureBoolean($value,false); }

	public function getDbType(){ return $this->_dbType; }
	public function setDbType($value){ $this->_dbType = $value; }

	public function initialize($sqlMap, $resultMap=null)
	{
		$this->_typeHandlerFactory = $sqlMap->getTypeHandlerFactory();
		if(!is_null($resultMap))
			$this->_hostResultMapID = $resultMap->getID();
//		$type = !is_null($this->_typeHandler) ? $this->_typeHandler: $this->_valueType;
//		$this->setTypeHandler($sqlMap->getTypeHandlerFactory()->getTypeHandler($type));
	}

	public function getDatabaseValue($row,$forceType=true)
	{
		$value = null;
		if($this->_columnIndex > 0 && isset($row[$this->_columnIndex]))
			$value = $this->getTypedValue($row[$this->_columnIndex], $forceType);
		else if(isset($row[$this->_columnName]))
			$value = $this->getTypedValue($row[$this->_columnName],$forceType);
		if(is_null($value) && !is_null($this->_nullValue))
			$value = $this->getTypedValue($this->_nullValue,$forceType);
		return $value;
	}
	
	public function getOrdinalValue($row)
	{
		return $this->getDatabaseValue($row,false);
	}

	private function getTypedValue($value, $forceType=true)
	{
		if(!$forceType) return $value;
		if(is_null($this->getTypeHandler()))
		{
			return TTypeHandlerFactory::convertToType($this->_valueType, $value);
		}
		else
		{
			try
			{
				return $this->getTypeHandler()->getResult($value);	
			}
			catch (Exception $e)
			{
				throw new TSqlMapExecutionException(
					'sqlmap_error_in_result_property_from_handler',$this->_hostResultMapID,
					$value, get_class($this->getTypeHandler()), $e->getMessage());
			}
		}
	}


	public function getPropertyType($type=null)
	{
		if(is_null($type))
			$type = $this->getType();
		if(class_exists($type, false)) //NO force autoloading
		{
			$class = new ReflectionClass($type);
			if($class->isSubclassOf('TList'))
				return self::LIST_TYPE;
			if($class->inmplementsInterface('ArrayAccess'))
				return self::ARRAY_TYPE;
		}
		if(strtolower($type) == 'array')
			return self::ARRAY_TYPE;
		return self::OBJECT_TYPE;
	}

	public function isListType($target)
	{
		if(is_null($this->getType()))
		{
			$prop = TPropertyAccess::get($target,$this->getProperty()); 
			return  $prop instanceof TList;
		}
		return $this->getPropertyType() == self::LIST_TYPE;
	}

	public function isArrayType($target)
	{
		if(is_null($this->getType()))
		{
			$prop = TPropertyAccess::get($target,$this->getProperty());
			if(is_object($prop))
				return $prop instanceof ArrayAccess;
			return is_array($prop);
		}
		return $this->getPropertyType() == self::ARRAY_TYPE;
	}

	public function isObjectType($target)
	{
		if(is_null($this->getType()))
		{
			$prop = TPropertyAccess::get($target,$this->getProperty());
			return is_object($prop);
		}
		return $this->getPropertyType() == self::OBJECT_TYPE;
	}
}

?>