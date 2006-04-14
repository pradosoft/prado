<?php

class TSqlMapSelect extends TSqlMapStatement
{
	private $_generate;

	public function getGenerate(){ return $this->_generate; }
	public function setGenerate($value){ $this->_generate = $value; }
}

?>