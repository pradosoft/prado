<?php

class UserLogin extends TPage
{
	public function login($sender,$param)
	{
		$manager=$this->Application->getModule('auth');
		if(!$manager->login($this->Username->Text,$this->Password->Text))
			$param->IsValid=false;
	}

	public function onLoadComplete($param)
	{
		parent::onLoadComplete($param);
		if($this->IsPostBack && $this->IsValid)
			$this->Response->redirect($this->Application->getModule('auth')->getReturnUrl());
	}
}

?>