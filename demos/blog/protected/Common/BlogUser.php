<?php

Prado::using('System.Security.TUser');

class BlogUser extends TUser
{
	private $_id;

	public function getID()
	{
		return $this->_id;
	}

	public function setID($value)
	{
		$this->_id=$value;
	}

	public function saveToString()
	{
		$a=array($this->_id,parent::saveToString());
		return serialize($a);
	}

	public function loadFromString($data)
	{
		if(!empty($data))
		{
			list($id,$str)=unserialize($data);
			$this->_id=$id;
			return parent::loadFromString($str);
		}
		else
			return $this;
	}
}

?>