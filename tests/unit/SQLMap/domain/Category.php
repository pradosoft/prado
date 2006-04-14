<?php

class Category
{
	private $_ID=-1;
	private $_Name='';
	private $_Guid='';

	public function getID(){ return $this->_ID; }
	public function setID($value){ $this->_ID = $value; }

	public function getName(){ return $this->_Name; }
	public function setName($value){ $this->_Name = $value; }

	public function getGuidString(){ return $this->_Guid; }
	public function setGuidString($value){ $this->_Guid = $value; }
}

?>