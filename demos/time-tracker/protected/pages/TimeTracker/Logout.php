<?php
/**
 * Logout class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado4
 * @copyright Copyright &copy; 2005-2006 PradoSoft
 * @license https://github.com/pradosoft/prado4/blob/master/LICENSE
 * @package Demos
 */

/**
 * Logout page class.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Demos
 * @since 3.1
 */
class Logout extends TPage
{
	/**
	 * Logs out the current user and redirect to default page.
	 */
	function onLoad($param)
	{
		$this->Application->getModule('auth')->logout();
		$url = $this->Service->constructUrl($this->Service->DefaultPage);
		$this->Response->redirect($url);
	}
}

