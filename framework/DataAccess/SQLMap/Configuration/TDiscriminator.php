<?php

class TDiscriminator extends TComponent
{
	private $_column='';
	private $_type='';
	private $_typeHandler=null;
	private $_dbType='';
	private $_columnIndex='';
	private $_nullValue='';
	private $_mapping='';
	private $_resultMaps=array();
	private $_subMaps=array();

	public function getColumn(){ return $this->_column; }
	public function setColumn($value){ $this->_column = $value; }

	public function getType(){ return $this->_type; }
	public function setType($value){ $this->_type = $value; }

	public function getTypeHandler(){ return $this->_typeHandler; }
	public function setTypeHandler($value){ $this->_typeHandler = $value; }

	public function getDbType(){ return $this->_dbType; }
	public function setDbType($value){ $this->_dbType = $value; }

	public function getColumnIndex(){ return $this->_columnIndex; }
	public function setColumnIndex($value){ $this->_columnIndex = $value; }

	public function getNullValue(){ return $this->_nullValue; }
	public function setNullValue($value){ $this->_nullValue = $value; }

	public function getMapping(){ return $this->_mapping; }

	public function getResultMaps(){ return $this->_resultMaps; }
	public function setResultMaps($value){ $this->_resultMaps = $value; }

	public function add($subMap)
	{ 
		$this->_subMaps[] = $subMap;
	}

	public function getSubMap($value)
	{ 
		if(isset($this->_resultMaps[$value]))
			return $this->_resultMaps[$value];
		else
			return null;
	}

	public function initMapping($sqlMap, $resultMap)
	{
		$this->_mapping = new TResultProperty;
		$this->_mapping->setColumn($this->getColumn());
		$this->_mapping->setColumnIndex($this->getColumnIndex());
		$this->_mapping->setType($this->getType());
		$this->_mapping->setTypeHandler($this->getTypeHandler());
		$this->_mapping->setDbType($this->getDbType());
		$this->_mapping->setNullValue($this->getNullValue());
		$this->_mapping->initialize($sqlMap, $resultMap);
	}

	public function initialize($sqlMap)
	{
		foreach($this->_subMaps as $subMap)
		{
			$this->_resultMaps[$subMap->getValue()] =
				$sqlMap->getResultMap($subMap->getResultMapping());
		}
	}
}


class TSubMap extends TComponent
{
	private $_value='';
	private $_resultMapping='';

	public function getValue(){ return $this->_value; }
	public function setValue($value){ $this->_value = $value; }

	public function getResultMapping(){ return $this->_resultMapping; }
	public function setResultMapping($value){ $this->_resultMapping = $value; }
}

?>