<?php
/**
 * TDbUserManager class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Security
 */

/**
 * Using IUserManager interface
 */
Prado::using('System.Security.IUserManager');
Prado::using('System.Data.TDataSourceConfig');
Prado::using('System.Security.TUser');

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
 *     class="System.Data.TDataSourceConfig" ..../>
 * <module id="users"
 *     class="System.Security.TDbUserManager"
 *     UserClass="Path.To.MyUserClass"
 *     ConnectionID="db" />
 * <module id="auth"
 *     class="System.Security.TAuthManager"
 *     UserManager="users" LoginPage="Path.To.LoginPage" />
 * </code>
 *
 * In the above, {@link setUserClass UserClass} specifies what class will be used
 * to create user instance. The class must extend from {@link TDbUser}.
 * {@link setConnectionID ConnectionID} refers to the ID of a {@link TDataSourceConfig} module
 * which specifies how to establish database connection to retrieve user information.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id$
 * @package System.Security
 * @since 3.1.0
 */
class TDbUserManager extends TModule implements IUserManager
{
	private $_connID='';
	private $_conn;
	private $_guestName='Guest';
	private $_userClass='';
	private $_userFactory;

	/**
	 * Initializes the module.
	 * This method is required by IModule and is invoked by application.
	 * @param TXmlElement module configuration
	 */
	public function init($config)
	{
		if($this->_userClass==='')
			throw new TConfigurationException('dbusermanager_userclass_required');
		$this->_userFactory=Prado::createComponent($this->_userClass,$this);
		if(!($this->_userFactory instanceof TDbUser))
			throw new TInvalidDataTypeException('dbusermanager_userclass_invalid',$this->_userClass);
	}

	/**
	 * @return string the user class name in namespace format. Defaults to empty string, meaning not set.
	 */
	public function getUserClass()
	{
		return $this->_userClass;
	}

	/**
	 * @param string the user class name in namespace format. The user class must extend from {@link TDbUser}.
	 */
	public function setUserClass($value)
	{
		$this->_userClass=$value;
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
	 * Validates if the username and password are correct.
	 * @param string user name
	 * @param string password
	 * @return boolean true if validation is successful, false otherwise.
	 */
	public function validateUser($username,$password)
	{
		return $this->_userFactory->validateUser($username,$password);
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
			$user=Prado::createComponent($this->_userClass,$this);
			$user->setIsGuest(true);
			return $user;
		}
		else
			return $this->_userFactory->createUser($username);
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
	 * @param string module ID.
	 */
	public function setConnectionID($value)
	{
		$this->_connID=$value;
	}

	/**
	 * @return TDbConnection the database connection that may be used to retrieve user data.
	 */
	public function getDbConnection()
	{
		if($this->_conn===null)
			$this->_conn=$this->createDbConnection($this->_connID);
		return $this->_conn;
	}

	/**
	 * Creates the DB connection.
	 * @param string the module ID for TDataSourceConfig
	 * @return TDbConnection the created DB connection
	 * @throws TConfigurationException if module ID is invalid or empty
	 */
	protected function createDbConnection($connectionID)
	{
		if($connectionID!=='')
		{
			$conn=$this->getApplication()->getModule($connectionID);
			if($conn instanceof TDataSourceConfig)
				return $conn->getDbConnection();
			else
				throw new TConfigurationException('dbusermanager_connectionid_invalid',$connectionID);
		}
		else
			throw new TConfigurationException('dbusermanager_connectionid_required');
	}
}


/**
 * TDbUser class
 *
 * TDbUser is the base user class for using together with {@link TDbUserManager}.
 * Two methods are declared and must be implemented in the descendant classes:
 * - {@link validateUser()}: validates if username and password are correct entries.
 * - {@link createUser()}: creates a new user instance given the username
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id$
 * @package System.Security
 * @since 3.1.0
 */
abstract class TDbUser extends TUser
{
	private $_connection;

	/**
	 * Returns a database connection that may be used to retrieve data from database.
	 *
	 * @return TDbConnection database connection that may be used to retrieve data from database
	 */
	public function getDbConnection()
	{
		if($this->_connection===null)
		{
			$userManager=$this->getManager();
			if($userManager instanceof TDbUserManager)
			{
				$connection=$userManager->getDbConnection();
				if($connection instanceof TDbConnection)
				{
					$connection->setActive(true);
					$this->_connection=$connection;
				}
			}
			if($this->_connection===null)
				throw new TConfigurationException('dbuser_dbconnection_invalid');
		}
		return $this->_connection;
	}

	/**
	 * Validates if username and password are correct entries.
	 * Usually, this is accomplished by checking if the user database
	 * contains this (username, password) pair.
	 * You may use {@link getDbConnection DbConnection} to deal with database.
	 * @param string username (case-sensitive)
	 * @param string password
	 * @return boolean whether the validation succeeds
	 */
	abstract public function validateUser($username,$password);

	/**
	 * Creates a new user instance given the username.
	 * This method usually needs to retrieve necessary user information
	 * (e.g. role, name, rank, etc.) from the user database according to
	 * the specified username. The newly created user instance should be
	 * initialized with these information.
	 *
	 * If the username is invalid (not found in the user database), null
	 * should be returned.
	 *
	 * You may use {@link getDbConnection DbConnection} to deal with database.
	 *
	 * @param string username (case-sensitive)
	 * @return TDbUser the newly created and initialized user instance
	 */
	abstract public function createUser($username);
}

?>