<?php
/**
 * ErrorReport class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2006-2015 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 */

/**
 * ErrorReport class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2006-2015 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 */
class ErrorReport extends BlogPage
{
	public function onLoad($param)
	{
		parent::onLoad($param);
		$this->ErrorMessage->Text=$this->Application->SecurityManager->validateData(urldecode($this->Request['msg']));
	}
}

