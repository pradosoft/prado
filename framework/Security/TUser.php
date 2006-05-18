<?php
/**
 * TUser class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Security
 */

/**
 * Using IUserManager interface
 */
Prado::using('System.Security.IUserManager');

/**
 * TUser class
 *
 * TUser implements basic user functionality for a prado application.
 * To get the name of the user, use {@link getName Name} property.
 * The property {@link getIsGuest IsGuest} tells if the user a guest/anonymous user.
 * To obtain or test the roles that the user is in, use property
 * {@link getRoles Roles} and call {@link isInRole()}, respectively.
 *
 * TUser is meant to be used together with {@link IUserManager}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Security
 * @since 3.0
 */
class TUser extends TComponent implements IUser
{
	/**
	 * @var IUserManager user manager
	 */
	private $_manager;
	/**
	 * @var boolean if the user is a guest
	 */
	private $_isGuest=true;
	/**
	 * @var string username
	 */
	private $_name='';
	/**
	 * @var array user roles
	 */
	private $_roles=array();

	/**
	 * Constructor.
	 * @param IUserManager user manager
	 */
	public function __construct(IUserManager $manager)
	{
		$this->_manager=$manager;
	}

	/**
	 * @return IUserManager user manager
	 */
	public function getManager()
	{
		return $this->_manager;
	}

	/**
	 * @return string username
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * @param string username
	 */
	public function setName($value)
	{
		$this->_name=$value;
	}

	/**
	 * @return boolean if the user is a guest
	 */
	public function getIsGuest()
	{
		return $this->_isGuest;
	}

	/**
	 * @param boolean if the user is a guest
	 */
	public function setIsGuest($value)
	{
		if($this->_isGuest=TPropertyValue::ensureBoolean($value))
		{
			$this->_name=$this->_manager->getGuestName();
			$this->_roles=array();
		}
	}

	/**
	 * @return array list of roles that the user is of
	 */
	public function getRoles()
	{
		return $this->_roles;
	}

	/**
	 * @return array|string list of roles that the user is of. If it is a string, roles are assumed by separated by comma
	 */
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

	/**
	 * @param string role to be tested. Note, role is case-insensitive.
	 * @return boolean whether the user is of this role
	 */
	public function isInRole($role)
	{
		foreach($this->_roles as $r)
			if(strcasecmp($role,$r)===0)
				return true;
		return false;
	}

	/**
	 * @return string user data that is serialized and will be stored in session
	 */
	public function saveToString()
	{
		return serialize(array($this->_name,$this->_roles,$this->_isGuest));
	}

	/**
	 * @param string user data that is serialized and restored from session
	 * @return IUser the user object
	 */
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

?>