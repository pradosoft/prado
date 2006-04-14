<?php

class User
{
	private $_ID='';
	private $_UserName='';
	private $_Password='';
	private $_EmailAddress='';
	private $_LastLogon='';

	public function getID(){ return $this->_ID; }
	public function setID($value){ $this->_ID = $value; }

	public function getUserName(){ return $this->_UserName; }
	public function setUserName($value){ $this->_UserName = $value; }

	public function getPassword(){ return $this->_Password; }
	public function setPassword($value){ $this->_Password = $value; }

	public function getEmailAddress(){ return $this->_EmailAddress; }
	public function setEmailAddress($value){ $this->_EmailAddress = $value; }

	public function getLastLogon(){ return $this->_LastLogon; }
	public function setLastLogon($value){ $this->_LastLogon = $value; }
}

?>