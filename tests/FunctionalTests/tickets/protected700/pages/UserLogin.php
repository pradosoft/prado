<?php

class UserLogin extends BasePage
{
	public function loginButtonClicked($sender,$param)
	{
		$authManager=$this->Application->getModule('auth');
		$authManager->login($this->Username->Text,$this->Password->Text);
		$this->Response->redirect($this->Service->constructUrl('Home'));
	}
}

?>