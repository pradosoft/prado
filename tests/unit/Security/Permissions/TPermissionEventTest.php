<?php

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Security\Permissions\TPermissionEvent;
use Prado\Security\TAuthorizationRule;
use Prado\Security\TUserManager;
use Prado\TApplication;
use Prado\Xml\TXmlDocument;

class TPermissionEventTest extends PHPUnit\Framework\TestCase
{
	public static $app = null;
	public static $usrMgr = null;

	protected function setUp(): void
	{
		/*
		if (self::$app === null) {
			self::$app = new TApplication(__DIR__ . '../app');
		}

		// Make a fake user manager module
		if (self::$usrMgr === null) {
			self::$usrMgr = new TUserManager();
			$config = new TXmlDocument('1.0', 'utf8');
			self::$usrMgr->setPasswordMode('Clear');
			$config->loadFromString('<users><user name="Joe" password="demo"/><user name="John" password="demo" /><role name="Administrator" users="John" /><role name="Writer" users="Joe,John" /></users>');
			self::$usrMgr->init($config);
			self::$app->setModule('users', self::$usrMgr);
		}*/
	}

	protected function tearDown(): void
	{
	}

	public function testConstruct()
	{
		$name = 'Perm_Name';
		$description = 'description of permission';
		$events = ['dyPermissionNameAllowed'];
		$rules = [new TAuthorizationRule()];
		$perm = new TPermissionEvent($name, $description, $events, $rules);
		
		self::assertEquals(strtolower($name), $perm->getName());
		self::assertEquals($description, $perm->getDescription());
		self::assertEquals(array_map('strtolower', $events), $perm->getEvents());
		self::assertEquals($rules, $perm->getRules());
	}
	
	public function testName()
	{
		$perm = new TPermissionEvent();
		
		$perm->setName(null);
		self::assertEquals('', $perm->getName());
		
		$perm->setName($v = '');
		self::assertEquals($v, $perm->getName());
		
		$perm->setName($v = 'test_perm');
		self::assertEquals($v, $perm->getName());
	}
	
	public function testDescription()
	{
		$perm = new TPermissionEvent();
		
		$perm->setDescription(null);
		self::assertEquals('', $perm->getDescription());
		
		$perm->setDescription($v = '');
		self::assertEquals($v, $perm->getDescription());
		
		$perm->setDescription($v = 'test_perm');
		self::assertEquals($v, $perm->getDescription());
	}
	
	public function testEvents()
	{
		$perm = new TPermissionEvent();
		
		$perm->setEvents(null);
		self::assertEquals([], $perm->getEvents());
		
		$perm->setEvents('dyAdd, dyUpdate, dyDelete');
		self::assertEquals(['dyadd', 'dyupdate', 'dydelete'], $perm->getEvents());
		
		$perm->setEvents(['dyAdd', 'dyUpdate', 'dyDelete']);
		self::assertEquals(['dyadd', 'dyupdate', 'dydelete'], $perm->getEvents());
		
		try {
			$perm->setEvents(false);
			self::fail("failed to throw TConfigurationException on bad event input data");
		} catch(TConfigurationException $e) {}
	}
	
	public function testRules()
	{
		$perm = new TPermissionEvent();
		
		$perm->setRules(null);
		self::assertEquals([], $perm->getRules());
		
		$rule = new TAuthorizationRule();
		
		$perm->setRules($rule);
		self::assertEquals([$rule], $perm->getRules());
		
		$perm->setRules([$rule]);
		self::assertEquals([$rule], $perm->getRules());
		
		try {
			$perm->setRules(false);
			self::fail("failed to throw TConfigurationException on bad role input data");
		} catch(TConfigurationException $e) {}
	}

}
