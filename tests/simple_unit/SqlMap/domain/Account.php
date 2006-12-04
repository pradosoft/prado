<?php

class Account
{
	private $_ID=0;
	private $_FirstName='';
	private $_LastName='';
	private $_EmailAddress=null;
	private $_IDS='';
	private $_BannerOptions=0;
	private $_CartOptions=0;

	public function getID(){ return $this->_ID; }
	public function setID($value){ $this->_ID = intval($value); }

	public function getFirstName(){ return $this->_FirstName; }
	public function setFirstName($value){ $this->_FirstName = $value; }

	public function getLastName(){ return $this->_LastName; }
	public function setLastName($value){ $this->_LastName = $value; }

	public function getEmailAddress(){ return $this->_EmailAddress; }
	public function setEmailAddress($value){ $this->_EmailAddress = $value; }

	public function getIDS(){ return $this->_IDS; }
	public function setIDS($value){ $this->_IDS = $value; }

	public function getBannerOptions(){ return $this->_BannerOptions; }
	public function setBannerOptions($value){ $this->_BannerOptions = $value; }

	public function getCartOptions(){ return $this->_CartOptions; }
	public function setCartOptions($value){ $this->_CartOptions = $value; }

}

?>