<?php
/**
 * AccountPortlet class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2006-2015 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 */

Prado::using('Application.Portlets.Portlet');

/**
 * AccountPortlet class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2006-2015 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 */
class AccountPortlet extends Portlet
{
	public function logout($sender,$param)
	{
		$this->Application->getModule('auth')->logout();
		$this->Response->reload();
	}
}

