<?php

class LoginPage extends TPage
{
	public function login($sender,$param)
	{
		$manager=$this->Application->getModule('auth');
		if($manager->login($this->username->Text,$this->password->Text))
			$this->Application->Response->redirect($this->Application->Request->Items['ReturnUrl']);
		else
			$this->error->Text='login failed';
	}

	public function defaultClicked($sender,$param)
	{
		$sender->Text="Clicked";
	}
}

?>