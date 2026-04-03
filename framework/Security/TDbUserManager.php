<?php

/**
 * TDbUserManager class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Security;

use Prado\Security\Traits\TUserManagerTrait;
use Prado\Data\TDataSourceConfig;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\Util\IDbModule;

/**
 * TDbUserManager class
 *
 * TDbUserManager manages user accounts that are stored in a database.
 * TDbUserManager is mainly designed to be used together with {@see \Prado\Security\TAuthManager}
 * which manages how users are authenticated and authorized in a Prado application.
 *
 * To use TDbUserManager together with TAuthManager, configure them in
 * the application configuration like following:
 * ```xml
 * <module id="db"
 *     class="Prado\Data\TDataSourceConfig" ..../>
 * <module id="users"
 *     class="Prado\Security\TDbUserManager"
 *     UserClass="Path\To\MyUserClass"
 *     ConnectionID="db" />
 * <module id="auth"
 *     class="Prado\Security\TAuthManager"
 *     UserManager="users" LoginPage="Path\To\LoginPage" />
 * ```
 *
 * In the above, {@see setUserClass UserClass} specifies what class will be used
 * to create user instance. The class must extend from {@see \Prado\Security\TDbUser}.
 * {@see setConnectionID ConnectionID} refers to the ID of a {@see \Prado\Data\TDataSourceConfig} module
 * which specifies how to establish database connection to retrieve user information.
 *
 * Roles for the class can be set up by comma-separated string or by PHP array via
 * {@see setUniqueRoles()}. Roles can also be set in the Application Parameters via
 * {@see setRolesAppParameterId()}. The resolution order for unique roles is:
 *   1. The user class ({@see TDbUser::getUniqueRoles()}) — highest priority
 *   2. Application Parameters (identified by {@see setRolesAppParameterId()})
 *   3. The {@see setUniqueRoles()} value set directly on this module — lowest priority
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.1.0
 */
class TDbUserManager extends \Prado\TModule implements IUserManager, IDbModule
{
	use TUserManagerTrait;

	/** @var \Prado\Data\TDbConnection The connection to the database. */
	private $_dbConnection;

	/** @var string The string ID of the TDataSourceConfig. */
	private $_connectionID = '';

	/** @var string The name of users who are not logged in. */
	private $_guestName = 'Guest';

	/** @var string The namespaced class of the User Factory. */
	private $_userClass = '';

	/** @var ?array The application parameter ID of the unique roles. */
	private $_rolesAppParameterId;

	/** @var ?array The unique roles set on the module by configuration. */
	private $_uniqueRoles;

	/** @var TDbUser The Factory for users. */
	private $_userFactory;

	/** @var bool whether the module has been initialized. */
	private $_initialized = false;

	/**
	 * Initializes.
	 * @param array|\Prado\Xml\TXmlElement $config module configuration
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
		parent::init($config);
		$this->_initialized = true;
	}

	/**
	 * @return string the user class name in namespace format. Defaults to empty string, meaning not set.
	 */
	public function getUserClass()
	{
		return $this->_userClass;
	}

	/**
	 * @param string $value the user class name in namespace format. The user class must extend from {@see \Prado\Security\TDbUser}.
	 */
	public function setUserClass($value)
	{
		$this->_userClass = $value;
	}

	/**
	 * This gets the ID of the Application Parameter for Roles.
	 * @return ?string The parameter ID of the application roles. Default is null.
	 */
	public function getRolesAppParameterId()
	{
		return $this->_rolesAppParameterId;
	}

	/**
	 * This sets the ID of the Application Parameter for Roles.
	 * It may not be set after initialization.
	 * @param ?string $value The parameter ID of the application roles.
	 * @throws TInvalidOperationException when called after the module has been initialized.
	 */
	public function setRolesAppParameterId($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('dbusermanager_property_unchangeable', 'RolesAppParameterId');
		}

		$this->_rolesAppParameterId = $value;
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
	public function validateUser($username, #[\SensitiveParameter] $password)
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
		} else {
			$user = $this->_userFactory->createUser($username);
		}
		if ($user) {
			$this->onFinalizeUser($user);
		}
		return $user;
	}

	/**
	 * If the module is configured for roles by Application Parameter or by Module
	 * parameter, then it is returned. Otherwise null.
	 * @return ?array The unique roles from the application parameter, or null if no
	 *   parameter ID is configured. Returns an empty array if the parameter ID is
	 *   configured but not present in the application parameters.
	 * @since 4.3.3
	 */
	protected function getUniqueRolesFromAppParameter()
	{
		$rolesParamId = $this->getRolesAppParameterId();
		if (!$rolesParamId) {
			return null;
		}

		$appParameters = $this->getApplication()->getParameters();
		if (!$appParameters->contains($rolesParamId)) {
			return [];
		}
		$appRoles = $appParameters->itemAt($rolesParamId);
		return array_filter(array_map('trim', explode(',', (string) $appRoles)));
	}

	/**
	 * Returns the unique roles in the application using the following priority order:
	 *   1. The user class ({@see TDbUser::getUniqueRoles()}) — if it returns non-null, it wins.
	 *   2. Application Parameters (identified by {@see getRolesAppParameterId()}) — if a
	 *      parameter ID is configured and the parameter exists, its value is used.
	 *   3. The {@see setUniqueRoles()} value set directly on this module.
	 * @return array The unique roles in the User Manager.
	 * @since 4.3.3
	 */
	public function getUniqueRoles()
	{
		$roles = $this->_userFactory->getUniqueRoles();
		if ($roles !== null) {
			return $roles;
		}
		$roles = $this->getUniqueRolesFromAppParameter();
		if ($roles !== null) {
			return $roles;
		}
		return $this->_uniqueRoles;
	}

	/**
	 * Returns the number of unique roles in the application, applying the same
	 * priority order as {@see getUniqueRoles()}:
	 *   1. The user class ({@see TDbUser::getUniqueRoleCount()}) — if it returns non-null, it wins.
	 *   2. Application Parameters (identified by {@see getRolesAppParameterId()}).
	 *   3. The {@see setUniqueRoles()} value set directly on this module.
	 * @return int The number of unique roles.
	 * @since 4.3.3
	 */
	public function getUniqueRoleCount()
	{
		$roleCount = $this->_userFactory->getUniqueRoleCount();
		if ($roleCount !== null) {
			return $roleCount;
		}
		$roles = $this->getUniqueRolesFromAppParameter();
		return count($roles ?? $this->_uniqueRoles ?? []);
	}

	/**
	 * Sets the unique roles for the application directly on this module.
	 * This is the lowest-priority source; it is overridden by Application Parameters
	 * and by the user class. Accepts either a comma-separated string (e.g. `"admin,editor,viewer"`)
	 * or a PHP array of role name strings. Must be set before the module is initialized.
	 *
	 * @param array|string $value The unique roles in the application
	 * @throws TInvalidOperationException when called after the module has been initialized.
	 * @throws TInvalidDataValueException when $value is neither a string nor an array.
	 * @since 4.3.3
	 */
	public function setUniqueRoles($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('dbusermanager_property_unchangeable', 'UniqueRoles');
		}

		if (is_string($value)) {
			$value = array_filter(array_map('trim', explode(',', (string) $value)));
		} elseif (!is_array($value)) {
			throw new TInvalidDataValueException('dbusermanager_uniqueroles_bad_data');
		}
		$this->_uniqueRoles = $value;
	}

	/**
	 * @return string the ID of a TDataSourceConfig module. Defaults to empty string, meaning not set.
	 */
	public function getConnectionID()
	{
		return $this->_connectionID;
	}

	/**
	 * Sets the ID of a TDataSourceConfig module.
	 * The datasource module will be used to establish the DB connection
	 * that will be used by the user manager.
	 * @param string $value module ID.
	 */
	public function setConnectionID($value)
	{
		$this->_connectionID = $value;
	}

	/**
	 * @return \Prado\Data\TDbConnection the database connection that may be used to retrieve user data.
	 */
	public function getDbConnection()
	{
		if ($this->_dbConnection === null) {
			$this->_dbConnection = $this->createDbConnection($this->_connectionID);
			$this->_dbConnection->setActive(true);
		}
		return $this->_dbConnection;
	}

	/**
	 * Creates the DB connection.
	 * @param string $connectionID the module ID for TDataSourceConfig
	 * @throws TConfigurationException if module ID is invalid or empty
	 * @return \Prado\Data\TDbConnection the created DB connection
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
	 * @param \Prado\Web\THttpCookie $cookie the cookie storing user authentication information
	 * @return TDbUser the user instance generated based on the cookie auth data, null if the cookie does not have valid auth data.
	 * @since 3.1.1
	 */
	public function getUserFromCookie($cookie)
	{
		$user = $this->_userFactory->createUserFromCookie($cookie);
		if ($user) {
			$this->onFinalizeUser($user);
		}
		return $user;
	}

	/**
	 * Saves user auth data into a cookie.
	 * @param \Prado\Web\THttpCookie $cookie the cookie to receive the user auth data.
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
