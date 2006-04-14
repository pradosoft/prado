<?php

class Search
{
	private $_NumberSearch='';
	private $_StartDate='';
	private $_Operande='';
	private $_StartDateAnd='';

	public function getNumberSearch(){ return $this->_NumberSearch; }
	public function setNumberSearch($value){ $this->_NumberSearch = $value; }

	public function getStartDate(){ return $this->_StartDate; }
	public function setStartDate($value){ $this->_StartDate = $value; }

	public function getOperande(){ return $this->_Operande; }
	public function setOperande($value){ $this->_Operande = $value; }

	public function getStartDateAnd(){ return $this->_StartDateAnd; }
	public function setStartDateAnd($value){ $this->_StartDateAnd = $value; }
}

?>