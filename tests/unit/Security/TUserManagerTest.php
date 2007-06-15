<?php
require_once dirname(__FILE__).'/../phpunit.php';

Prado::using('System.Security.TUserManager');

/**
 * @package System.Security
 */
class TUserManagerTest extends PHPUnit_Framework_TestCase {
	
	public static $app=null;
	public static $config=null;
	

	public function setUp() {
		
		if(self::$app === null) {
			self::$app = new TApplication(dirname(__FILE__).'/app');
			prado::setPathofAlias('App', dirname(__FILE__));
		}
		
		if (self::$config === null) {
			// Simulate a config file
			self::$config=new TXmlDocument('1.0','utf8');
			self::$config->loadFromString('<users><user name="Joe" password="demo"/><user name="John" password="demo" /><user name="test" password="test" roles="Reader, User"/><role name="Administrator" users="John" /><role name="Writer" users="Joe, John" /></users>');
		}
	}

	public function tearDown() {
	}

	public function testInit() {
		$userManager=new TUserManager ();
		$userManager->init (self::$config);
		self::assertEquals(array('joe'=>'demo','john'=>'demo', 'test' => 'test'), $userManager->getUsers());
		$userManager=null;
		// Test with a file
		if (is_writable (dirname(__FILE__))) {
			self::$config->saveToFile(dirname(__FILE__).'/users.xml');
			$userManager=new TUserManager();
			$userManager->setUserFile('App.users');
			$userManager->init (new TXmlDocument()); // Empty config
			self::assertEquals(array('joe'=>'demo','john'=>'demo', 'test' => 'test'), $userManager->getUsers());
			unlink (dirname(__FILE__).'/users.xml');
		} 
	}
	
	public function testUsers() {
		$userManager=new TUserManager ();
		$userManager->init (self::$config);
		self::assertEquals(array('joe'=>'demo','john'=>'demo', 'test' => 'test'), $userManager->getUsers());
	}
	
	public function testRoles() {
		$userManager=new TUserManager ();
		$userManager->init (self::$config);
		self::assertEquals(array('joe'=>array ('Writer'), 'john'=>array ('Administrator','Writer'), 'test' => array ('Reader', 'User')), $userManager->getRoles());
	}
	
	public function testUserFile() {
		$userManager=new TUserManager ();
		try {
			$userManager->setUserFile('invalidFile');
			self::fail ('Exception TConfigurationException not thrown');
		} catch (TConfigurationException $e) {}
		$userManager=null;
		if (is_writable (dirname(__FILE__))) {
			self::$config->saveToFile(dirname(__FILE__).'/users.xml');
			$userManager=new TUserManager();
			$userManager->setUserFile('App.users');
			$userManager->init (new TXmlDocument()); // Empty config
			self::assertEquals(dirname(__FILE__).'/users.xml', $userManager->getUserFile());
			unlink (dirname(__FILE__).'/users.xml');
			$userManager=null;
		} 
		$userManager=new TUserManager ();
		$userManager->init (self::$config);
		try {
			$userManager->setUserFile('App.users');
			self::fail ('Exception TInvalidOperationException not thrown');
		} catch (TInvalidOperationException $e) {}
	}
	
	public function testGuestName() {
		$userManager=new TUserManager ();
		self::assertEquals('Guest', $userManager->getGuestName());
		$userManager->setGuestName('Invite');
		self::assertEquals('Invite', $userManager->getGuestName());
	}
	
	public function testPasswordMode() {
		$userManager=new TUserManager ();
		$userManager->setPasswordMode('Clear');
		self::assertEquals('Clear', $userManager->getPasswordMode());
		$userManager->setPasswordMode('MD5');
		self::assertEquals('MD5', $userManager->getPasswordMode());
		$userManager->setPasswordMode('SHA1');
		self::assertEquals('SHA1', $userManager->getPasswordMode());
		try {
			$userManager->setPasswordMode('Invalid');
			self::fail ('Exception TInvalidDataValueException not thrown');
		} catch (TInvalidDataValueException $e) {}
		
	}
	
	public function testValidateUser() {
		$userManager=new TUserManager ();
		$userManager->init (self::$config);
		$userManager->setPasswordMode('Clear');
		self::assertTrue($userManager->validateUser('Joe', 'demo'));
		self::assertFalse($userManager->validateUser('John', 'bad'));
	}
	
	public function testUser() {
		$userManager=new TUserManager ();
		$userManager->init (self::$config);
		$guest=$userManager->getUser(null);
		self::assertType('TUser', $guest);
		self::assertTrue($guest->getIsGuest());
		$user=$userManager->getUser('joe');
		self::assertType('TUser', $user);
		self::assertEquals('joe', $user->getName());
		self::assertEquals(array ('Writer'), $user->getRoles());
		self::assertFalse($user->getIsGuest());
		self::assertNull($userManager->getUser('badUser'));
	}
	
	public function testSwitchToGuest() {
		$userManager=new TUserManager ();
		$userManager->init (self::$config);
		$user=$userManager->getUser('joe');
		$userManager->switchToGuest($user);
		self::assertTrue($user->getIsGuest());
	}

}

?>
