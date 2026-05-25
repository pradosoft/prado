<?php

class HttpCookieTest extends \Prado\Web\UI\TPage
{
	public function onLoad($param)
	{
		parent::onLoad($param);

		$action = $this->Request->itemAt('action') ?? '';

		switch ($action) {
			case 'set-basic':
				$cookie = new \Prado\Web\THttpCookie('basic_cookie', 'basic_value');
				$this->Response->getCookies()->add($cookie);
				break;

			case 'set-httponly':
				$cookie = new \Prado\Web\THttpCookie('httponly_cookie', 'secret_value');
				$cookie->setHttpOnly(true);
				$this->Response->getCookies()->add($cookie);
				break;

			case 'set-expiry':
				$cookie = new \Prado\Web\THttpCookie('expiry_cookie', 'expires_value');
				$cookie->setExpire(time() + 3600);
				$this->Response->getCookies()->add($cookie);
				break;

			case 'set-samesite-lax':
				$cookie = new \Prado\Web\THttpCookie('samesite_cookie', 'lax_value');
				$cookie->setSameSite(\Prado\Web\THttpCookieSameSite::Lax);
				$this->Response->getCookies()->add($cookie);
				break;

			case 'set-path':
				$cookie = new \Prado\Web\THttpCookie('path_cookie', 'path_value');
				$cookie->setPath('/tests/FunctionalTests/web/');
				$this->Response->getCookies()->add($cookie);
				break;

			case 'remove':
				$name = $this->Request->itemAt('name') ?? 'basic_cookie';
				$cookie = new \Prado\Web\THttpCookie($name, '');
				$this->Response->removeCookie($cookie);
				break;
		}
	}

	/**
	 * Returns a JSON representation of the cookies sent with the current request
	 * ($_COOKIE reflects cookies set by PREVIOUS responses, not this one).
	 */
	public function getCookieJson()
	{
		return htmlspecialchars(json_encode($_COOKIE), ENT_QUOTES);
	}
}
