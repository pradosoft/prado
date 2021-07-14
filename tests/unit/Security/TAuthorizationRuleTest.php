<?php

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Security\TAuthorizationRule;

class TAuthorizationRuleTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
	}

	protected function tearDown(): void
	{
	}

	public function testConstruct()
	{
		$rule = new TAuthorizationRule();
		self::assertEquals('allow', $rule->getAction());
	}
	
	public function testAction()
	{
		$rule = new TAuthorizationRule(' ALloW', '*', '*');
		self::assertEquals('allow', $rule->getAction());
		
		$rule = new TAuthorizationRule('deny', '*', '*');
		self::assertEquals('deny', $rule->getAction());
		
		try {
			$rule = new TAuthorizationRule('test', '*', '*');
			self::fail('TInvalidDataValueException not thrown when action is invalid');
		} catch(TInvalidDataValueException $e) {}
	}

	public function testUsers()
	{ // and getGuestApplied, getEveryoneApplied, and getAuthenticatedApplied
		$rule = new TAuthorizationRule('allow', '', '*');
		self::assertEquals([], $rule->getUsers());
		self::assertTrue($rule->getGuestApplied());
		self::assertTrue($rule->getEveryoneApplied());
		self::assertTrue($rule->getAuthenticatedApplied());
		
		$rule = new TAuthorizationRule('allow', '*', '*');
		self::assertEquals([], $rule->getUsers());
		self::assertTrue($rule->getGuestApplied());
		self::assertTrue($rule->getEveryoneApplied());
		self::assertTrue($rule->getAuthenticatedApplied());
		
		$rule = new TAuthorizationRule('allow', '?', '*');
		self::assertEquals([], $rule->getUsers());
		self::assertTrue($rule->getGuestApplied());
		self::assertFalse($rule->getEveryoneApplied());
		self::assertFalse($rule->getAuthenticatedApplied());
		
		$rule = new TAuthorizationRule('allow', '@', '*');
		self::assertEquals([], $rule->getUsers());
		self::assertFalse($rule->getGuestApplied());
		self::assertFalse($rule->getEveryoneApplied());
		self::assertTrue($rule->getAuthenticatedApplied());
		
		$rule = new TAuthorizationRule('allow', '?, @', '*');
		self::assertEquals([], $rule->getUsers());
		self::assertTrue($rule->getGuestApplied());
		self::assertFalse($rule->getEveryoneApplied());
		self::assertTrue($rule->getAuthenticatedApplied());
		
		$rule = new TAuthorizationRule('allow', 'root, admin, user', '*');
		self::assertEquals(['root', 'admin', 'user'], $rule->getUsers());
		self::assertFalse($rule->getGuestApplied());
		self::assertFalse($rule->getEveryoneApplied());
		self::assertFalse($rule->getAuthenticatedApplied());
		
		$rule = new TAuthorizationRule('allow', '?, @, root, admin, user', '*');
		self::assertEquals(['root', 'admin', 'user'], $rule->getUsers());
		self::assertTrue($rule->getGuestApplied());
		self::assertFalse($rule->getEveryoneApplied());
		self::assertTrue($rule->getAuthenticatedApplied());
	}

	public function testRoles()
	{
		$rule = new TAuthorizationRule('allow', '', '');
		self::assertEquals(['*'], $rule->getRoles());
		
		$rule = new TAuthorizationRule('allow', '', '*');
		self::assertEquals(['*'], $rule->getRoles());
		
		$rule = new TAuthorizationRule('allow', '', 'admin, writer, contributor');
		self::assertEquals(['admin', 'writer', 'contributor'], $rule->getRoles());
	}

	public function testVerb()
	{
		$rule = new TAuthorizationRule('allow', '', '', '');
		self::assertEquals('*', $rule->getVerb());
		
		$rule = new TAuthorizationRule('allow', '', '', '*');
		self::assertEquals('*', $rule->getVerb());
		
		$rule = new TAuthorizationRule('allow', '', '', 'get');
		self::assertEquals('get', $rule->getVerb());
		
		$rule = new TAuthorizationRule('allow', '', '', 'post');
		self::assertEquals('post', $rule->getVerb());
		
		try {
			$rule = new TAuthorizationRule('test', '*', '*', 'test');
			self::fail('TInvalidDataValueException not thrown when action is invalid');
		} catch(TInvalidDataValueException $e) {}
	}

	public function testIPRules()
	{
		$rule = new TAuthorizationRule('allow', '', '', '', '');
		self::assertEquals(['*'], $rule->getIPRules());
		
		$rule = new TAuthorizationRule('allow', '', '', '', '*');
		self::assertEquals(['*'], $rule->getIPRules());
		
		$rule = new TAuthorizationRule('allow', '', '', '', '192.168.*.*, 10.0.0.*');
		self::assertEquals(['192.168.*.*','10.0.0.*'], $rule->getIPRules());
	}

	public function testIsUserAllowed()
	{
		$user = new TUser(TAuthManagerTest::$usrMgr);
		
		$rule = new TAuthorizationRule('allow', '', '', '', '');
		self::assertEquals(1, $rule->isUserAllowed($user, 'get', '192.168.0.10'));
		
		$rule = new TAuthorizationRule('deny', '', '', '', '');
		self::assertEquals(-1, $rule->isUserAllowed($user, 'get', '192.168.0.10'));
		
		$rule = new TAuthorizationRule('deny', '', '', '', '192.168.0.9');
		self::assertEquals(0, $rule->isUserAllowed($user, 'get', '192.168.0.10'));
	}

	public function testIsIpMatched()
	{
		$user = new TUser(TAuthManagerTest::$usrMgr);
		
		$rule = new TAuthorizationRule('allow', '', '', '', '');
		self::assertEquals(1, $rule->isUserAllowed($user, 'get', '192.168.0.10'));
		
		$rule = new TAuthorizationRule('allow', '', '', '', '192.168.0.10');
		self::assertEquals(1, $rule->isUserAllowed($user, 'get', '192.168.0.10'));
		self::assertEquals(0, $rule->isUserAllowed($user, 'get', '192.168.0.9'));
		
		$rule = new TAuthorizationRule('allow', '', '', '', '192.168.*.*');
		self::assertEquals(1, $rule->isUserAllowed($user, 'get', '192.168.0.10'));
		self::assertEquals(1, $rule->isUserAllowed($user, 'get', '192.168.10.254'));
		self::assertEquals(0, $rule->isUserAllowed($user, 'get', '192.167.0.10'));
		self::assertEquals(0, $rule->isUserAllowed($user, 'get', '192.167.10.254'));
		self::assertEquals(0, $rule->isUserAllowed($user, 'get', '10.0.0.1'));
	}

	public function testIsUserMatched()
	{
		$user = new TUser(TAuthManagerTest::$usrMgr);
		
		$allrule = new TAuthorizationRule('allow', '*', '');
		$guestrule = new TAuthorizationRule('allow', '?', '');
		$authrule = new TAuthorizationRule('allow', '@', '');
		$userrule = new TAuthorizationRule('allow', 'admin', '');
		
		self::assertEquals(1, $allrule->isUserAllowed($user, 'get', '192.168.0.10'));
		self::assertEquals(1, $guestrule->isUserAllowed($user, 'get', '192.168.0.10'));
		self::assertEquals(0, $authrule->isUserAllowed($user, 'get', '192.168.0.10'));
		self::assertEquals(0, $userrule->isUserAllowed($user, 'get', '192.168.0.10'));
		
		$user->setIsGuest(false);
		
		self::assertEquals(1, $allrule->isUserAllowed($user, 'get', '192.168.0.10'));
		self::assertEquals(0, $guestrule->isUserAllowed($user, 'get', '192.168.0.10'));
		self::assertEquals(1, $authrule->isUserAllowed($user, 'get', '192.168.0.10'));
		self::assertEquals(0, $userrule->isUserAllowed($user, 'get', '192.168.0.10'));
		
		$user->setName('admin2');
		
		self::assertEquals(1, $allrule->isUserAllowed($user, 'get', '192.168.0.10'));
		self::assertEquals(0, $guestrule->isUserAllowed($user, 'get', '192.168.0.10'));
		self::assertEquals(1, $authrule->isUserAllowed($user, 'get', '192.168.0.10'));
		self::assertEquals(0, $userrule->isUserAllowed($user, 'get', '192.168.0.10'));
		
		$user->setName('admin');
		
		self::assertEquals(1, $allrule->isUserAllowed($user, 'get', '192.168.0.10'));
		self::assertEquals(0, $guestrule->isUserAllowed($user, 'get', '192.168.0.10'));
		self::assertEquals(1, $authrule->isUserAllowed($user, 'get', '192.168.0.10'));
		self::assertEquals(1, $userrule->isUserAllowed($user, 'get', '192.168.0.10'));
	}

	public function testIsRoleMatched()
	{
		$user = new TUser(TAuthManagerTest::$usrMgr);
		
		$rule = new TAuthorizationRule('allow', '', '*');
		$rule2 = new TAuthorizationRule('allow', '', 'admin, writer, contributor');
		
		$user->setRoles('');
		
		self::assertEquals(1, $rule->isUserAllowed($user, 'get', '192.168.0.10'));
		self::assertEquals(0, $rule2->isUserAllowed($user, 'get', '192.168.0.10'));
		
		$user->setRoles('writer');
		
		self::assertEquals(1, $rule->isUserAllowed($user, 'get', '192.168.0.10'));
		self::assertEquals(1, $rule2->isUserAllowed($user, 'get', '192.168.0.10'));
		
		$user->setRoles('manager');
		
		self::assertEquals(1, $rule->isUserAllowed($user, 'get', '192.168.0.10'));
		self::assertEquals(0, $rule2->isUserAllowed($user, 'get', '192.168.0.10'));
	}

	public function testIsVerbMatched()
	{
		$user = new TUser(TAuthManagerTest::$usrMgr);
		$rule = new TAuthorizationRule('allow', '', '', '*');
		self::assertEquals(1, $rule->isUserAllowed($user, 'post', '192.168.0.10'));
		self::assertEquals(1, $rule->isUserAllowed($user, 'get', '192.168.0.10'));
		
		$rule = new TAuthorizationRule('allow', '', '', 'get');
		self::assertEquals(0, $rule->isUserAllowed($user, 'post', '192.168.0.10'));
		self::assertEquals(1, $rule->isUserAllowed($user, 'get', '192.168.0.10'));
		
		$rule = new TAuthorizationRule('allow', '', '', 'post');
		self::assertEquals(0, $rule->isUserAllowed($user, 'get', '192.168.0.10'));
		self::assertEquals(1, $rule->isUserAllowed($user, 'post', '192.168.0.10'));
	}

	public function testZappableSleepProps()
	{
		$rule = new TAuthorizationRule();
		$_rule = unserialize(serialize($rule));
		
		self::assertEquals($_rule->getAction(), $rule->getAction());
		self::assertEquals($_rule->getUsers(), $rule->getUsers());
		self::assertEquals($_rule->getRoles(), $rule->getRoles());
		self::assertEquals($_rule->getVerb(), $rule->getVerb());
		self::assertEquals($_rule->getIpRules(), $rule->getIpRules());
		self::assertEquals($_rule->getPriority(), $rule->getPriority());
		
		$rule = new TAuthorizationRule('deny', 'user1, user2', 'default, subscriber', 'get', '192.168.*', 2);
		$_rule = unserialize(serialize($rule));
		
		self::assertEquals($_rule->getAction(), $rule->getAction());
		self::assertEquals($_rule->getUsers(), $rule->getUsers());
		self::assertEquals($_rule->getRoles(), $rule->getRoles());
		self::assertEquals($_rule->getVerb(), $rule->getVerb());
		self::assertEquals($_rule->getIpRules(), $rule->getIpRules());
		self::assertEquals($_rule->getPriority(), $rule->getPriority());
	}
}
