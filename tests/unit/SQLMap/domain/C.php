<?php

class C
{
	private $_ID='';
	private $_Libelle='';

	public function getID(){ return $this->_ID; }
	public function setID($value){ $this->_ID = $value; }

	public function getLibelle(){ return $this->_Libelle; }
	public function setLibelle($value){ $this->_Libelle = $value; }
}

?>