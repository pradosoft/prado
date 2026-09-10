<?php

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Security\TDbUser;
use Prado\Security\TDbUserManager;

/**
 * Concrete {@see TDbUser} used to satisfy the UserClass type guard without a
 * live database. The abstract seams return fixed values.
 */
class TTestDbUser extends TDbUser
{
	public function validateUser($username, #[\SensitiveParameter] $password)
	{
		return true;
	}

	public function createUser($username)
	{
		return null;
	}
}

/**
 * A component that is not a {@see TDbUser}, used to exercise the UserClass
 * type guard. Accepts the manager argument that {@see \Prado\Prado::createComponent}
 * forwards.
 */
class TTestNotADbUser extends \Prado\TComponent
{
	public function __construct($manager = null)
	{
		parent::__construct();
	}
}

class TDbUserManagerTest extends PHPUnit\Framework\TestCase
{
	public static TTestApplication $app;

	public static function setUpBeforeClass(): void
	{
		self::$app = new TTestApplication();
	}

	public static function tearDownAfterClass(): void
	{
		self::$app->restoreApplication();
	}

	public function testGetSetUserClassBeforeInit()
	{
		$mgr = new TDbUserManager();
		self::assertEquals('', $mgr->getUserClass());

		$mgr->setUserClass(TTestDbUser::class);
		self::assertEquals(TTestDbUser::class, $mgr->getUserClass());
	}

	public function testInitRequiresUserClass()
	{
		$mgr = new TDbUserManager();
		$this->expectException(TConfigurationException::class);
		$mgr->init(null);
	}

	public function testInitRejectsInvalidUserClass()
	{
		$mgr = new TDbUserManager();
		$mgr->setUserClass(TTestNotADbUser::class);
		$this->expectException(TInvalidDataTypeException::class);
		$mgr->init(null);
	}

	public function testInit()
	{
		$mgr = new TDbUserManager();
		$mgr->setUserClass(TTestDbUser::class);
		$mgr->init(null);
		self::assertTrue($mgr->getIsInitialized());

		// UserClass stays readable after initialization; the guard belongs on
		// the setter, not the getter.
		self::assertEquals(TTestDbUser::class, $mgr->getUserClass());
	}

	public function testSetUserClassRejectedAfterInit()
	{
		$mgr = new TDbUserManager();
		$mgr->setUserClass(TTestDbUser::class);
		$mgr->init(null);

		$this->expectException(TInvalidOperationException::class);
		$mgr->setUserClass(TTestDbUser::class);
	}
}
