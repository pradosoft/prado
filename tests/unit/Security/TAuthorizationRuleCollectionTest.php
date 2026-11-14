<?php

namespace Prado\Test\Unit\Security;

use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Security\TAuthorizationRule;
use Prado\Security\TAuthorizationRuleCollection;
use Prado\Security\TUser;
use Prado\Security\TUserManager;

class TAuthorizationRuleCollectionTest extends \PHPUnit\Framework\TestCase
{
	private function user(string $name): TUser
	{
		$user = new TUser(new TUserManager());
		$user->setName($name);
		$user->setIsGuest(false);
		return $user;
	}

	public function testInsertAtAcceptsOnlyRules()
	{
		$rules = new TAuthorizationRuleCollection();
		$deny = new TAuthorizationRule('deny', 'joe', '', '', '', 5);
		self::assertEquals(5, $rules->insertAt(0, $deny));
		self::assertSame($deny, $rules->itemAt(0));

		$this->expectException(TInvalidDataTypeException::class);
		$rules->insertAt(0, 'not a rule');
	}

	public function testIsUserAllowedRequiresAUser()
	{
		$rules = new TAuthorizationRuleCollection();
		self::assertFalse($rules->isUserAllowed(null, 'get', '127.0.0.1'));
	}

	public function testIsUserAllowedWithoutADecidingRuleAllows()
	{
		$rules = new TAuthorizationRuleCollection();
		self::assertTrue($rules->isUserAllowed($this->user('joe'), 'get', '127.0.0.1'));

		$rules->add(new TAuthorizationRule('deny', 'john'));
		self::assertTrue($rules->isUserAllowed($this->user('joe'), ' GET ', '127.0.0.1'));
		self::assertFalse($rules->isUserAllowed($this->user('john'), 'get', '127.0.0.1'));
	}
}
