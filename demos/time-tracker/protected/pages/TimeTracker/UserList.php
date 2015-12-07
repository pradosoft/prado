<?php
/**
 * UserList page class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2006 PradoSoft
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 * @package Demos
 */

/**
 * List all users in a repeater.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Demos
 * @since 3.1
 */
class UserList extends TPage
{
	/**
	 * Load all the users and display them in a repeater.
	 */
	function onLoad($param)
	{
		$userDao = $this->Application->Modules['daos']->getDao('UserDao');
		$this->list->DataSource = $userDao->getAllUsers();
		$this->list->dataBind();
	}
}

