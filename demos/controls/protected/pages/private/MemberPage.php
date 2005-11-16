<?php

class MemberPage extends TPage
{
	public function logout($sender,$param)
	{
		$this->Application->getModule('auth')->logout();
		$this->Application->Response->redirect($this->Application->Service->constructUrl('home'));
	}
}

?>