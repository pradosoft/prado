<?php

class Order
{
	private $_ID=-1;
	private $_Account='';
	private $_Date='';
	private $_CardType='';
	private $_CardExpiry='';
	private $_CardNumber='';
	private $_Street='';
	private $_City='';
	private $_Province='';
	private $_PostalCode='';
	private $_LineItemsList='';
	private $_LineItems=null;
	private $_LineItemsArray=array();
	private $_FavouriteLineItem=null;

	public function __construct()
	{
		$this->_LineItemsList = new TList;
		$this->_LineItems = new TList;
		$this->_FavouriteLineItem = new LineItem;
	}

	public function getID(){ return $this->_ID; }
	public function setID($value){ $this->_ID = $value; }

	public function getAccount(){ return $this->_Account; }
	public function setAccount($value){ $this->_Account = $value; }

	public function getDate(){ return $this->_Date; }
	public function setDate($value){ $this->_Date = $value; }

	public function getCardType(){ return $this->_CardType; }
	public function setCardType($value){ $this->_CardType = $value; }

	public function getCardExpiry(){ return $this->_CardExpiry; }
	public function setCardExpiry($value){ $this->_CardExpiry = $value; }

	public function getCardNumber(){ return $this->_CardNumber; }
	public function setCardNumber($value){ $this->_CardNumber = $value; }

	public function getStreet(){ return $this->_Street; }
	public function setStreet($value){ $this->_Street = $value; }

	public function getCity(){ return $this->_City; }
	public function setCity($value){ $this->_City = $value; }

	public function getProvince(){ return $this->_Province; }
	public function setProvince($value){ $this->_Province = $value; }

	public function getPostalCode(){ return $this->_PostalCode; }
	public function setPostalCode($value){ $this->_PostalCode = $value; }

	public function getLineItemsList(){ return $this->_LineItemsList; }
	public function setLineItemsList($value){ $this->_LineItemsList = $value; }

	public function getLineItems(){ return $this->_LineItems; }
	public function setLineItems($value){ $this->_LineItems = $value; }

	public function getLineItemsArray(){ return $this->_LineItemsArray; }
	public function setLineItemsArray($value){ $this->_LineItemsArray = $value; }

	public function getFavouriteLineItem(){ return $this->_FavouriteLineItem; }
	public function setFavouriteLineItem($value){ $this->_FavouriteLineItem = $value; }

}

?>