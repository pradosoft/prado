<?php
/**
 * TUserManager class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Security
 */

namespace Prado\Security;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\TApplication;
use Prado\TPropertyValue;
use Prado\Xml\TXmlDocument;

/**
 * TUserManager class
 *
 * TUserManager manages a static list of users {@link TUser}.
 * The user information is specified via module configuration using the following XML syntax,
 * <code>
 * <module id="users" class="Prado\Security\TUserManager" PasswordMode="Clear">
 *   <user name="Joe" password="demo" />
 *   <user name="John" password="demo" />
 *   <role name="Administrator" users="John" />
 *   <role name="Writer" users="Joe,John" />
 * </module>
 * </code>
 *
 * PHP configuration style:
 * <code>
 * array(
 *   'users' => array(
 *      'class' => 'Prado\Security\TUserManager',
 *      'properties' => array(
 *         'PasswordMode' => 'Clear',
 *       ),
 *       'users' => array(
 *          array('name'=>'Joe','password'=>'demo'),
 *          array('name'=>'John','password'=>'demo'),
 *       ),
 *       'roles' => array(
 *          array('name'=>'Administrator','users'=>'John'),
 *          array('name'=>'Writer','users'=>'Joe,John'),
 *       ),
 *    ),
 * )
 * </code>
 *
 * In addition, user information can also be loaded from an external file
 * specified by {@link setUserFile UserFile} property. Note, the property
 * only accepts a file path in namespace format. The user file format is
 * similar to the above sample.
 *
 * The user passwords may be specified as clear text, SH1 or MD5 hashed by setting
 * {@link setPasswordMode PasswordMode} as <b>Clear</b>, <b>SHA1</b> or <b>MD5</b>.
 * The default name for a guest user is <b>Guest</b>. It may be changed
 * by setting {@link setGuestName GuestName} property.
 *
 * TUserManager may be used together with {@link TAuthManager} which manages
 * how users are authenticated and authorized in a Prado application.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Carl Mathisen <carl@kamikazemedia.no>
 * @package Prado\Security
 * @since 3.0
 */
class TUserManager extends \Prado\TModule implements IUserManager
{
	/**
	 * extension name to the user file
	 */
	const USER_FILE_EXT = '.xml';

	/**
	 * @var array list of users managed by this module
	 */
	private $_users = [];
	/**
	 * @var array list of roles managed by this module
	 */
	private $_roles = [];
	/**
	 * @var string guest name
	 */
	private $_guestName = 'Guest';
	/**
	 * @var TUserManagerPasswordMode password mode
	 */
	private $_passwordMode = TUserManagerPasswordMode::MD5;
	/**
	 * @var bool whether the module has been initialized
	 */
	private $_initialized = false;
	/**
	 * @var string user/role information file
	 */
	private $_userFile;

	/**
	 * Initializes the module.
	 * This method is required by IModule and is invoked by application.
	 * It loads user/role information from the module configuration.
	 * @param mixed $config module configuration
	 */
	public function init($config)
	{
		$this->loadUserData($config);
		if ($this->_userFile !== null) {
			if ($this->getApplication()->getConfigurationType() == TApplication::CONFIG_TYPE_PHP) {
				$userFile = include $this->_userFile;
				$this->loadUserDataFromPhp($userFile);
			} else {
				$dom = new TXmlDocument;
				$dom->loadFromFile($this->_userFile);
				$this->loadUserDataFromXml($dom);
			}
		}
		$this->_initialized = true;
	}

	/*
	 * Loads user/role information
	 * @param mixed $config the variable containing the user information
	 */
	private function loadUserData($config)
	{
		if ($this->getApplication()->getConfigurationType() == TApplication::CONFIG_TYPE_PHP) {
			$this->loadUserDataFromPhp($config);
		} else {
			$this->loadUserDataFromXml($config);
		}
	}

	/**
	 * Loads user/role information from an php array.
	 * @param array $config the array containing the user information
	 */
	private function loadUserDataFromPhp($config)
	{
		if (isset($config['users']) && is_array($config['users'])) {
			foreach ($config['users'] as $user) {
				$name = trim(strtolower($user['name'] ?? ''));
				$password = $user['password'] ?? '';
				$this->_users[$name] = $password;
				$roles = $user['roles'] ?? '';
				if ($roles !== '') {
					foreach (explode(',', $roles) as $role) {
						if (($role = trim($role)) !== '') {
							$this->_roles[$name][] = $role;
						}
					}
				}
			}
		}
		if (isset($config['roles']) && is_array($config['roles'])) {
			foreach ($config['roles'] as $role) {
				$name = $role['name'] ?? '';
				$users = $role['users'] ?? '';
				foreach (explode(',', $users) as $user) {
					if (($user = trim($user)) !== '') {
						$this->_roles[strtolower($user)][] = $name;
					}
				}
			}
		}
	}

	/**
	 * Loads user/role information from an XML node.
	 * @param TXmlElement $xmlNode the XML node containing the user information
	 */
	private function loadUserDataFromXml($xmlNode)
	{
		foreach ($xmlNode->getElementsByTagName('user') as $node) {
			$name = trim(strtolower($node->getAttribute('name')));
			$this->_users[$name] = $node->getAttribute('password');
			if (($roles = trim($node->getAttribute('roles'))) !== '') {
				foreach (explode(',', $roles) as $role) {
					if (($role = trim($role)) !== '') {
						$this->_roles[$name][] = $role;
					}
				}
			}
		}
		foreach ($xmlNode->getElementsByTagName('role') as $node) {
			foreach (explode(',', $node->getAttribute('users')) as $user) {
				if (($user = trim($user)) !== '') {
					$this->_roles[strtolower($user)][] = $node->getAttribute('name');
				}
			}
		}
	}

	/**
	 * Returns an array of all users.
	 * Each array element represents a single user.
	 * The array key is the username in lower case, and the array value is the
	 * corresponding user password.
	 * @return array list of users
	 */
	public function getUsers()
	{
		return $this->_users;
	}

	/**
	 * Returns an array of user role information.
	 * Each array element represents the roles for a single user.
	 * The array key is the username in lower case, and the array value is
	 * the roles (represented as an array) that the user is in.
	 * @return array list of user role information
	 */
	public function getRoles()
	{
		return $this->_roles;
	}

	/**
	 * @return string the full path to the file storing user/role information
	 */
	public function getUserFile()
	{
		return $this->_userFile;
	}

	/**
	 * @param string $value user/role data file path (in namespace form). The file format is XML
	 * whose content is similar to that user/role block in application configuration.
	 * @throws TInvalidOperationException if the module is already initialized
	 * @throws TConfigurationException if the file is not in proper namespace format
	 */
	public function setUserFile($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('usermanager_userfile_unchangeable');
		} elseif (($this->_userFile = Prado::getPathOfNamespace($value, self::USER_FILE_EXT)) === null || !is_file($this->_userFile)) {
			throw new TConfigurationException('usermanager_userfile_invalid', $value);
		}
	}

	/**
	 * @return string guest name, defaults to 'Guest'
	 */
	public function getGuestName()
	{
		return $this->_guestName;
	}

	/**
	 * @param string $value name to be used for guest users.
	 */
	public function setGuestName($value)
	{
		$this->_guestName = $value;
	}

	/**
	 * @return TUserManagerPasswordMode how password is stored, clear text, or MD5 or SHA1 hashed. Default to TUserManagerPasswordMode::MD5.
	 */
	public function getPasswordMode()
	{
		return $this->_passwordMode;
	}

	/**
	 * @param TUserManagerPasswordMode $value how password is stored, clear text, or MD5 or SHA1 hashed.
	 */
	public function setPasswordMode($value)
	{
		$this->_passwordMode = TPropertyValue::ensureEnum($value, 'Prado\\Security\\TUserManagerPasswordMode');
	}

	/**
	 * Validates if the username and password are correct.
	 * @param string $username user name
	 * @param string $password password
	 * @return bool true if validation is successful, false otherwise.
	 */
	public function validateUser($username, $password)
	{
		if ($this->_passwordMode === TUserManagerPasswordMode::MD5) {
			$password = md5($password);
		} elseif ($this->_passwordMode === TUserManagerPasswordMode::SHA1) {
			$password = sha1($password);
		}
		$username = strtolower($username);
		return (isset($this->_users[$username]) && $this->_users[$username] === $password);
	}

	/**
	 * Returns a user instance given the user name.
	 * @param null|string $username user name, null if it is a guest.
	 * @return TUser the user instance, null if the specified username is not in the user database.
	 */
	public function getUser($username = null)
	{
		if ($username === null) {
			$user = new TUser($this);
			$user->setIsGuest(true);
			return $user;
		} else {
			$username = strtolower($username);
			if (isset($this->_users[$username])) {
				$user = new TUser($this);
				$user->setName($username);
				$user->setIsGuest(false);
				if (isset($this->_roles[$username])) {
					$user->setRoles($this->_roles[$username]);
				}
				return $user;
			} else {
				return null;
			}
		}
	}

	/**
	 * Returns a user instance according to auth data stored in a cookie.
	 * @param THttpCookie $cookie the cookie storing user authentication information
	 * @return TUser the user instance generated based on the cookie auth data, null if the cookie does not have valid auth data.
	 * @since 3.1.1
	 */
	public function getUserFromCookie($cookie)
	{
		if (($data = $cookie->getValue()) !== '') {
			$data = unserialize($data);
			if (is_array($data) && count($data) === 2) {
				[$username, $token] = $data;
				if (isset($this->_users[$username]) && $token === md5($username . $this->_users[$username])) {
					return $this->getUser($username);
				}
			}
		}
		return null;
	}

	/**
	 * Saves user auth data into a cookie.
	 * @param THttpCookie $cookie the cookie to receive the user auth data.
	 * @since 3.1.1
	 */
	public function saveUserToCookie($cookie)
	{
		$user = $this->getApplication()->getUser();
		$username = strtolower($user->getName());
		if (isset($this->_users[$username])) {
			$data = [$username, md5($username . $this->_users[$username])];
			$cookie->setValue(serialize($data));
		}
	}

	/**
	 * Sets a user as a guest.
	 * User name is changed as guest name, and roles are emptied.
	 * @param TUser $user the user to be changed to a guest.
	 */
	public function switchToGuest($user)
	{
		$user->setIsGuest(true);
	}
}
