<?php

Prado::using('System.Security.TUser');
Prado::using('System.Security.TUserManager');

class TimeTrackerUser extends TUser
{
	private $_ID;
	
	public function __construct()
	{
		parent::__construct(new TUserManager());
	}
		
	public function getID(){ return $this->_ID; }
	public function setID($value)
	{ 
		if(is_null($this->_ID))
			$this->_ID = $value;
		else
			throw new TimeTrackerUserException(
				'timetracker_user_readonly_id');
	}
}

class TimeTrackerUserException extends TimeTrackerException
{
	
}

?>