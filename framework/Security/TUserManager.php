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
				$this->_roles[]=trim($value);
		}
	}

	public function isInRole($role)
	{
		return in_array($role,$this->_roles);
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
	private $_guestName='Guest';
	private $_passwordMode='MD5';

	public function init($application,$config)
	{
		foreach($config->getElementsByTagName('user') as $node)
			$this->_users[$node->getAttribute('name')]=$node->getAttribute('password');
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
		return (isset($this->_users[$username]) && $this->_users[$username]===$password);
	}

	public function logout($user)
	{
		$user->setIsGuest(true);
		$user->setName($this->getGuestName());
	}

	public function getUser($username=null)
	{
		if($username===null)
		{
			$user=new TUser($this);
			$user->setIsGuest($username===null);
			return $user;
		}
		else if(isset($this->_users[$username]))
		{
			$user=new TUser($this);
			$user->setName($username);
			return $user;
		}
		else
			return null;
	}
}

?>