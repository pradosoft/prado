<?php

/**
 * TUserManager class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Security;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\TApplication;
use Prado\TPropertyValue;
use Prado\Xml\TXmlDocument;
use Prado\Xml\TXmlElement;

/**
 * TUserManager class
 *
 * TUserManager manages a static list of users {@see \Prado\Security\TUser}.
 * The user information is specified via module configuration using the following XML syntax,
 * ```xml
 * <module id="users" class="Prado\Security\TUserManager" PasswordMode="Clear">
 *   <user name="Joe" password="demo" />
 *   <user name="John" password="demo" />
 *   <user name="Jerry" password="demo" roles="Writer,Administrator" />
 *   <role name="Administrator" users="John" />
 *   <role name="Writer" users="Joe,John" />
 * </module>
 * ```
 *
 * PHP configuration style:
 * ```php
 * array(
 *   'users' => array(
 *      'class' => 'Prado\Security\TUserManager',
 *      'properties' => array(
 *         'PasswordMode' => 'Clear',
 *       ),
 *       'users' => array(
 *          array('name'=>'Joe','password'=>'demo'),
 *          array('name'=>'John','password'=>'demo'),
 *          array('name'=>'Jerry','password'=>'demo','roles'=>'Administrator,Writer'),
 *       ),
 *       'roles' => array(
 *          array('name'=>'Administrator','users'=>'John'),
 *          array('name'=>'Writer','users'=>'Joe,John'),
 *       ),
 *    ),
 * )
 * ```
 *
 * In addition, user information can also be loaded from an external file
 * specified by {@see setUserFile UserFile} property. Note, the property
 * only accepts a file path in namespace format. The user file format is
 * similar to the above sample.
 *
 * User passwords may be specified as Clear text, or hashed in MD5, SHA1, or any algorithm
 * listed in `hash_algos` (when available). {@see setPasswordMode PasswordMode} is used to
 * set how the the password is hashed. Valid values for setPasswordMode are <b>Clear</b>,
 * <b>MD5</b>, <b>SHA1</b>, or any algorithm accepted by `hash`.
 *
 * The default name for a guest user is <b>Guest</b>. It may be changed
 * by setting {@see setGuestName GuestName} property.
 *
 * TUserManager may be used together with {@see \Prado\Security\TAuthManager} which manages
 * how users are authenticated and authorized in a Prado application.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Carl Mathisen <carl@kamikazemedia.no>
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 3.0
 * @see `hash` https://www.php.net/manual/en/function.hash.php
 */
class TUserManager extends \Prado\TModule implements IUserManager
{
	/**
	 * @var array list of users managed by this module
	 */
	private $_users = [];
	/**
	 * @var array list of unique roles managed by this module
	 */
	private $_uniqueRoles = [];
	/**
	 * @var array list of users and their rolls $_roles[user][(rolls)]
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
			} else {
				$dom = new TXmlDocument();
				$dom->loadFromFile($this->_userFile);
				$userFile = $this->configXml2Php($dom);
			}
			$this->loadUserDataFromPhp($userFile);
		}
		$this->_initialized = true;
		parent::init($config);
	}

	/*
	 * This selects the children elements with a tag name from $xmlNode.
	 * Each selected elements' attributes (array) are saved in an array and
	 * returned.
	 * @param TXmlElement $xmlNode The XML Element with children to search.
	 * @param string	  $tagName The tagName for selecting children by tag.
	 * @return array A list of arrays containing found elements' attributes.
	 * @since 4.3.3
	 * @note this may need to be elevated to TModule for more general use.
	 */
	protected function xmlChildren2AttributesArray(TXmlElement $xmlNode, string $tagName): array
	{
		$results = [];
		foreach ($xmlNode->getElementsByTagName($tagName) as $node) {
			$results[] = $node->getAttributes()->toArray();
		}

		return $results;
	}

	/*
	 * Converts XML configuration to PHP configuration for TUserManager.
	 * @param TXmlElement $xmlNode the variable containing the user information
	 * @since 4.3.3
	 * @note this may need to be elevated to TModule for more general use.
	 */
	protected function configXml2Php(?TXmlElement $xmlNode): array
	{
		$phpConfig = [];
		if ($xmlNode) {
			$users = $this->xmlChildren2AttributesArray($xmlNode, 'user');
			if (!empty($users)) {
				$phpConfig['users'] = $users;
			}
			$roles = $this->xmlChildren2AttributesArray($xmlNode, 'role');
			if (!empty($roles)) {
				$phpConfig['roles'] = $roles;
			}
		}
		return $phpConfig;
	}

	/*
	 * Loads user/role information
	 * @param mixed $config the variable containing the user information
	 */
	private function loadUserData($config)
	{
		if ($this->getApplication()->getConfigurationType() == TApplication::CONFIG_TYPE_XML && $config instanceof TXmlElement) {
			$config = $this->configXml2Php($config);
		}
		$this->loadUserDataFromPhp($config);
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
							if (!in_array($role, $this->_uniqueRoles)) {
								$this->_uniqueRoles[] = $role;
							}
						}
					}
				}
			}
		}
		if (isset($config['roles']) && is_array($config['roles'])) {
			foreach ($config['roles'] as $role) {
				$name = $role['name'] ?? '';
				if (!in_array($role, $this->_uniqueRoles)) {
					$this->_uniqueRoles[] = $role;
				}
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
	 * @return string The user class name.
	 */
	public function getUserClass()
	{
		return TUser::class;
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
	 * Returns the configured unique roles for users.
	 * @return array list of user role information
	 * @since 4.3.3
	 */
	public function getUniqueRoles()
	{
		return $this->_uniqueRoles;
	}

	/**
	 * Returns the number of unique roles in the application.
	 * @return int The number of unique roles.
	 * @since 4.3.3
	 */
	public function getUniqueRoleCount()
	{
		return count($this->_uniqueRoles);
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
		} elseif (($this->_userFile = Prado::getPathOfNamespace($value, $this->getApplication()->getConfigurationFileExt())) === null || !is_file($this->_userFile)) {
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
		try {
			$this->_passwordMode = TPropertyValue::ensureEnum($value, TUserManagerPasswordMode::class);
		} catch (TInvalidDataValueException $e) {
			$throw = true;
			if (function_exists('hash_algos')) {
				$availableHashes = array_flip(hash_algos());
				if (isset($availableHashes[$value])) {
					$this->_passwordMode = $value;
					$throw = false;
				}
			}
			if ($throw) {
				throw $e;
			}
		}
	}

	/**
	 * Validates if the username and password are correct.
	 * @param string $username user name
	 * @param string $password password
	 * @return bool true if validation is successful, false otherwise.
	 */
	public function validateUser($username, #[\SensitiveParameter] $password)
	{
		if ($this->_passwordMode === TUserManagerPasswordMode::MD5) {
			$password = md5($password);
		} elseif ($this->_passwordMode === TUserManagerPasswordMode::SHA1) {
			$password = sha1($password);
		} elseif (function_exists('hash') && $this->_passwordMode !== TUserManagerPasswordMode::Clear) {
			$password = hash($this->_passwordMode, $password);
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
		} else {
			$username = strtolower($username);
			if (isset($this->_users[$username])) {
				$user = new TUser($this);
				$user->setName($username);
				$user->setIsGuest(false);
				if (isset($this->_roles[$username])) {
					$user->setRoles($this->_roles[$username]);
				}
			} else {
				return null;
			}
		}

		$this->onFinalizeUser($user);
		return $user;
	}

	/**
	 * Returns a user instance according to auth data stored in a cookie.
	 * {@see getUserFromCookie()} uses {@see getUser()} to get the user.
	 * @param \Prado\Web\THttpCookie $cookie the cookie storing user authentication information
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
	 * Finalizes a user with the application after it is set up but before it is returned
	 * from {@see getUser()}.
	 * @param TUser $user The user to finalize.
	 * @since 4.3.3
	 */
	public function onFinalizeUser($user): void
	{
		$this->raiseEvent('onFinalizeUser', $this, $user);
	}

	/**
	 * Saves user auth data into a cookie.
	 * @param \Prado\Web\THttpCookie $cookie the cookie to receive the user auth data.
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
}
