<?php
/**
 * IUserManager interface file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Security
 */

namespace Prado\Security;

/**
 * IUserManager interface
 *
 * IUserManager specifies the interface that must be implemented by
 * a user manager class if it is to be used together with {@link TAuthManager}
 * and {@link TUser}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Security
 * @since 3.0
 */
interface IUserManager
{
	/**
	 * @return string name for a guest user.
	 */
	public function getGuestName();
	/**
	 * Returns a user instance given the user name.
	 * @param null|string $username user name, null if it is a guest.
	 * @return TUser the user instance, null if the specified username is not in the user database.
	 */
	public function getUser($username = null);
	/**
	 * Returns a user instance according to auth data stored in a cookie.
	 * @param THttpCookie $cookie the cookie storing user authentication information
	 * @return TUser the user instance generated based on the cookie auth data, null if the cookie does not have valid auth data.
	 * @since 3.1.1
	 */
	public function getUserFromCookie($cookie);
	/**
	 * Saves user auth data into a cookie.
	 * @param THttpCookie $cookie the cookie to receive the user auth data.
	 * @since 3.1.1
	 */
	public function saveUserToCookie($cookie);
	/**
	 * Validates if the username and password are correct.
	 * @param string $username user name
	 * @param string $password password
	 * @return bool true if validation is successful, false otherwise.
	 */
	public function validateUser($username, $password);
}
