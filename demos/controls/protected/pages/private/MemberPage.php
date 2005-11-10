<?php

class MemberPage extends TPage
{
	public function logout($sender,$param)
	{
		$this->Application->AuthManager->logout();
		$this->Application->Response->redirect($this->Application->Service->constructUrl('home'));
	}
}

?>