<?php

class Layout extends TTemplateControl
{
	public function logout($sender,$param)
	{
		$this->Application->getModule('auth')->logout();
		$this->Response->redirect($this->Service->constructUrl('Home',null,false));
	}
}

?>