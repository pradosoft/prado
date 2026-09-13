<?php

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Security\TDbUser;
use Prado\Security\TDbUserManager;
use Prado\Security\TUser;
use Prado\Web\THttpCookie;

/**
 * Concrete {@see TDbUser} spy. Every seam records its invocation and returns a
 * configurable value, so delegation from {@see TDbUserManager} is observable
 * without a live database.
 */
class TTestDbUser extends TDbUser
{
	/** @var list<array> ordered record of method calls and arguments */
	public array $calls = [];
	public bool $validateReturn = true;
	public mixed $createUserReturn = null;
	public mixed $createFromCookieReturn = null;
	public int $savedToCookie = 0;

	public function validateUser($username, #[\SensitiveParameter] $password)
	{
		$this->calls[] = ['validateUser', $username, $password];
		return $this->validateReturn;
	}

	public function createUser($username)
	{
		$this->calls[] = ['createUser', $username];
		return $this->createUserReturn;
	}

	public function createUserFromCookie($cookie)
	{
		$this->calls[] = ['createUserFromCookie', $cookie];
		return $this->createFromCookieReturn;
	}

	public function saveUserToCookie($cookie)
	{
		$this->savedToCookie++;
	}
}

/**
 * A component that is not a {@see TDbUser}, used to exercise the UserClass type
 * guard. Accepts the manager argument that {@see \Prado\Prado::createComponent}
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

	/**
	 * Builds a manager initialized with the spy user class and returns both the
	 * manager and its factory instance (the private `_userFactory`).
	 *
	 * @return array{0: TDbUserManager, 1: TTestDbUser}
	 */
	private function initializedManager(): array
	{
		$mgr = new TDbUserManager();
		$mgr->setUserClass(TTestDbUser::class);
		$mgr->init(null);
		$factory = PradoUnit::getProp($mgr, '_userFactory');
		return [$mgr, $factory];
	}

	// ---- UserClass property ------------------------------------------------

	public function testUserClassDefaultsEmpty()
	{
		$mgr = new TDbUserManager();
		self::assertEquals('', $mgr->getUserClass());
	}

	public function testGetSetUserClassBeforeInit()
	{
		$mgr = new TDbUserManager();
		$mgr->setUserClass(TTestDbUser::class);
		self::assertEquals(TTestDbUser::class, $mgr->getUserClass());
	}

	// ---- init() ------------------------------------------------------------

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

	public function testInitBuildsFactoryAndMarksInitialized()
	{
		[$mgr, $factory] = $this->initializedManager();
		self::assertTrue($mgr->getIsInitialized());
		self::assertInstanceOf(TTestDbUser::class, $factory);
	}

	public function testGetUserClassReadableAfterInit()
	{
		// The initialization guard belongs on the setter; reading UserClass after
		// init() must not throw.
		[$mgr] = $this->initializedManager();
		self::assertEquals(TTestDbUser::class, $mgr->getUserClass());
	}

	public function testSetUserClassRejectedAfterInit()
	{
		[$mgr] = $this->initializedManager();
		$this->expectException(TInvalidOperationException::class);
		$mgr->setUserClass(TTestDbUser::class);
	}

	// ---- GuestName property ------------------------------------------------

	public function testGuestNameDefault()
	{
		$mgr = new TDbUserManager();
		self::assertEquals('Guest', $mgr->getGuestName());
	}

	public function testSetGuestName()
	{
		$mgr = new TDbUserManager();
		$mgr->setGuestName('Visitor');
		self::assertEquals('Visitor', $mgr->getGuestName());
	}

	// ---- validateUser() ----------------------------------------------------

	public function testValidateUserDelegatesTrue()
	{
		[$mgr, $factory] = $this->initializedManager();
		$factory->validateReturn = true;
		self::assertTrue($mgr->validateUser('joe', 'demo'));
		self::assertEquals(['validateUser', 'joe', 'demo'], $factory->calls[0]);
	}

	public function testValidateUserDelegatesFalse()
	{
		[$mgr, $factory] = $this->initializedManager();
		$factory->validateReturn = false;
		self::assertFalse($mgr->validateUser('joe', 'wrong'));
	}

	// ---- getUser() ---------------------------------------------------------

	public function testGetUserGuestWhenUsernameNull()
	{
		$mgr = new TDbUserManager();
		$mgr->setGuestName('Visitor');
		$mgr->setUserClass(TTestDbUser::class);
		$mgr->init(null);

		$user = $mgr->getUser(null);
		self::assertInstanceOf(TTestDbUser::class, $user);
		self::assertTrue($user->getIsGuest());
		self::assertEquals('Visitor', $user->getName());
	}

	public function testGetUserGuestWhenUsernameOmitted()
	{
		[$mgr] = $this->initializedManager();
		$user = $mgr->getUser();
		self::assertInstanceOf(TDbUser::class, $user);
		self::assertTrue($user->getIsGuest());
	}

	public function testGetUserDelegatesForNamedUser()
	{
		[$mgr, $factory] = $this->initializedManager();
		$marker = new TTestDbUser($mgr);
		$factory->createUserReturn = $marker;

		$user = $mgr->getUser('bob');
		self::assertSame($marker, $user);
		self::assertEquals(['createUser', 'bob'], $factory->calls[0]);
	}

	// ---- getUserFromCookie() -----------------------------------------------

	public function testGetUserFromCookieDelegates()
	{
		[$mgr, $factory] = $this->initializedManager();
		$marker = new TTestDbUser($mgr);
		$factory->createFromCookieReturn = $marker;
		$cookie = new THttpCookie('auth', 'token');

		$user = $mgr->getUserFromCookie($cookie);
		self::assertSame($marker, $user);
		self::assertEquals(['createUserFromCookie', $cookie], $factory->calls[0]);
	}

	// ---- saveUserToCookie() ------------------------------------------------

	public function testSaveUserToCookieWhenUserIsDbUser()
	{
		[$mgr] = $this->initializedManager();
		$appUser = new TTestDbUser($mgr);
		self::$app->setUser($appUser);
		$cookie = new THttpCookie('auth', '');

		$mgr->saveUserToCookie($cookie);
		self::assertEquals(1, $appUser->savedToCookie);
	}

	public function testSaveUserToCookieNoOpWhenUserNotDbUser()
	{
		[$mgr] = $this->initializedManager();
		// A plain TUser is an IUser but not a TDbUser, so the guard skips the save.
		$appUser = new TUser($mgr);
		self::$app->setUser($appUser);
		$cookie = new THttpCookie('auth', '');

		$mgr->saveUserToCookie($cookie);
		self::assertNotInstanceOf(TDbUser::class, self::$app->getUser());
	}

	// ---- protected connection message keys ---------------------------------

	public function testConnectionInvalidExceptionKey()
	{
		$mgr = new TDbUserManager();
		self::assertEquals(
			'dbusermanager_connectionid_invalid',
			PradoUnit::invoke($mgr, 'getConnectionInvalidExceptionKey')
		);
	}

	public function testConnectionRequiredExceptionKey()
	{
		$mgr = new TDbUserManager();
		self::assertEquals(
			'dbusermanager_connectionid_required',
			PradoUnit::invoke($mgr, 'getConnectionRequiredExceptionKey')
		);
	}
}
