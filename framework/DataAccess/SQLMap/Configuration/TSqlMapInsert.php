<?php

class TSqlMapInsert extends TSqlMapStatement
{
	private $_selectKey=null;
	private $_generate=null;

	public function getSelectKey(){ return $this->_selectKey; }
	public function setSelectKey($value){ $this->_selectKey = $value; }

	public function getGenerate(){ return $this->_generate; }
	public function setGenerate($value){ $this->_generate = $value; }
}

?>