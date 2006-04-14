<?php

class Document
{
	private $_ID='';
	private $_Title='';

	public function getID(){ return $this->_ID; }
	public function setID($value){ $this->_ID = $value; }

	public function getTitle(){ return $this->_Title; }
	public function setTitle($value){ $this->_Title = $value; }

}

?>