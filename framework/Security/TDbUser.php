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

use Prado\Data\TDbConnection;
use Prado\Exceptions\TConfigurationException;

/**
 * TDbUser class
 *
 * TDbUser is the base user class for using together with {@link TDbUserManager}.
 * Two methods are declared and must be implemented in the descendant classes:
 * - {@link validateUser()}: validates if username and password are correct entries.
 * - {@link createUser()}: creates a new user instance given the username
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Security
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
		if ($this->_connection === null) {
			$userManager = $this->getManager();
			if ($userManager instanceof TDbUserManager) {
				$connection = $userManager->getDbConnection();
				if ($connection instanceof TDbConnection) {
					$connection->setActive(true);
					$this->_connection = $connection;
				}
			}
			if ($this->_connection === null) {
				throw new TConfigurationException('dbuser_dbconnection_invalid');
			}
		}
		return $this->_connection;
	}

	/**
	 * Validates if username and password are correct entries.
	 * Usually, this is accomplished by checking if the user database
	 * contains this (username, password) pair.
	 * You may use {@link getDbConnection DbConnection} to deal with database.
	 * @param string $username username (case-sensitive)
	 * @param string $password password
	 * @return bool whether the validation succeeds
	 */
	abstract public function validateUser($username, $password);

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
	 * @param string $username username (case-sensitive)
	 * @return TDbUser the newly created and initialized user instance
	 */
	abstract public function createUser($username);

	/**
	 * Creates a new user instance given the cookie containing auth data.
	 *
	 * This method is invoked when {@link TAuthManager::setAllowAutoLogin AllowAutoLogin} is set true.
	 * The default implementation simply returns null, meaning no user instance can be created
	 * from the given cookie.
	 *
	 * If you want to support automatic login (remember login), you should override this method.
	 * Typically, you obtain the username and a unique token from the cookie's value.
	 * You then verify the token is valid and use the username to create a user instance.
	 *
	 * @param THttpCookie $cookie the cookie storing user authentication information
	 * @return TDbUser the user instance generated based on the cookie auth data, null if the cookie does not have valid auth data.
	 * @see saveUserToCookie
	 * @since 3.1.1
	 */
	public function createUserFromCookie($cookie)
	{
		return null;
	}

	/**
	 * Saves necessary auth data into a cookie.
	 * This method is invoked when {@link TAuthManager::setAllowAutoLogin AllowAutoLogin} is set true.
	 * The default implementation does nothing, meaning auth data is not stored in the cookie
	 * (and thus automatic login is not supported.)
	 *
	 * If you want to support automatic login (remember login), you should override this method.
	 * Typically, you generate a unique token according to the current login information
	 * and save it together with the username in the cookie's value.
	 * You should avoid revealing the password in the generated token.
	 *
	 * @param THttpCookie $cookie the cookie to store the user auth information
	 * @see createUserFromCookie
	 * @since 3.1.1
	 */
	public function saveUserToCookie($cookie)
	{
	}
}
