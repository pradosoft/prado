<?php

class TParameterMap extends TComponent
{
	private $_ID='';
	private $_extend='';
	private $_properties;
	private $_propertyMap;
	private $_extendMap;

	public function __construct()
	{
		$this->_properties = new TList;
		$this->_propertyMap = new TMap;
	}

	public function getProperties(){ return $this->_properties; }

	public function getID(){ return $this->_ID; }
	public function setID($value){ $this->_ID = $value; }

	public function getExtends(){ return $this->_extend; }
	public function setExtends($value){ $this->_extend = $value; }

	public function getProperty($index)
	{
		if(is_string($index))
			return $this->_propertyMap->itemAt($index);
		else if(is_int($index))
			return $this->_properties->itemAt($index);
		else
			throw new TDataMapperException(
						'sqlmap_index_must_be_string_or_int', $index);
	}

	public function addParameterProperty(TParameterProperty $property)
	{
		$this->_propertyMap->add($property->getProperty(), $property);
		$this->_properties->add($property);
	}

	public function insertParameterProperty($index, TParameterProperty $property)
	{
		$this->_propertyMap->add($property->getProperty(), $property);
		$this->_properties->insertAt($index, $property);
	}
	
	public function getPropertyNames()
	{
		return $this->_propertyMap->getKeys();
	}

	public function getParameter($mapping, $parameterValue, $statement)
	{
		$value = $parameterValue;
		$typeHandler = $mapping->getTypeHandler();
		try
		{
			$value = TPropertyAccess::get($parameterValue, $mapping->getProperty());	
		}
		catch (TInvalidPropertyException $e)
		{
			throw new TSqlMapExecutionException(
					'sqlmap_unable_to_get_property_for_parameter',$this->getID(), 
					$mapping->getProperty(), get_class($parameterValue), 
					$e->getMessage(), $statement->getID());
		}

		if(!is_null($typeHandler))
		{
			try
			{
				$value = $typeHandler->getParameter($value);			
			}
			catch (Exception $e)
			{
				throw new TSqlMapExecutionException(
				'sqlmap_error_in_parameter_from_handler',$this->getID(),
				$value, get_class($typeHandler), $e->getMessage());
			}
		}

		if(!is_null($nullValue = $mapping->getNullValue()))
		{
			if($nullValue === $value)
				$value = null;
		}

		if(!is_null($type = $mapping->getType()))
			$value = TTypeHandlerFactory::convertToType($type, $value);

		return $value;
	}
}
?>