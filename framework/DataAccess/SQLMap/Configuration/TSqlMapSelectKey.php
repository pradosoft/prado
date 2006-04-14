<?php

class TSqlMapSelectKey extends TSqlMapStatement
{
	private $_type = 'post';
	private $_property = '';

	public function getType(){ return $this->_type; }
	public function setType($value){ $this->_type = $value; }

	public function getProperty(){ return $this->_property; }
	public function setProperty($value){ $this->_property = $value; }

	public function setExtends($value)
	{
		throw new TSqlMapConfigurationException(
				'sqlmap_can_not_extend_select_key');
	}

	public function getIsAfter()
	{
		return $this->_type == 'post';
	}
}

?>