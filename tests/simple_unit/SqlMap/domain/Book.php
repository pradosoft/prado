<?php

class Book extends Document
{
	private $_PageNumber='';

	public function getPageNumber(){ return $this->_PageNumber; }
	public function setPageNumber($value){ $this->_PageNumber = $value; }	
}

?>