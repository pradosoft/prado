<?php

class AuthViewTest extends \Prado\Web\UI\TPage
{
	public function signIn($sender, $param)
	{
		$this->Application->getModule('auth')->login('Joe', 'demo');
	}

	public function signOut($sender, $param)
	{
		$this->Application->getModule('auth')->logout();
	}

	public function count($sender, $param)
	{
		$this->Counter->Text = (int) $this->Counter->Text + 1;
	}

	public function purge($sender, $param)
	{
		$this->Purged->Text = 'purged';
	}
}
