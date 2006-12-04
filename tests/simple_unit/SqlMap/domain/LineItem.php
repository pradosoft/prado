<?php

class LineItem
{
	private $_ID=-1;
	private $_Order='';
	private $_Code='';
	private $_Quantity=-1;
	private $_Price=0.0;
	private $_PictureData='';

	public function getID(){ return $this->_ID; }
	public function setID($value){ $this->_ID = $value; }

	public function getOrder(){ return $this->_Order; }
	public function setOrder($value){ $this->_Order = $value; }

	public function getCode(){ return $this->_Code; }
	public function setCode($value){ $this->_Code = $value; }

	public function getQuantity(){ return $this->_Quantity; }
	public function setQuantity($value){ $this->_Quantity = $value; }

	public function getPrice(){ return $this->_Price; }
	public function setPrice($value){ $this->_Price = $value; }

	public function getPictureData(){ return $this->_PictureData; }
	public function setPictureData($value){ $this->_PictureData = $value; }

}

?>