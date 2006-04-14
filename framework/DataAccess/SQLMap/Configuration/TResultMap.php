<?php

class TResultMap extends TComponent
{
	private $_ID='';
	private $_className='';
	private $_columns='';
	private $_extendMap='';
	private $_groupBy='';
	private $_discriminator=null;

	public function __construct()
	{
		$this->_columns = new TMap;
	}

	public function getID(){ return $this->_ID; }
	public function setID($value){ $this->_ID = $value; }

	public function getClass(){ return $this->_className; }
	public function setClass($value){ $this->_className = $value; }

	public function getColumns(){ return $this->_columns; }
	public function setColumns($value){ $this->_columns = $value; }

	public function getExtends(){ return $this->_extendMap; }
	public function setExtends($value){ $this->_extendMap = $value; }

	public function getGroupBy(){ return $this->_groupBy; }
	public function setGroupBy($value){ $this->_groupBy = $value; }

	public function getDiscriminator(){ return $this->_discriminator; }
	public function setDiscriminator($value){ $this->_discriminator = $value; }

	public function addResultProperty(TResultProperty $property)
	{
		$this->_columns->add($property->getProperty(), $property);
	}
	
	public function createInstanceOfResult()
	{
		return TTypeHandlerFactory::createInstanceOf($this->getClass());
	}

	public function resolveSubMap($row)
	{
		$subMap = $this;
		if(!is_null($disc = $this->getDiscriminator()))
		{
			$mapping = $disc->getMapping();
			$dataValue = $mapping->getDatabaseValue($row);
			$subMap = $disc->getSubMap((string)$dataValue);
		
			if(is_null($subMap))
				$subMap = $this;
			else if($subMap !== $this)
				$subMap = $subMap->resolveSubMap($row);
		}
		return $subMap;
	}
}

?>