<?php
/**
 * BlogUserManager class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 */

Prado::using('System.Security.IUserManager');
Prado::using('Application.Common.BlogUser');

/**
 * BlogUserManager class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 */
class BlogUserManager extends TModule implements IUserManager
{
	public function getGuestName()
	{
		return 'Guest';
	}

	/**
	 * Returns a user instance given the user name.
	 * @param string user name, null if it is a guest.
	 * @return TUser the user instance, null if the specified username is not in the user database.
	 */
	public function getUser($username=null)
	{
		if($username===null)
			return new BlogUser($this);
		else
		{
			$username=strtolower($username);
			$db=$this->Application->getModule('data');
			if(($userRecord=$db->queryUserByName($username))!==null)
			{
				$user=new BlogUser($this);
				$user->setID($userRecord->ID);
				$user->setName($username);
				$user->setIsGuest(false);
				$user->setRoles($userRecord->Role===UserRecord::ROLE_USER?'user':'admin');
				return $user;
			}
			else
				return null;
		}
	}

	/**
	 * Validates if the username and password are correct.
	 * @param string user name
	 * @param string password
	 * @return boolean true if validation is successful, false otherwise.
	 */
	public function validateUser($username,$password)
	{
		$db=$this->Application->getModule('data');
		if(($userRecord=$db->queryUserByName($username))!==null)
			return $userRecord->Password===md5($password) && $userRecord->Status===UserRecord::STATUS_NORMAL;
		else
			return false;
	}
}

?>