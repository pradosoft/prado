<?php
/**
 * Core interfaces essential for TApplication class.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System
 */


/**
 * IUser interface.
 *
 * This interface must be implemented by user objects.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System
 * @since 3.0
 */
interface IUser
{
	/**
	 * @return string username
	 */
	public function getName();
	/**
	 * @param string username
	 */
	public function setName($value);
	/**
	 * @return boolean if the user is a guest
	 */
	public function getIsGuest();
	/**
	 * @param boolean if the user is a guest
	 */
	public function setIsGuest($value);
	/**
	 * @return array list of roles that the user is of
	 */
	public function getRoles();
	/**
	 * @return array|string list of roles that the user is of. If it is a string, roles are assumed by separated by comma
	 */
	public function setRoles($value);
	/**
	 * @param string role to be tested
	 * @return boolean whether the user is of this role
	 */
	public function isInRole($role);
	/**
	 * @return string user data that is serialized and will be stored in session
	 */
	public function saveToString();
	/**
	 * @param string user data that is serialized and restored from session
	 * @return IUser the user object
	 */
	public function loadFromString($string);
}