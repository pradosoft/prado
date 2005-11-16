<?php

/**
 * IUser interface.
 *
 * This interface must be implemented by user objects.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Security
 * @since 3.0
 */
interface IUser
{
	public function getManager();
	public function getName();
	public function setName($value);
	public function getIsGuest();
	public function setIsGuest($value);
	public function getRoles();
	public function setRoles($value);
	/**
	 * @param string role to be tested
	 * @return boolean whether the user is of this role
	 */
	public function isInRole($role);
	public function saveToString();
	public function loadFromString($string);
}

class TUser extends TComponent implements IUser
{
	private $_manager;
	private $_isGuest=false;
	private $_name='';
	private $_roles=array();

	public function __construct($manager=null)
	{
		parent::__construct();
		$this->_manager=$manager;
	}

	public function getManager()
	{
		return $this->_manager;
	}

	public function getName()
	{
		return $this->_name;
	}

	public function setName($value)
	{
		$this->_name=$value;
	}

	public function getIsGuest()
	{
		return $this->_isGuest;
	}

	public function setIsGuest($value)
	{
		$this->_isGuest=TPropertyValue::ensureBoolean($value);
		if($this->_isGuest)
		{
			$this->_name=$this->_manager->getGuestName();
			$this->_roles=array();
		}
	}

	public function getRoles()
	{
		return $this->_roles;
	}

	public function setRoles($value)
	{
		if(is_array($value))
			$this->_roles=$value;
		else
		{
			$this->_roles=array();
			foreach(explode(',',$value) as $role)
			{
				if(($role=trim($role))!=='')
					$this->_roles[]=$role;
			}
		}
	}

	public function isInRole($role)
	{
		foreach($this->_roles as $r)
			if(strcasecmp($role,$r)===0)
				return true;
		return false;
	}

	public function saveToString()
	{
		return serialize(array($this->_name,$this->_roles,$this->_isGuest));
	}

	public function loadFromString($data)
	{
		if(!empty($data))
		{
			$array=unserialize($data);
			$this->_name=$array[0];
			$this->_roles=$array[1];
			$this->_isGuest=$array[2];
		}
		return $this;
	}
}


class TUserManager extends TComponent implements IModule
{
	private $_id;
	private $_users=array();
	private $_roles=array();
	private $_guestName='Guest';
	private $_passwordMode='MD5';

	public function init($application,$config)
	{
		foreach($config->getElementsByTagName('user') as $node)
			$this->_users[strtolower($node->getAttribute('name'))]=$node->getAttribute('password');
		foreach($config->getElementsByTagName('role') as $node)
		{
			foreach(explode(',',$node->getAttribute('users')) as $user)
			{
				if(($user=trim($user))!=='')
					$this->_roles[strtolower($user)][]=$node->getAttribute('name');
			}
		}
	}

	public function getID()
	{
		return $this->_id;
	}

	public function setID($value)
	{
		$this->_id=$value;
	}

	public function getGuestName()
	{
		return $this->_guestName;
	}

	public function setGuestName($value)
	{
		$this->_guestName=$value;
	}

	public function getPasswordMode()
	{
		return $this->_passwordMode;
	}

	public function setPasswordMode($value)
	{
		$this->_passwordMode=TPropertyValue::ensureEnum($value,array('Clear','MD5','SHA1'));
	}

	public function validateUser($username,$password)
	{
		if($this->_passwordMode==='MD5')
			$password=md5($password);
		else if($this->_passwordMode==='SHA1')
			$password=sha1($password);
		$username=strtolower($username);
		return (isset($this->_users[$username]) && $this->_users[$username]===$password);
	}

	public function logout($user)
	{
		$user->setIsGuest(true);
		$user->setName($this->getGuestName());
		$user->setRoles(array());
	}

	public function getUser($username=null)
	{
		if($username===null)
		{
			$user=new TUser($this);
			$user->setIsGuest($username===null);
			return $user;
		}
		else
		{
			$username=strtolower($username);
			if(isset($this->_users[$username]))
			{
				$user=new TUser($this);
				$user->setName($username);
				if(isset($this->_roles[$username]))
					$user->setRoles($this->_roles[$username]);
				return $user;
			}
			else
				return null;
		}
	}
}

?>