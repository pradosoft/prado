<?php

class Login extends \Prado\Web\UI\TPage
{
	private string $_errorMessage = '';

	public function getErrorMessage(): string
	{
		return $this->_errorMessage;
	}

	public function onLoad($param)
	{
		parent::onLoad($param);

		// Already authenticated — send to home.
		if (!$this->Application->getUser()->getIsGuest()) {
			$this->Response->redirect($this->Service->constructUrl('Home'));
			return;
		}

		// Plain-HTML form submit — handle directly from request data.
		if ($this->Request->getRequestType() === 'POST'
			&& $this->Request->contains('auth_username')
		) {
			/** @var \Prado\Security\TAuthManager $auth */
			$auth = $this->Application->getModule('auth');

			$username = (string) $this->Request->itemAt('auth_username');
			$password = (string) $this->Request->itemAt('auth_password');
			$remember = $this->Request->contains('auth_remember');
			$expire = $remember ? 86400 * 30 : 0; // 30 days when "remember me"

			if ($auth->login($username, $password, $expire)) {
				$returnUrl = $auth->getReturnUrl();
				if (!$returnUrl) {
					$returnUrl = $this->Service->constructUrl('Home');
				}
				// Clear the stored return URL so it doesn't persist across logins.
				$this->Application->getSession()->remove($auth->getReturnUrlVarName());
				$this->Response->redirect($returnUrl);
			} else {
				$this->_errorMessage = 'Invalid username or password.';
			}
		}
	}
}
