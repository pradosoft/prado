<?php

class MainLayout extends TTemplateControl
{
	public function logout($sender,$param)
	{
		$this->Application->getModule('auth')->logout();
		$this->Response->reload();
	}
}

?>