<?php

class AdminOnly extends \Prado\Web\UI\TPage
{
	public function onLoad($param)
	{
		parent::onLoad($param);

		// switchUser: administrator switches the active session to another user.
		// After the switch the current user changes; redirect to Home to verify.
		if ($this->Request->itemAt('action') === 'switch') {
			$target = (string) $this->Request->itemAt('to');
			/** @var \Prado\Security\TAuthManager $auth */
			$auth = $this->Application->getModule('auth');
			if ($auth->switchUser($target)) {
				$this->Response->redirect($this->Service->constructUrl('Home'));
			}
		}
	}

	public function getCurrentUser(): string
	{
		return $this->Application->getUser()->getName();
	}
}
