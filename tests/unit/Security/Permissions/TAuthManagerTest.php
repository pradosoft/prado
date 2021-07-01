<?php

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Security\TAuthManager;
use Prado\Security\TUserManager;
use Prado\TApplication;
use Prado\Xml\TXmlDocument;

class TAuthManagerTest extends PHPUnit\Framework\TestCase
{
	public static $app = null;
	public static $usrMgr = null;

	protected function setUp(): void
	{
		// ini_set('session.use_cookies',0);
		// ini_set('session.cache_limiter', 'none');
		if (self::$app === null) {
			self::$app = new TApplication(__DIR__ . '/app');
		}

		// Make a fake user manager module
		if (self::$usrMgr === null) {
			self::$usrMgr = new TUserManager();
			$config = new TXmlDocument('1.0', 'utf8');
			$config->loadFromString('<users><user name="Joe" password="demo"/><user name="John" password="demo" /><role name="Administrator" users="John" /><role name="Writer" users="Joe,John" /></users>');
			self::$usrMgr->init($config);
			self::$app->setModule('users', self::$usrMgr);
		}
	}

	protected function tearDown(): void
	{
	}

	public function testInit()
	{
		$authManager = new TAuthManager();
		// Catch exception with null usermgr
		try {
			$authManager->init(null);
			self::fail('Expected TConfigurationException not thrown');
		} catch (TConfigurationException $e) {
		}

		$authManager->setUserManager('users');
		$authManager->init(null);
		self::assertEquals(self::$usrMgr, $authManager->getUserManager());
	}

	public function testUserManager()
	{
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);
		self::assertEquals(self::$usrMgr, $authManager->getUserManager());

		// test change
		try {
			$authManager->setUserManager('invalid');
			self::fail('Expected TInvalidOperationException not thrown');
		} catch (TInvalidOperationException $e) {
		}
	}

	public function testLoginPage()
	{
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);
		$authManager->setLoginPage('LoginPage');
		self::assertEquals('LoginPage', $authManager->getLoginPage());
	}

	public function testDoAuthentication()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
		// Not yet finished, Session won't start because of headers :( :(

		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);
		$authManager->setLoginPage('LoginPage');
		self::$app->raiseEvent('onAuthentication', self::$app, null);
	}

	public function testDoAuthorization()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testLeave()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testReturnUrlVarName()
	{
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);
		$authManager->setReturnUrlVarName('test');
		self::assertEquals('test', $authManager->getReturnUrlVarName());
		$authManager->setReturnUrlVarName('variable');
		self::assertEquals('variable', $authManager->getReturnUrlVarName());
	}

	public function testReturnUrl()
	{
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);
		$authManager->setReturnUrl('test');
		self::assertEquals('test', $authManager->getReturnUrl());
		$authManager->setReturnUrl('variable');
		self::assertEquals('variable', $authManager->getReturnUrl());
	}

	public function testAllowAutoLogin()
	{
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);
		$authManager->setAllowAutoLogin(true);
		self::assertEquals(true, $authManager->getAllowAutoLogin());
		$authManager->setAllowAutoLogin(false);
		self::assertEquals(false, $authManager->getAllowAutoLogin());
		$authManager->setAllowAutoLogin(1);
		self::assertEquals(true, $authManager->getAllowAutoLogin());
		$authManager->setAllowAutoLogin(0);
		self::assertEquals(false, $authManager->getAllowAutoLogin());
		$authManager->setAllowAutoLogin('true');
		self::assertEquals(true, $authManager->getAllowAutoLogin());
		$authManager->setAllowAutoLogin('false');
		self::assertEquals(false, $authManager->getAllowAutoLogin());
	}

	public function testAuthExpire()
	{
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);
		$authManager->setAuthExpire(86400);
		self::assertEquals(86400, $authManager->getAuthExpire());
		$authManager->setAuthExpire(0);
		self::assertEquals(0, $authManager->getAuthExpire());
	}

	public function testOnAuthenticate()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}
	public function testOnAuthExpire()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}
	public function testOnAuthorize()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testUserKey()
	{
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);
		$md5 = md5($authManager->getApplication()->getUniqueID() . 'prado:user');
		self::assertEquals($md5, $authManager->getUserKey());
	}

	public function testUpdateSessionUser()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testSwitchUser()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testLogin()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testLogout()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}
	
	protected $_handled = 0;
	public function myhandler()
	{
		$this->_handled++;
	}
	
	public function testOnLogin()
	{
		$this->_handled = 0;
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);
		$authManager->onLogin[] = [$this, 'myhandler'];
		$authManager->onLogin($this, null);
		self::assertEquals(1, $this->_handled);
	}
	public function testOnLoginFailed()
	{
		$this->_handled = 0;
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);
		$authManager->onLoginFailed[] = [$this, 'myhandler'];
		$authManager->onLoginFailed($this, null);
		self::assertEquals(1, $this->_handled);
	}
	public function testOnLogout()
	{
		$this->_handled = 0;
		$authManager = new TAuthManager();
		$authManager->setUserManager('users');
		$authManager->init(null);
		$authManager->onLogout[] = [$this, 'myhandler'];
		$authManager->onLogout($this, null);
		self::assertEquals(1, $this->_handled);
	}
}
