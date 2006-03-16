<?php
/**
 * TUser, TUserManager class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Security
 */

/**
 * TUser class
 *
 * TUser implements basic user functionality for a prado application.
 * To get the name of the user, use {@link getName Name} property.
 * The property {@link getIsGuest IsGuest} tells if the user a guest/anonymous user.
 * To obtain or test the roles that the user is in, use property
 * {@link getRoles Roles} and call {@link isInRole()}, respectively.
 *
 * TUser is meant to be used together with {@link TUserManager} and
 * {@link TAuthManager}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Security
 * @since 3.0
 */
class TUser extends TComponent implements IUser
{
	/**
	 * @var TUserManager user manager
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
	 * @param TUserManager user manager
	 */
	public function __construct($manager=null)
	{
		$this->_manager=$manager;
	}

	/**
	 * @return TUserManager user manager
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
		$this->_isGuest=TPropertyValue::ensureBoolean($value);
		if($this->_isGuest)
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

/**
 * TUserManager class
 *
 * TUserManager manages a static list of users {@link TUser}.
 * The user information is specified via module configuration using the following XML syntax,
 * <module id="users" class="System.Security.TUserManager" PasswordMode="Clear">
 *   <user name="Joe" password="demo" />
 *   <user name="John" password="demo" />
 *   <role name="Administrator" users="John" />
 *   <role name="Writer" users="Joe,John" />
 * </module>
 *
 * The user passwords may be specified as clear text, SH1 or MD5 hashed by setting
 * {@link setPasswordMode PasswordMode} as <b>Clear</b>, <b>SH1</b> or <b>MD5</b>.
 * The default name for a guest user is <b>Guest</b>. It may be changed
 * by setting {@link setGuestName GuestName} property.
 *
 * TUserManager may be used together with {@link TAuthManager} which manages
 * how users are authenticated and authorized in a Prado application.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Security
 * @since 3.0
 */
class TUserManager extends TModule
{
	/**
	 * extension name to the user file
	 */
	const USER_FILE_EXT='.xml';

	/**
	 * @var array list of users managed by this module
	 */
	private $_users=array();
	/**
	 * @var array list of roles managed by this module
	 */
	private $_roles=array();
	/**
	 * @var string guest name
	 */
	private $_guestName='Guest';
	/**
	 * @var string password mode, Clear|MD5|SH1
	 */
	private $_passwordMode='MD5';
	/**
	 * @var boolean whether the module has been initialized
	 */
	private $_initialized=false;
	/**
	 * @var string user/role information file
	 */
	private $_userFile=null;

	/**
	 * Initializes the module.
	 * This method is required by IModule and is invoked by application.
	 * It loads user/role information from the module configuration.
	 * @param TXmlElement module configuration
	 */
	public function init($config)
	{
		if($this->_userFile!==null)
		{
			if(is_file($this->_userFile))
			{
				$dom=new TXmlDocument;
				$dom->loadFromFile($this->_userFile);
				$this->loadUserData($dom);
			}
			else
				throw new TConfigurationException('usermanager_userfile_invalid',$this->_userFile);
		}
		$this->loadUserData($config);
		$this->_initialized=true;
	}

	/**
	 * Loads user/role information from an XML node.
	 * @param TXmlElement the XML node containing the user information
	 */
	private function loadUserData($xmlNode)
	{
		foreach($xmlNode->getElementsByTagName('user') as $node)
		{
			$name=strtolower($node->getAttribute('name'));
			$this->_users[$name]=$node->getAttribute('password');
			if(($roles=trim($node->getAttribute('roles')))!=='')
			{
				foreach(explode(',',$roles) as $role)
					$this->_roles[$name][]=$role;
			}
		}
		foreach($xmlNode->getElementsByTagName('role') as $node)
		{
			foreach(explode(',',$node->getAttribute('users')) as $user)
			{
				if(($user=trim($user))!=='')
					$this->_roles[strtolower($user)][]=$node->getAttribute('name');
			}
		}
	}

	/**
	 * @return string the full path to the file storing user/role information
	 */
	public function getUserFile()
	{
		return $this->_userFile;
	}

	/**
	 * @param string user/role data file path (in namespace form). The file format is XML
	 * whose content is similar to that user/role block in application configuration.
	 * @throws TInvalidOperationException if the module is already initialized
	 * @throws TConfigurationException if the file is not in proper namespace format
	 */
	public function setUserFile($value)
	{
		if($this->_initialized)
			throw new TInvalidOperationException('usermanager_userfile_unchangeable');
		else if(($this->_userFile=Prado::getPathOfNamespace($value,self::USER_FILE_EXT))===null)
			throw new TConfigurationException('usermanager_userfile_invalid',$value);
	}

	/**
	 * @return string guest name, defaults to 'Guest'
	 */
	public function getGuestName()
	{
		return $this->_guestName;
	}

	/**
	 * @param string name to be used for guest users.
	 */
	public function setGuestName($value)
	{
		$this->_guestName=$value;
	}

	/**
	 * @return string (Clear|MD5|SH1) how password is stored, clear text, or MD5 or SH1 hashed. Default to MD5.
	 */
	public function getPasswordMode()
	{
		return $this->_passwordMode;
	}

	/**
	 * @param string (Clear|MD5|SH1) how password is stored, clear text, or MD5 or SH1 hashed.
	 */
	public function setPasswordMode($value)
	{
		$this->_passwordMode=TPropertyValue::ensureEnum($value,array('Clear','MD5','SHA1'));
	}

	/**
	 * Validates if the username and password are correct.
	 * @param string user name
	 * @param string password
	 * @return boolean true if validation is successful, false otherwise.
	 */
	public function validateUser($username,$password)
	{
		if($this->_passwordMode==='MD5')
			$password=md5($password);
		else if($this->_passwordMode==='SHA1')
			$password=sha1($password);
		$username=strtolower($username);
		return (isset($this->_users[$username]) && $this->_users[$username]===$password);
	}

	/**
	 * Returns a user instance given the user name.
	 * @param string user name, null if it is a guest.
	 * @return TUser the user instance, null if the specified username is not in the user database.
	 */
	public function getUser($username=null)
	{
		if($username===null)
		{
			$user=new TUser($this);
			$user->setIsGuest(true);
			return $user;
		}
		else
		{
			$username=strtolower($username);
			if(isset($this->_users[$username]))
			{
				$user=new TUser($this);
				$user->setName($username);
				$user->setIsGuest(false);
				if(isset($this->_roles[$username]))
					$user->setRoles($this->_roles[$username]);
				return $user;
			}
			else
				return null;
		}
	}

	/**
	 * Sets a user as a guest.
	 * User name is changed as guest name, and roles are emptied.
	 * @param TUser the user to be changed to a guest.
	 */
	public function switchToGuest($user)
	{
		$user->setIsGuest(true);
		$user->setName($this->getGuestName());
		$user->setRoles(array());
	}
}

?>