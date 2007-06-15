<?php
require_once dirname(__FILE__).'/../phpunit.php';

Prado::using('System.Security.TUser');
Prado::using('System.Security.TUserManager');

/**
 * @package System.Security
 */
class TUserTest extends PHPUnit_Framework_TestCase {
	public static $app=null;
	public static $mgr=null;

	public function setUp() {
		if(self::$app === null) {
			self::$app = new TApplication(dirname(__FILE__).'/app');
			prado::setPathofAlias('App', dirname(__FILE__));
		}

		if (self::$mgr===null) {
			$config=new TXmlDocument('1.0','utf8');
			$config->loadFromString('<users><user name="Joe" password="demo"/><user name="John" password="demo" /><role name="Administrator" users="John" /><role name="Writer" users="Joe,John" /></users>');
			self::$mgr=new TUserManager();
			self::$mgr->init($config);
		}
	}

	public function tearDown() {
	}

	public function testConstruct() {
		$user = new TUser (self::$mgr);
		self::assertEquals('Guest', $user->getName());
		self::assertEquals(self::$mgr, $user->getManager());
	}
	
	public function testManager() {
		$user = new TUser (self::$mgr);
		self::assertEquals(self::$mgr, $user->getManager());
	}
	
	public function testName() {
		$user = new TUser (self::$mgr);
		$user->setName('joe');
		self::assertEquals('joe', $user->getName());
	}
	
	public function testIsGuest() {
		$user = new TUser (self::$mgr);
		$user->setName('John');
		$user->setIsGuest(false);
		$user->setRoles('Administrator, Writer');
		self::assertFalse($user->getIsGuest());
		$user->setIsGuest(true);
		self::assertTrue($user->getIsGuest());
		self::assertEquals(array(),$user->getRoles());
	}
	
	public function testRoles() {
		$user=new TUser(self::$mgr);
		$user->setRoles(array('Administrator','Writer'));
		self::assertEquals(array('Administrator','Writer'), $user->getRoles());
		$user->setRoles('Reader,User');
		self::assertEquals(array('Reader','User'), $user->getRoles());
	}
	
	public function testIsInRole() {
		$user=new TUser(self::$mgr);
		$user->setRoles(array('Administrator','Writer'));
		// Roles are case insensitive
		self::assertTrue($user->IsInRole('writer'));
		self::assertTrue($user->IsInRole('Writer'));
		self::assertFalse($user->isInRole('Reader'));
	}
	
	public function testSaveToString() {
		$user = new TUser (self::$mgr);
		$user->setName('John');
		$user->setIsGuest(false);
		$user->setRoles('Administrator, Writer');
		// State array should now be :
		$assumedState=array ('Name' => 'John', 'IsGuest' => false, 'Roles' => array ('Administrator', 'Writer'));
		self::assertEquals(serialize($assumedState), $user->saveToString());
	}
	
	public function testLoadFromString() {
		$user = new TUser (self::$mgr);
		$user->setName('John');
		$user->setIsGuest(false);
		$user->setRoles('Administrator, Writer');
		$save=$user->saveToString();
		
		$user2 = new TUser (self::$mgr);
		$user2->loadFromString($save);
		
		self::assertEquals($user, $user2);
	}
	
	/* getState & setState are protected methods, will be tested with other tests.
	public function testState() {
		throw new PHPUnit_Framework_IncompleteTestError();
	}
	*/

	public function testStateChanged() {
		$user = new TUser (self::$mgr);
		$user->setName('John');
		self::assertTrue($user->getStateChanged());
		$user->setStateChanged(false);
		self::assertFalse($user->getStateChanged());
	}

}

?>
