<?php
require_once dirname(__FILE__).'/../phpunit.php';

Prado::using('System.Security.TAuthManager');
Prado::using('System.Security.TUserManager');
Prado::using('System.Xml.TXmlDocument');

/**
 * @package System.Security
 */
class TAuthManagerTest extends PHPUnit_Framework_TestCase {
	
	public static $app = null;
	public static $usrMgr = null;

	public function setUp() {
		ini_set('session.use_cookies',0);
		ini_set('session.cache_limiter', 'none');
		if(self::$app === null) {
			self::$app = new TApplication(dirname(__FILE__).'/app');
		}
		
		// Make a fake user manager module
		if (self::$usrMgr === null) {
			self::$usrMgr=new TUserManager ();
			$config=new TXmlDocument('1.0','utf8');
			$config->loadFromString('<users><user name="Joe" password="demo"/><user name="John" password="demo" /><role name="Administrator" users="John" /><role name="Writer" users="Joe,John" /></users>');
			self::$usrMgr->init($config);
			self::$app->setModule('users', self::$usrMgr);
		}
	}

	public function tearDown() {
	}

	public function testInit() {
		$authManager=new TAuthManager ();
		// Catch exception with null usermgr
		try {
			$authManager->init(null);
			self::fail ('Expected TConfigurationException not thrown');
		} catch (TConfigurationException $e) {}
		
		$authManager->setUserManager('users');
		$authManager->init (null);
		self::assertEquals(self::$usrMgr, $authManager->getUserManager());
	}
	
	public function testUserManager() {
		$authManager=new TAuthManager ();
		$authManager->setUserManager('users');
		$authManager->init(null);
		self::assertEquals(self::$usrMgr, $authManager->getUserManager());
		
		// test change
		try {
			$authManager->setUserManager('invalid');
			self::fail ('Expected TInvalidOperationException not thrown');
		} catch (TInvalidOperationException $e) {}
		
	}
	
	public function testLoginPage() {
		$authManager=new TAuthManager ();
		$authManager->setUserManager('users');
		$authManager->init(null);
		$authManager->setLoginPage ('LoginPage');
		self::assertEquals('LoginPage', $authManager->getLoginPage());
	}
	
	public function testDoAuthentication() {
		throw new PHPUnit_Framework_IncompleteTestError();
		// Not yet finished, Session won't start because of headers :( :(

		$authManager=new TAuthManager ();
		$authManager->setUserManager('users');
		$authManager->init(null);
		$authManager->setLoginPage ('LoginPage');
		self::$app->raiseEvent ('onAuthentication', self::$app, null);
		
	}
	
	public function testDoAuthorization() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testLeave() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testReturnUrl() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testOnAuthenticate() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testOnAuthorize() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testUpdateSessionUser() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testLogin() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	
	public function testLogout() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}

}

?>
