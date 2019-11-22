<?php
/**
 * TDbUserManager class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Security
 */

namespace Prado\Security;

use Prado\Data\TDataSourceConfig;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Prado;

/**
 * TDbUserManager class
 *
 * TDbUserManager manages user accounts that are stored in a database.
 * TDbUserManager is mainly designed to be used together with {@link TAuthManager}
 * which manages how users are authenticated and authorized in a Prado application.
 *
 * To use TDbUserManager together with TAuthManager, configure them in
 * the application configuration like following:
 * <code>
 * <module id="db"
 *     class="Prado\Data\TDataSourceConfig" ..../>
 * <module id="users"
 *     class="Prado\Security\TDbUserManager"
 *     UserClass="Path\To\MyUserClass"
 *     ConnectionID="db" />
 * <module id="auth"
 *     class="Prado\Security\TAuthManager"
 *     UserManager="users" LoginPage="Path\To\LoginPage" />
 * </code>
 *
 * In the above, {@link setUserClass UserClass} specifies what class will be used
 * to create user instance. The class must extend from {@link TDbUser}.
 * {@link setConnectionID ConnectionID} refers to the ID of a {@link TDataSourceConfig} module
 * which specifies how to establish database connection to retrieve user information.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Security
 * @since 3.1.0
 */
class TDbUserManager extends \Prado\TModule implements IUserManager
{
	private $_connID = '';
	private $_conn;
	private $_guestName = 'Guest';
	private $_userClass = '';
	private $_userFactory;

	/**
	 * Initializes the module.
	 * This method is required by IModule and is invoked by application.
	 * @param TXmlElement $config module configuration
	 */
	public function init($config)
	{
		if ($this->_userClass === '') {
			throw new TConfigurationException('dbusermanager_userclass_required');
		}
		$this->_userFactory = Prado::createComponent($this->_userClass, $this);
		if (!($this->_userFactory instanceof TDbUser)) {
			throw new TInvalidDataTypeException('dbusermanager_userclass_invalid', $this->_userClass);
		}
	}

	/**
	 * @return string the user class name in namespace format. Defaults to empty string, meaning not set.
	 */
	public function getUserClass()
	{
		return $this->_userClass;
	}

	/**
	 * @param string $value the user class name in namespace format. The user class must extend from {@link TDbUser}.
	 */
	public function setUserClass($value)
	{
		$this->_userClass = $value;
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
	 * Validates if the username and password are correct.
	 * @param string $username user name
	 * @param string $password password
	 * @return bool true if validation is successful, false otherwise.
	 */
	public function validateUser($username, $password)
	{
		return $this->_userFactory->validateUser($username, $password);
	}

	/**
	 * Returns a user instance given the user name.
	 * @param null|string $username user name, null if it is a guest.
	 * @return TUser the user instance, null if the specified username is not in the user database.
	 */
	public function getUser($username = null)
	{
		if ($username === null) {
			$user = Prado::createComponent($this->_userClass, $this);
			$user->setIsGuest(true);
			return $user;
		} else {
			return $this->_userFactory->createUser($username);
		}
	}

	/**
	 * @return string the ID of a TDataSourceConfig module. Defaults to empty string, meaning not set.
	 */
	public function getConnectionID()
	{
		return $this->_connID;
	}

	/**
	 * Sets the ID of a TDataSourceConfig module.
	 * The datasource module will be used to establish the DB connection
	 * that will be used by the user manager.
	 * @param string $value module ID.
	 */
	public function setConnectionID($value)
	{
		$this->_connID = $value;
	}

	/**
	 * @return TDbConnection the database connection that may be used to retrieve user data.
	 */
	public function getDbConnection()
	{
		if ($this->_conn === null) {
			$this->_conn = $this->createDbConnection($this->_connID);
			$this->_conn->setActive(true);
		}
		return $this->_conn;
	}

	/**
	 * Creates the DB connection.
	 * @param string $connectionID the module ID for TDataSourceConfig
	 * @throws TConfigurationException if module ID is invalid or empty
	 * @return TDbConnection the created DB connection
	 */
	protected function createDbConnection($connectionID)
	{
		if ($connectionID !== '') {
			$conn = $this->getApplication()->getModule($connectionID);
			if ($conn instanceof TDataSourceConfig) {
				return $conn->getDbConnection();
			} else {
				throw new TConfigurationException('dbusermanager_connectionid_invalid', $connectionID);
			}
		} else {
			throw new TConfigurationException('dbusermanager_connectionid_required');
		}
	}

	/**
	 * Returns a user instance according to auth data stored in a cookie.
	 * @param THttpCookie $cookie the cookie storing user authentication information
	 * @return TDbUser the user instance generated based on the cookie auth data, null if the cookie does not have valid auth data.
	 * @since 3.1.1
	 */
	public function getUserFromCookie($cookie)
	{
		return $this->_userFactory->createUserFromCookie($cookie);
	}

	/**
	 * Saves user auth data into a cookie.
	 * @param THttpCookie $cookie the cookie to receive the user auth data.
	 * @since 3.1.1
	 */
	public function saveUserToCookie($cookie)
	{
		$user = $this->getApplication()->getUser();
		if ($user instanceof TDbUser) {
			$user->saveUserToCookie($cookie);
		}
	}
}
