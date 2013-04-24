<?php
/**
 * LoginPortlet class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id: LoginPortlet.php 3189 2012-07-12 12:16:21Z ctrlaltca $
 */

Prado::using('Application.Portlets.Portlet');

/**
 * LoginPortlet class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2006 PradoSoft
 * @license http://www.pradosoft.com/license/
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

