<?php

class Newspaper extends Document
{
	private $_City='';

	public function getCity(){ return $this->_City; }
	public function setCity($value){ $this->_City = $value; }

}

?>