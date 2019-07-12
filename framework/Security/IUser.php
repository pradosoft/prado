<?php
/**
 * Core interfaces essential for TApplication class.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Security
 */

namespace Prado\Security;

/**
 * IUser interface.
 *
 * This interface must be implemented by user objects.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Security
 * @since 3.0
 */
interface IUser
{
	/**
	 * @return string username
	 */
	public function getName();
	/**
	 * @param string $value username
	 */
	public function setName($value);
	/**
	 * @return bool if the user is a guest
	 */
	public function getIsGuest();
	/**
	 * @param bool $value if the user is a guest
	 */
	public function setIsGuest($value);
	/**
	 * @return array list of roles that the user is of
	 */
	public function getRoles();
	/**
	 * @param mixed $value
	 * @return array|string list of roles that the user is of. If it is a string, roles are assumed by separated by comma
	 */
	public function setRoles($value);
	/**
	 * @param string $role role to be tested
	 * @return bool whether the user is of this role
	 */
	public function isInRole($role);
	/**
	 * @return string user data that is serialized and will be stored in session
	 */
	public function saveToString();
	/**
	 * @param string $string user data that is serialized and restored from session
	 * @return IUser the user object
	 */
	public function loadFromString($string);
}
