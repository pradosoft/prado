<?php
/**
 * LoginPortlet class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2006-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 */

Prado::using('Application.Portlets.Portlet');

/**
 * LoginPortlet class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2006-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 */
class LoginPortlet extends Portlet
{
	public function validateUser($sender,$param)
	{
		$authManager=$this->Application->getModule('auth');
		if(!$authManager->login(strtolower($this->Username->Text),$this->Password->Text))
			$param->IsValid=false;
	}

	public function loginButtonClicked($sender,$param)
	{
		if($this->Page->IsValid)
			$this->Response->reload();
			//$this->Response->redirect($this->Application->getModule('auth')->getReturnUrl());
	}
}

