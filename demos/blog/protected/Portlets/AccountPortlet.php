<?php

Prado::using('Application.Portlets.Portlet');

class AccountPortlet extends Portlet
{
	public function logout($sender,$param)
	{
		$this->Application->getModule('auth')->logout();
		$this->Response->reload();
	}
}

?>