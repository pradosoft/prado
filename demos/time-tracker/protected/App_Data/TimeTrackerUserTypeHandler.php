<?php
/**
 * TimeTrackerUserTypeHandler class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package Demos
 */

/**
 * SQLMap type handler for TimeTrackerUser.
 * The TimeTrackerUser requires an instance of IUserManager in constructor.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @package Demos
 * @since 3.1
 */
class TimeTrackerUserTypeHandler implements ITypeHandlerCallback
{
	/**
	 * Not implemented.
	 */
	public function getParameter($object)
	{
		throw new TimeTrackerException('Not implemented');
	}

	/**
	 * Not implemented.
	 */
	public function getResult($string)
	{
		throw new TimeTrackerException('Not implemented');		
	}

	/**
	 * Creates a new instance of TimeTrackerUser
	 * @param array result data
	 * @return TimeTrackerUser new user instance
	 */
	public function createNewInstance($row=null)
	{
		$manager = Prado::getApplication()->getModule('users');
		if(is_null($manager))
			$manager = new UserManager();
		return new TimeTrackerUser($manager);
	}
}

?>