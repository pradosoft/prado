<?php
/**
 * ErrorReport class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado4
 * @copyright Copyright &copy; 2006-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado4/blob/master/LICENSE
 */

/**
 * ErrorReport class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado4
 * @copyright Copyright &copy; 2006-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado4/blob/master/LICENSE
 */
class ErrorReport extends BlogPage
{
	public function onLoad($param)
	{
		parent::onLoad($param);
		$this->ErrorMessage->Text=$this->Application->SecurityManager->validateData(urldecode($this->Request['msg']));
	}
}

