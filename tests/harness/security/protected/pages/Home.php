<?php

class Home extends \Prado\Web\UI\TPage
{
	public function onLoad($param)
	{
		parent::onLoad($param);

		$action = $this->Request->itemAt('action');

		if ($action === 'logout') {
			/** @var \Prado\Security\TAuthManager $auth */
			$auth = $this->Application->getModule('auth');
			$auth->logout();
			$this->Response->redirect($this->Service->constructUrl('Home'));
		}
	}

	/**
	 * The login page URL that returns to this request.
	 */
	public function getLoginUrl(): string
	{
		return $this->Application->getModule('auth')->constructLoginUrl($this->Request->getRequestUri());
	}

	public function getCurrentUser(): string
	{
		$user = $this->Application->getUser();
		return $user->getIsGuest() ? 'Guest' : $user->getName();
	}
}
