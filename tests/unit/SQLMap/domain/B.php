<?php

class B
{
	private $_C='';
	private $_D='';
	private $_ID='';
	private $_Libelle='';

	public function getC(){ return $this->_C; }
	public function setC($value){ $this->_C = $value; }

	public function getD(){ return $this->_D; }
	public function setD($value){ $this->_D = $value; }

	public function getID(){ return $this->_ID; }
	public function setID($value){ $this->_ID = $value; }

	public function getLibelle(){ return $this->_Libelle; }
	public function setLibelle($value){ $this->_Libelle = $value; }
}

?>