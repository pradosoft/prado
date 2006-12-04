<?php

class A
{
	private $_ID='';
	private $_Libelle='';
	private $_B='';
	private $_E='';
	private $_F='';

	public function getID(){ return $this->_ID; }
	public function setID($value){ $this->_ID = $value; }

	public function getLibelle(){ return $this->_Libelle; }
	public function setLibelle($value){ $this->_Libelle = $value; }

	public function getB(){ return $this->_B; }
	public function setB($value){ $this->_B = $value; }

	public function getE(){ return $this->_E; }
	public function setE($value){ $this->_E = $value; }

	public function getF(){ return $this->_F; }
	public function setF($value){ $this->_F = $value; }
}

?>