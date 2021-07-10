<?php

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Security\Permissions\TUserOwnerRule;
use Prado\Security\TUserManager;

class TUserOwnerRuleTest extends PHPUnit\Framework\TestCase
{
	protected $obj;
	
	protected function setUp(): void
	{
		$this->obj = new TUserOwnerRule();
	}

	protected function tearDown(): void
	{
		$this->obj = null;
	}

	public function testConstruct()
	{
		self::assertInstanceOf('Prado\\Security\\Permissions\\TUserOwnerRule', $this->obj);
	}

	public function testIsUserAllowed()
	{
		$userManager = new TUserManager();
		$user = new TUser($userManager);
		$user->setName('admin1');
		
		self::assertEquals(0, $this->obj->isUserAllowed($user, 'get', '192.168.0.10', null));
		self::assertEquals(1, $this->obj->isUserAllowed($user, 'get', '192.168.0.10', ['username' => 'admin1']));
		
		$this->obj->setAction('deny');
		self::assertEquals(-1, $this->obj->isUserAllowed($user, 'get', '192.168.0.10', ['username' => 'Admin1']));
		
		$this->obj->setAction('allow');
		$this->obj->setRoles('Developer');
		self::assertEquals(0, $this->obj->isUserAllowed($user, 'get', '192.168.0.10', ['username' => 'admin1']));
		
		$user->setRoles('Developer');
		self::assertEquals(1, $this->obj->isUserAllowed($user, 'get', '192.168.0.10', ['username' => 'admin1']));
		self::assertEquals(0, $this->obj->isUserAllowed($user, 'get', '192.168.0.10', ['username' => 'admin2']));
	}
	

}
