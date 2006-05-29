<?php

Prado::using('Application.Portlets.Portlet');

class LoginPortlet extends Portlet
{
	public function validateUser($sender,$param)
	{
		$authManager=$this->Application->getModule('auth');
		if(!$authManager->login($this->Username->Text,$this->Password->Text))
			$param->IsValid=false;
	}

	public function loginButtonClicked($sender,$param)
	{
		if($this->Page->IsValid)
			$this->Response->reload();
			//$this->Response->redirect($this->Application->getModule('auth')->getReturnUrl());
	}
}

?>