<?php

/**
 * TDbUserManagerTest
 *
 * Comprehensive unit tests for {@see \Prado\Security\TDbUserManager}.
 *
 * Design notes
 * ------------
 * • TDbUserManagerTestable subclasses TDbUserManager and overrides
 *   getApplication() to return a MockApplication, eliminating the need
 *   for a live PRADO service registry.
 * • Reflection helpers on TDbUserManagerTestable let tests inject
 *   _userFactory and toggle _initialized without going through init().
 * • A concrete TDbUserTestStub (extends TDbUser) is used for the small
 *   set of tests that must exercise the real Prado::createComponent path
 *   (init success, getUser guest path).
 * • All database-layer classes (TDataSourceConfig, TDbConnection) are
 *   mocked; no connection is ever established.
 *
 * Changelog vs previous version
 * ------------------------------
 * • Reflection references updated: $_conn → $_dbConnection,
 *   $_connID → $_connectionID (field renames in TDbUserManager v4).
 * • Two new tests added (section 15) that assert the corrected error
 *   message keys ('dbusermanager_connectionid_*') are used by
 *   createDbConnection(), catching any future regression of the typo
 *   'dbConnectionectionid' that appeared in the submitted v4 source.
 *
 * @requires PHPUnit >= 10.0
 */

namespace Prado\Tests\Security;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Prado\Security\TDbUserManager;
use Prado\Security\TDbUser;
use Prado\Security\TUser;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Data\TDataSourceConfig;
use Prado\Data\TDbConnection;
use Prado\Web\THttpCookie;

// ====================================================================
// Concrete TDbUser stub
// Extends TDbUser so that the instanceof check in init() passes.
// All abstract methods return safe, do-nothing defaults.
// Constructor signature mirrors TDbUser(TUserManager $manager).
// ====================================================================

class TDbUserTestStub extends TDbUser
{
	/** @inheritdoc */
	public function createUser($username): ?TDbUser
	{
		return null;
	}

	/** @inheritdoc */
	public function validateUser($username, $password): bool
	{
		return false;
	}

	/** @inheritdoc */
	public function createUserFromCookie($cookie): ?TDbUser
	{
		return null;
	}

	/** @inheritdoc */
	public function saveUserToCookie($cookie): void
	{
	}

	/** @inheritdoc */
	public function getUniqueRoles(): ?array
	{
		return null;
	}

	/** @inheritdoc */
	public function getUniqueRoleCount(): ?int
	{
		return null;
	}
}

// ====================================================================
// A class that is NOT a TDbUser — used to trigger the
// TInvalidDataTypeException path inside init().
// ====================================================================

class NotATDbUser
{
	/** Must accept the same constructor arg that Prado::createComponent passes. */
	public function __construct($manager) {}
}

// ====================================================================
// Minimal application stub
// ====================================================================

/**
 * Provides the surface of TApplication that TDbUserManager actually uses:
 *   getModule(id), getParameters(), getUser().
 */
class MockApplication
{
	private array              $modules    = [];
	private MockParameterList  $parameters;
	private mixed              $currentUser = null;

	public function __construct()
	{
		$this->parameters = new MockParameterList();
	}

	public function getModule(string $id): mixed
	{
		return $this->modules[$id] ?? null;
	}

	public function registerModule(string $id, object $module): void
	{
		$this->modules[$id] = $module;
	}

	public function getParameters(): MockParameterList
	{
		return $this->parameters;
	}

	public function getUser(): mixed
	{
		return $this->currentUser;
	}

	public function setCurrentUser(mixed $user): void
	{
		$this->currentUser = $user;
	}
}

/**
 * Minimal parameter list stub implementing the three methods TDbUserManager calls.
 */
class MockParameterList
{
	private array $params = [];

	public function set(string $key, mixed $value): void
	{
		$this->params[$key] = $value;
	}

	public function contains(string $key): bool
	{
		return array_key_exists($key, $this->params);
	}

	public function itemAt(string $key): mixed
	{
		return $this->params[$key] ?? null;
	}
}

// ====================================================================
// Testable subclass
// ====================================================================

/**
 * TDbUserManagerTestable
 *
 * • Overrides getApplication() → MockApplication (avoids PRADO service registry).
 * • Exposes protected methods via public wrappers.
 * • Provides reflection helpers for injecting private state.
 *
 * Reflection field names kept in sync with TDbUserManager private fields:
 *   _userFactory, _initialized, _dbConnection, _connectionID
 */
class TDbUserManagerTestable extends TDbUserManager
{
	public MockApplication $mockApp;

	public function __construct()
	{
		$this->mockApp = new MockApplication();
	}

	// ------------------------------------------------------------------
	// Override the application getter
	// ------------------------------------------------------------------

	public function getApplication(): MockApplication
	{
		return $this->mockApp;
	}

	// ------------------------------------------------------------------
	// Reflection helpers
	// ------------------------------------------------------------------

	/**
	 * Inject a factory directly into _userFactory, bypassing init().
	 * Lets individual tests set precise mock expectations without running
	 * the real init() code path.
	 */
	public function injectFactory(TDbUser $factory): void
	{
		$prop = new \ReflectionProperty(TDbUserManager::class, '_userFactory');
		$prop->setAccessible(true);
		$prop->setValue($this, $factory);
	}

	/**
	 * Mark the module as initialized so post-init guards activate and
	 * property locks engage.
	 */
	public function markInitialized(): void
	{
		$this->setInitializedFlag(true);
	}

	/**
	 * Reset the module to its pre-init state so setters that are locked
	 * post-init can be called again.
	 */
	public function markUninitialized(): void
	{
		$this->setInitializedFlag(false);
	}

	private function setInitializedFlag(bool $value): void
	{
		$prop = new \ReflectionProperty(TDbUserManager::class, '_initialized');
		$prop->setAccessible(true);
		$prop->setValue($this, $value);
	}

	/**
	 * Inject a pre-built TDbConnection mock directly into _dbConnection,
	 * bypassing getDbConnection()'s lazy-init path.
	 * Used by tests that need to verify caching behaviour at the field level.
	 */
	public function injectDbConnection(TDbConnection $conn): void
	{
		$prop = new \ReflectionProperty(TDbUserManager::class, '_dbConnection');
		$prop->setAccessible(true);
		$prop->setValue($this, $conn);
	}

	/**
	 * Read back the cached _dbConnection field so caching tests can assert
	 * the field was actually populated without going through the public getter.
	 */
	public function readDbConnectionField(): mixed
	{
		$prop = new \ReflectionProperty(TDbUserManager::class, '_dbConnection');
		$prop->setAccessible(true);
		return $prop->getValue($this);
	}

	// ------------------------------------------------------------------
	// Protected method exposure
	// ------------------------------------------------------------------

	public function exposedGetUniqueRolesFromAppParameter(): ?array
	{
		return $this->getUniqueRolesFromAppParameter();
	}

	public function exposedCreateDbConnection(string $id): TDbConnection
	{
		return $this->createDbConnection($id);
	}
}

// ====================================================================
// Test case
// ====================================================================

class TDbUserManagerTest extends TestCase
{
	// The fully-initialized manager used by most tests.
	private TDbUserManagerTestable $manager;

	// Default factory mock wired into $this->manager.
	private TDbUser|MockObject $factoryMock;

	// ------------------------------------------------------------------
	// setUp / helpers
	// ------------------------------------------------------------------

	protected function setUp(): void
	{
		$this->manager     = new TDbUserManagerTestable();
		$this->factoryMock = $this->makeFactoryMock();

		// Most tests need an initialized manager with a controllable factory.
		$this->manager->setUserClass(TDbUserTestStub::class);
		$this->manager->injectFactory($this->factoryMock);
		$this->manager->markInitialized();
	}

	/**
	 * Build a PHPUnit mock for TDbUser with the full method surface used by
	 * TDbUserManager.  Additional methods can be passed if a test needs them.
	 *
	 * @return TDbUser&MockObject
	 */
	private function makeFactoryMock(array $extra = []): TDbUser|MockObject
	{
		$methods = array_unique(array_merge(
			[
				'validateUser',
				'createUser',
				'createUserFromCookie',
				'getUniqueRoles',
				'getUniqueRoleCount',
				'saveUserToCookie',
			],
			$extra
		));

		return $this->getMockBuilder(TDbUser::class)
					->disableOriginalConstructor()
					->onlyMethods($methods)
					->getMock();
	}

	/**
	 * Return a fresh, uninitialized testable manager with no factory injected.
	 */
	private function freshManager(): TDbUserManagerTestable
	{
		return new TDbUserManagerTestable();
	}

	/**
	 * Attach an onFinalizeUser handler that captures its arguments.
	 * Returns a stdClass; check ->called, ->sender, ->user.
	 *
	 * stdClass is used instead of an array because PHP closures receive
	 * objects by handle, so modifications inside the closure are visible
	 * through the returned reference without needing a by-ref capture.
	 * An array would be returned by value, giving the caller a stale copy
	 * that never reflects changes made when the event fires.
	 */
	private function attachFinalizeCapture(TDbUserManagerTestable $mgr): \stdClass
	{
		$captured         = new \stdClass();
		$captured->sender = null;
		$captured->user   = null;
		$captured->called = false;

		$mgr->onFinalizeUser[] = function ($sender, $user) use ($captured): void {
			$captured->sender = $sender;
			$captured->user   = $user;
			$captured->called = true;
		};
		return $captured;
	}

	// ==================================================================
	// 1. Default property values
	// ==================================================================

	public function testGetUserClassDefaultIsEmptyString(): void
	{
		$this->assertSame('', $this->freshManager()->getUserClass());
	}

	public function testGetGuestNameDefaultIsGuest(): void
	{
		$this->assertSame('Guest', $this->freshManager()->getGuestName());
	}

	public function testGetRolesAppParameterIdDefaultIsNull(): void
	{
		$this->assertNull($this->freshManager()->getRolesAppParameterId());
	}

	public function testGetConnectionIDDefaultIsEmptyString(): void
	{
		$this->assertSame('', $this->freshManager()->getConnectionID());
	}

	// ==================================================================
	// 2. Property setters / getters (pre-init)
	// ==================================================================

	public function testSetGetUserClass(): void
	{
		$m = $this->freshManager();
		$m->setUserClass('My\\App\\User');
		$this->assertSame('My\\App\\User', $m->getUserClass());
	}

	public function testSetGetGuestName(): void
	{
		$this->manager->setGuestName('Anonymous');
		$this->assertSame('Anonymous', $this->manager->getGuestName());
	}

	public function testSetGetConnectionID(): void
	{
		$m = $this->freshManager();
		$m->setConnectionID('mydb');
		$this->assertSame('mydb', $m->getConnectionID());
	}

	public function testSetGetRolesAppParameterIdPreInit(): void
	{
		$m = $this->freshManager();
		$m->setRolesAppParameterId('app.roles');
		$this->assertSame('app.roles', $m->getRolesAppParameterId());
	}

	public function testSetRolesAppParameterIdToNullPreInit(): void
	{
		$m = $this->freshManager();
		$m->setRolesAppParameterId('something');
		$m->setRolesAppParameterId(null);
		$this->assertNull($m->getRolesAppParameterId());
	}

	// ==================================================================
	// 3. setUniqueRoles() — pre-init variations
	// ==================================================================

	/**
	 * Helper: a fresh manager whose factory returns null for roles, so
	 * module-level _uniqueRoles is the only source in getUniqueRoles().
	 */
	private function freshManagerWithNullFactory(): TDbUserManagerTestable
	{
		$m       = $this->freshManager();
		$factory = $this->makeFactoryMock();
		$factory->method('getUniqueRoles')->willReturn(null);
		$factory->method('getUniqueRoleCount')->willReturn(null);
		$m->injectFactory($factory);
		$m->markInitialized();
		return $m;
	}

	public function testSetUniqueRolesWithArrayStoresCorrectValues(): void
	{
		$m = $this->freshManager();
		$m->setUniqueRoles(['admin', 'editor']);
		$factory = $this->makeFactoryMock();
		$factory->method('getUniqueRoles')->willReturn(null);
		$m->injectFactory($factory);
		$m->markInitialized();
		$this->assertSame(['admin', 'editor'], $m->getUniqueRoles());
	}

	public function testSetUniqueRolesWithCommaStringParsesAllEntries(): void
	{
		$m = $this->freshManager();
		$m->setUniqueRoles('admin,editor,viewer');
		$factory = $this->makeFactoryMock();
		$factory->method('getUniqueRoles')->willReturn(null);
		$m->injectFactory($factory);
		$m->markInitialized();

		$roles = array_values($m->getUniqueRoles());
		$this->assertCount(3, $roles);
		$this->assertContains('admin',  $roles);
		$this->assertContains('editor', $roles);
		$this->assertContains('viewer', $roles);
	}

	public function testSetUniqueRolesStringTrimsWhitespaceAroundEntries(): void
	{
		$m = $this->freshManager();
		$m->setUniqueRoles('  admin , editor , viewer  ');
		$factory = $this->makeFactoryMock();
		$factory->method('getUniqueRoles')->willReturn(null);
		$m->injectFactory($factory);
		$m->markInitialized();

		$roles = array_values($m->getUniqueRoles());
		$this->assertSame('admin',  $roles[0]);
		$this->assertSame('editor', $roles[1]);
		$this->assertSame('viewer', $roles[2]);
	}

	public function testSetUniqueRolesStringFiltersEmptySegments(): void
	{
		$m = $this->freshManager();
		$m->setUniqueRoles('admin,,editor,,');
		$factory = $this->makeFactoryMock();
		$factory->method('getUniqueRoles')->willReturn(null);
		$m->injectFactory($factory);
		$m->markInitialized();

		$this->assertCount(2, $m->getUniqueRoles());
	}

	public function testSetUniqueRolesWithEmptyStringYieldsEmptyArray(): void
	{
		$m = $this->freshManager();
		$m->setUniqueRoles('');
		$factory = $this->makeFactoryMock();
		$factory->method('getUniqueRoles')->willReturn(null);
		$m->injectFactory($factory);
		$m->markInitialized();

		$this->assertEmpty($m->getUniqueRoles());
	}

	public function testSetUniqueRolesWithIntegerThrowsTInvalidDataValueException(): void
	{
		$this->expectException(TInvalidDataValueException::class);
		$this->freshManager()->setUniqueRoles(42);
	}

	public function testSetUniqueRolesWithObjectThrowsTInvalidDataValueException(): void
	{
		$this->expectException(TInvalidDataValueException::class);
		$this->freshManager()->setUniqueRoles(new \stdClass());
	}

	public function testSetUniqueRolesWithBooleanThrowsTInvalidDataValueException(): void
	{
		$this->expectException(TInvalidDataValueException::class);
		$this->freshManager()->setUniqueRoles(true);
	}

	// ==================================================================
	// 4. Post-init property locks
	// ==================================================================

	public function testSetUniqueRolesAfterInitThrowsTInvalidOperationException(): void
	{
		$this->expectException(TInvalidOperationException::class);
		$this->manager->setUniqueRoles(['admin']);
	}

	public function testSetRolesAppParameterIdAfterInitThrowsTInvalidOperationException(): void
	{
		$this->expectException(TInvalidOperationException::class);
		$this->manager->setRolesAppParameterId('locked.param');
	}

	// ==================================================================
	// 5. init() — exception paths and success
	//    These exercise the real Prado::createComponent code path.
	// ==================================================================

	public function testInitThrowsTConfigurationExceptionWhenUserClassIsEmpty(): void
	{
		$m = $this->freshManager();
		// userClass defaults to '' — init must throw before createComponent
		$this->expectException(TConfigurationException::class);
		$m->init(null);
	}

	public function testInitThrowsTInvalidDataTypeExceptionWhenClassIsNotTDbUser(): void
	{
		$m = $this->freshManager();
		$m->setUserClass(NotATDbUser::class);
		$this->expectException(TInvalidDataTypeException::class);
		$m->init(null);
	}

	public function testInitSucceedsWithValidTDbUserSubclass(): void
	{
		$m = $this->freshManager();
		$m->setUserClass(TDbUserTestStub::class);
		$m->init(null);
		// Post-init lock is the observable proxy for _initialized = true.
		$this->expectException(TInvalidOperationException::class);
		$m->setUniqueRoles(['any']);
	}

	public function testInitLocksRolesAppParameterIdAfterwards(): void
	{
		$m = $this->freshManager();
		$m->setUserClass(TDbUserTestStub::class);
		$m->init(null);
		$this->expectException(TInvalidOperationException::class);
		$m->setRolesAppParameterId('locked');
	}

	public function testInitLocksUniqueRolesAfterwards(): void
	{
		$m = $this->freshManager();
		$m->setUserClass(TDbUserTestStub::class);
		$m->init(null);
		$this->expectException(TInvalidOperationException::class);
		$m->setUniqueRoles(['blocked']);
	}

	public function testUserClassIsPreservedAfterInit(): void
	{
		$m = $this->freshManager();
		$m->setUserClass(TDbUserTestStub::class);
		$m->init(null);
		$this->assertSame(TDbUserTestStub::class, $m->getUserClass());
	}

	// ==================================================================
	// 6. validateUser()
	// ==================================================================

	public function testValidateUserReturnsTrueWhenFactoryReturnsTrue(): void
	{
		$this->factoryMock->method('validateUser')->willReturn(true);
		$this->assertTrue($this->manager->validateUser('alice', 'correct'));
	}

	public function testValidateUserReturnsFalseWhenFactoryReturnsFalse(): void
	{
		$this->factoryMock->method('validateUser')->willReturn(false);
		$this->assertFalse($this->manager->validateUser('alice', 'wrong'));
	}

	public function testValidateUserForwardsUsernameAndPasswordToFactory(): void
	{
		$this->factoryMock
			->expects($this->once())
			->method('validateUser')
			->with($this->identicalTo('bob'), $this->identicalTo('s3cr3t'))
			->willReturn(true);

		$this->manager->validateUser('bob', 's3cr3t');
	}

	public function testValidateUserIsCalledExactlyOnce(): void
	{
		$this->factoryMock
			->expects($this->once())
			->method('validateUser')
			->willReturn(false);

		$this->manager->validateUser('x', 'y');
	}

	// ==================================================================
	// 7. getUser() — non-null username (found / not-found)
	// ==================================================================

	public function testGetUserWithUsernameCallsCreateUserOnFactory(): void
	{
		$userMock = $this->createMock(TDbUser::class);
		$this->factoryMock
			->expects($this->once())
			->method('createUser')
			->with('alice')
			->willReturn($userMock);

		$this->manager->getUser('alice');
	}

	public function testGetUserWithUsernameReturnsUserFromFactory(): void
	{
		$userMock = $this->createMock(TDbUser::class);
		$this->factoryMock->method('createUser')->willReturn($userMock);

		$this->assertSame($userMock, $this->manager->getUser('alice'));
	}

	public function testGetUserFoundCallsOnFinalizeUserWithTheUser(): void
	{
		$userMock = $this->createMock(TDbUser::class);
		$this->factoryMock->method('createUser')->willReturn($userMock);

		$captured = $this->attachFinalizeCapture($this->manager);
		$this->manager->getUser('alice');

		$this->assertTrue($captured->called);
		$this->assertSame($userMock, $captured->user);
	}

	public function testGetUserFoundPassesManagerAsSenderToOnFinalizeUser(): void
	{
		$userMock = $this->createMock(TDbUser::class);
		$this->factoryMock->method('createUser')->willReturn($userMock);

		$captured = $this->attachFinalizeCapture($this->manager);
		$this->manager->getUser('alice');

		$this->assertSame($this->manager, $captured->sender);
	}

	public function testGetUserNotFoundReturnsNull(): void
	{
		$this->factoryMock->method('createUser')->willReturn(null);
		$this->assertNull($this->manager->getUser('nobody'));
	}

	public function testGetUserNotFoundDoesNotCallOnFinalizeUser(): void
	{
		$this->factoryMock->method('createUser')->willReturn(null);

		$captured = $this->attachFinalizeCapture($this->manager);
		$this->manager->getUser('nobody');

		$this->assertFalse($captured->called,
			'onFinalizeUser must NOT fire when createUser returns null');
	}

	// ==================================================================
	// 8. getUser() — null username (guest path)
	//    These tests go through the real Prado::createComponent path.
	// ==================================================================

	public function testGetUserGuestReturnsNonNullUser(): void
	{
		$m = $this->freshManager();
		$m->setUserClass(TDbUserTestStub::class);
		$m->init(null);

		$this->assertNotNull($m->getUser(null));
	}

	public function testGetUserGuestSetsIsGuestToTrue(): void
	{
		$m = $this->freshManager();
		$m->setUserClass(TDbUserTestStub::class);
		$m->init(null);

		$this->assertTrue($m->getUser(null)->getIsGuest());
	}

	public function testGetUserGuestIsInstanceOfExpectedUserClass(): void
	{
		$m = $this->freshManager();
		$m->setUserClass(TDbUserTestStub::class);
		$m->init(null);

		$this->assertInstanceOf(TDbUserTestStub::class, $m->getUser(null));
	}

	public function testGetUserGuestCallsOnFinalizeUser(): void
	{
		$m = $this->freshManager();
		$m->setUserClass(TDbUserTestStub::class);
		$m->init(null);

		$captured = $this->attachFinalizeCapture($m);
		$guest    = $m->getUser(null);

		$this->assertTrue($captured->called);
		$this->assertSame($guest, $captured->user);
	}

	// ==================================================================
	// 9. getUserFromCookie()
	// ==================================================================

	public function testGetUserFromCookieCallsCreateUserFromCookieWithCookie(): void
	{
		$cookie = $this->createMock(THttpCookie::class);
		$this->factoryMock
			->expects($this->once())
			->method('createUserFromCookie')
			->with($this->identicalTo($cookie))
			->willReturn(null);

		$this->manager->getUserFromCookie($cookie);
	}

	public function testGetUserFromCookieValidCookieReturnsUser(): void
	{
		$cookie   = $this->createMock(THttpCookie::class);
		$userMock = $this->createMock(TDbUser::class);
		$this->factoryMock->method('createUserFromCookie')->willReturn($userMock);

		$this->assertSame($userMock, $this->manager->getUserFromCookie($cookie));
	}

	public function testGetUserFromCookieValidCookieCallsOnFinalizeUser(): void
	{
		$cookie   = $this->createMock(THttpCookie::class);
		$userMock = $this->createMock(TDbUser::class);
		$this->factoryMock->method('createUserFromCookie')->willReturn($userMock);

		$captured = $this->attachFinalizeCapture($this->manager);
		$this->manager->getUserFromCookie($cookie);

		$this->assertTrue($captured->called);
		$this->assertSame($userMock, $captured->user);
	}

	public function testGetUserFromCookieValidCookiePassesManagerAsSender(): void
	{
		$cookie   = $this->createMock(THttpCookie::class);
		$userMock = $this->createMock(TDbUser::class);
		$this->factoryMock->method('createUserFromCookie')->willReturn($userMock);

		$captured = $this->attachFinalizeCapture($this->manager);
		$this->manager->getUserFromCookie($cookie);

		$this->assertSame($this->manager, $captured->sender);
	}

	public function testGetUserFromCookieInvalidCookieReturnsNull(): void
	{
		$cookie = $this->createMock(THttpCookie::class);
		$this->factoryMock->method('createUserFromCookie')->willReturn(null);

		$this->assertNull($this->manager->getUserFromCookie($cookie));
	}

	public function testGetUserFromCookieInvalidCookieDoesNotCallOnFinalizeUser(): void
	{
		$cookie = $this->createMock(THttpCookie::class);
		$this->factoryMock->method('createUserFromCookie')->willReturn(null);

		$captured = $this->attachFinalizeCapture($this->manager);
		$this->manager->getUserFromCookie($cookie);

		$this->assertFalse($captured->called,
			'onFinalizeUser must NOT fire when cookie resolves to null');
	}

	// ==================================================================
	// 10. saveUserToCookie()
	// ==================================================================

	public function testSaveUserToCookieDelegatesToTDbUserWhenAppUserIsTDbUser(): void
	{
		$cookie   = $this->createMock(THttpCookie::class);
		$userMock = $this->createMock(TDbUser::class);
		$userMock->expects($this->once())
				 ->method('saveUserToCookie')
				 ->with($this->identicalTo($cookie));

		$this->manager->mockApp->setCurrentUser($userMock);
		$this->manager->saveUserToCookie($cookie);
	}

	public function testSaveUserToCookieDoesNothingWhenAppUserIsBaseUser(): void
	{
		$cookie    = $this->createMock(THttpCookie::class);
		$nonDbUser = $this->createMock(TUser::class);
		// TUser has no saveUserToCookie; if it were called the mock would error.
		$this->manager->mockApp->setCurrentUser($nonDbUser);

		$this->manager->saveUserToCookie($cookie);
		$this->assertTrue(true, 'No exception means correct short-circuit');
	}

	public function testSaveUserToCookieDoesNothingWhenAppUserIsNull(): void
	{
		$cookie = $this->createMock(THttpCookie::class);
		$this->manager->mockApp->setCurrentUser(null);

		$this->manager->saveUserToCookie($cookie);
		$this->assertTrue(true);
	}

	// ==================================================================
	// 11. onFinalizeUser() — event mechanics
	// ==================================================================

	public function testOnFinalizeUserFiresAttachedHandler(): void
	{
		$userMock = $this->createMock(TDbUser::class);
		$fired    = false;
		$this->manager->onFinalizeUser[] = function () use (&$fired): void {
			$fired = true;
		};

		$this->manager->onFinalizeUser($userMock);
		$this->assertTrue($fired);
	}

	public function testOnFinalizeUserPassesManagerAsSender(): void
	{
		$userMock       = $this->createMock(TDbUser::class);
		$receivedSender = null;
		$this->manager->onFinalizeUser[] = function ($sender) use (&$receivedSender): void {
			$receivedSender = $sender;
		};

		$this->manager->onFinalizeUser($userMock);
		$this->assertSame($this->manager, $receivedSender);
	}

	public function testOnFinalizeUserPassesUserAsParams(): void
	{
		$userMock     = $this->createMock(TDbUser::class);
		$receivedUser = null;
		$this->manager->onFinalizeUser[] = function ($sender, $user) use (&$receivedUser): void {
			$receivedUser = $user;
		};

		$this->manager->onFinalizeUser($userMock);
		$this->assertSame($userMock, $receivedUser);
	}

	public function testOnFinalizeUserFiresAllAttachedHandlers(): void
	{
		$userMock  = $this->createMock(TDbUser::class);
		$callCount = 0;
		$inc       = function () use (&$callCount): void { $callCount++; };

		$this->manager->onFinalizeUser[] = $inc;
		$this->manager->onFinalizeUser[] = $inc;
		$this->manager->onFinalizeUser[] = $inc;

		$this->manager->onFinalizeUser($userMock);
		$this->assertSame(3, $callCount);
	}

	// ==================================================================
	// 12. getUniqueRolesFromAppParameter()
	// ==================================================================

	public function testGetUniqueRolesFromAppParamReturnsNullWhenNoParamIdConfigured(): void
	{
		$this->assertNull($this->manager->exposedGetUniqueRolesFromAppParameter());
	}

	public function testGetUniqueRolesFromAppParamReturnsEmptyArrayWhenParamAbsent(): void
	{
		$this->manager->markUninitialized();
		$this->manager->setRolesAppParameterId('missing.param');
		$this->manager->markInitialized();

		$result = $this->manager->exposedGetUniqueRolesFromAppParameter();
		$this->assertIsArray($result);
		$this->assertEmpty($result);
	}

	public function testGetUniqueRolesFromAppParamReturnsCorrectRolesWhenPresent(): void
	{
		$this->manager->markUninitialized();
		$this->manager->setRolesAppParameterId('app.roles');
		$this->manager->mockApp->getParameters()->set('app.roles', 'admin,editor,viewer');
		$this->manager->markInitialized();

		$result = $this->manager->exposedGetUniqueRolesFromAppParameter();
		$this->assertCount(3, $result);
		$this->assertContains('admin',  $result);
		$this->assertContains('editor', $result);
		$this->assertContains('viewer', $result);
	}

	public function testGetUniqueRolesFromAppParamTrimsWhitespaceFromEntries(): void
	{
		$this->manager->markUninitialized();
		$this->manager->setRolesAppParameterId('app.roles');
		$this->manager->mockApp->getParameters()->set('app.roles', ' admin , editor ');
		$this->manager->markInitialized();

		$result = array_values($this->manager->exposedGetUniqueRolesFromAppParameter());
		$this->assertSame('admin',  $result[0]);
		$this->assertSame('editor', $result[1]);
	}

	public function testGetUniqueRolesFromAppParamFiltersEmptySegments(): void
	{
		$this->manager->markUninitialized();
		$this->manager->setRolesAppParameterId('app.roles');
		$this->manager->mockApp->getParameters()->set('app.roles', 'admin,,editor,,');
		$this->manager->markInitialized();

		$this->assertCount(2, $this->manager->exposedGetUniqueRolesFromAppParameter());
	}

	public function testGetUniqueRolesFromAppParamWithSingleRoleReturnsOneElement(): void
	{
		$this->manager->markUninitialized();
		$this->manager->setRolesAppParameterId('app.roles');
		$this->manager->mockApp->getParameters()->set('app.roles', 'superadmin');
		$this->manager->markInitialized();

		$this->assertCount(1, $this->manager->exposedGetUniqueRolesFromAppParameter());
	}

	// ==================================================================
	// 13. getUniqueRoles() — priority (factory → appParam → module)
	// ==================================================================

	public function testGetUniqueRolesFactoryWinsOverAllOtherSources(): void
	{
		$this->factoryMock->method('getUniqueRoles')->willReturn(['superadmin']);

		$this->manager->markUninitialized();
		$this->manager->setRolesAppParameterId('app.roles');
		$this->manager->mockApp->getParameters()->set('app.roles', 'editor');
		$this->manager->setUniqueRoles(['viewer']);
		$this->manager->markInitialized();

		$this->assertSame(['superadmin'], $this->manager->getUniqueRoles());
	}

	public function testGetUniqueRolesFactoryEmptyArrayStillWinsOverOtherSources(): void
	{
		// [] !== null — factory wins even with an empty array.
		$this->factoryMock->method('getUniqueRoles')->willReturn([]);

		$this->manager->markUninitialized();
		$this->manager->setUniqueRoles(['should-not-appear']);
		$this->manager->markInitialized();

		$this->assertSame([], $this->manager->getUniqueRoles());
	}

	public function testGetUniqueRolesAppParamWinsWhenFactoryReturnsNull(): void
	{
		$this->factoryMock->method('getUniqueRoles')->willReturn(null);

		$this->manager->markUninitialized();
		$this->manager->setRolesAppParameterId('app.roles');
		$this->manager->mockApp->getParameters()->set('app.roles', 'editor,viewer');
		$this->manager->setUniqueRoles(['should-not-appear']);
		$this->manager->markInitialized();

		$roles = $this->manager->getUniqueRoles();
		$this->assertContains('editor', $roles);
		$this->assertContains('viewer', $roles);
		$this->assertNotContains('should-not-appear', $roles);
	}

	public function testGetUniqueRolesModuleConfigWinsWhenFactoryAndAppParamBothNull(): void
	{
		$this->factoryMock->method('getUniqueRoles')->willReturn(null);
		// No RolesAppParameterId → appParam returns null.

		$this->manager->markUninitialized();
		$this->manager->setUniqueRoles(['module-role-a', 'module-role-b']);
		$this->manager->markInitialized();

		$this->assertSame(['module-role-a', 'module-role-b'], $this->manager->getUniqueRoles());
	}

	public function testGetUniqueRolesReturnsNullWhenAllSourcesAreNull(): void
	{
		$this->factoryMock->method('getUniqueRoles')->willReturn(null);
		// No param ID, no module roles → _uniqueRoles is null.

		$this->assertNull($this->manager->getUniqueRoles());
	}

	public function testGetUniqueRolesAppParamConfiguredButMissingYieldsEmptyNotModuleConfig(): void
	{
		// Param ID set, key absent → getUniqueRolesFromAppParameter returns []
		// (non-null) → module config is NOT reached.
		$this->factoryMock->method('getUniqueRoles')->willReturn(null);

		$this->manager->markUninitialized();
		$this->manager->setRolesAppParameterId('missing.param');
		$this->manager->setUniqueRoles(['module-role']);
		$this->manager->markInitialized();

		$this->assertSame([], $this->manager->getUniqueRoles());
	}

	// ==================================================================
	// 14. getUniqueRoleCount() — priority mirrors getUniqueRoles()
	// ==================================================================

	public function testGetUniqueRoleCountFactoryWinsOverAllOtherSources(): void
	{
		$this->factoryMock->method('getUniqueRoleCount')->willReturn(99);

		$this->manager->markUninitialized();
		$this->manager->setRolesAppParameterId('app.roles');
		$this->manager->mockApp->getParameters()->set('app.roles', 'a,b');
		$this->manager->setUniqueRoles(['x', 'y', 'z']);
		$this->manager->markInitialized();

		$this->assertSame(99, $this->manager->getUniqueRoleCount());
	}

	public function testGetUniqueRoleCountFactoryZeroStillWins(): void
	{
		// 0 !== null → factory returning 0 still wins.
		$this->factoryMock->method('getUniqueRoleCount')->willReturn(0);

		$this->manager->markUninitialized();
		$this->manager->setUniqueRoles(['should-not-count']);
		$this->manager->markInitialized();

		$this->assertSame(0, $this->manager->getUniqueRoleCount());
	}

	public function testGetUniqueRoleCountAppParamWinsWhenFactoryReturnsNull(): void
	{
		$this->factoryMock->method('getUniqueRoleCount')->willReturn(null);

		$this->manager->markUninitialized();
		$this->manager->setRolesAppParameterId('app.roles');
		$this->manager->mockApp->getParameters()->set('app.roles', 'a,b,c');
		$this->manager->setUniqueRoles(['x']);
		$this->manager->markInitialized();

		$this->assertSame(3, $this->manager->getUniqueRoleCount());
	}

	public function testGetUniqueRoleCountModuleConfigWinsWhenFactoryAndAppParamNull(): void
	{
		$this->factoryMock->method('getUniqueRoleCount')->willReturn(null);

		$this->manager->markUninitialized();
		$this->manager->setUniqueRoles(['x', 'y']);
		$this->manager->markInitialized();

		$this->assertSame(2, $this->manager->getUniqueRoleCount());
	}

	public function testGetUniqueRoleCountReturnsZeroWhenAllSourcesNull(): void
	{
		$this->factoryMock->method('getUniqueRoleCount')->willReturn(null);

		$this->assertSame(0, $this->manager->getUniqueRoleCount());
	}

	public function testGetUniqueRoleCountMatchesCountOfGetUniqueRolesForModuleConfig(): void
	{
		$this->factoryMock->method('getUniqueRoles')->willReturn(null);
		$this->factoryMock->method('getUniqueRoleCount')->willReturn(null);

		$this->manager->markUninitialized();
		$this->manager->setUniqueRoles(['a', 'b', 'c', 'd']);
		$this->manager->markInitialized();

		$this->assertCount(
			$this->manager->getUniqueRoleCount(),
			$this->manager->getUniqueRoles()
		);
	}

	public function testGetUniqueRoleCountMatchesCountOfGetUniqueRolesForAppParam(): void
	{
		$this->factoryMock->method('getUniqueRoles')->willReturn(null);
		$this->factoryMock->method('getUniqueRoleCount')->willReturn(null);

		$this->manager->markUninitialized();
		$this->manager->setRolesAppParameterId('app.roles');
		$this->manager->mockApp->getParameters()->set('app.roles', 'r1,r2,r3,r4,r5');
		$this->manager->markInitialized();

		$this->assertCount(
			$this->manager->getUniqueRoleCount(),
			$this->manager->getUniqueRoles()
		);
	}

	// ==================================================================
	// 15. createDbConnection() — error message key regression tests
	//     These guard against re-introduction of the 'dbConnectionectionid'
	//     typo that appeared in the v4 source file.
	// ==================================================================

	public function testCreateDbConnectionInvalidModuleExceptionMessageKeyIsCorrect(): void
	{
		$this->manager->mockApp->registerModule('badmod', new \stdClass());
		try {
			$this->manager->exposedCreateDbConnection('badmod');
			$this->fail('Expected TConfigurationException was not thrown');
		} catch (TConfigurationException $e) {
			$this->assertStringNotContainsStringIgnoringCase(
				'dbConnectionectionid',
				$e->getErrorCode(),
				'Error key must not contain the doubled "ection" typo'
			);
			$this->assertSame('dbusermanager_connectionid_invalid', $e->getErrorCode());
		}
	}

	public function testCreateDbConnectionEmptyIdExceptionMessageKeyIsCorrect(): void
	{
		try {
			$this->manager->exposedCreateDbConnection('');
			$this->fail('Expected TConfigurationException was not thrown');
		} catch (TConfigurationException $e) {
			$this->assertStringNotContainsStringIgnoringCase(
				'dbConnectionectionid',
				$e->getErrorCode(),
				'Error key must not contain the doubled "ection" typo'
			);
			$this->assertSame('dbusermanager_connectionid_required', $e->getErrorCode());
		}
	}

	// ==================================================================
	// 16. createDbConnection() — routing logic
	// ==================================================================

	public function testCreateDbConnectionThrowsWhenConnectionIdIsEmpty(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->manager->exposedCreateDbConnection('');
	}

	public function testCreateDbConnectionThrowsWhenModuleIdIsNotRegistered(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->manager->exposedCreateDbConnection('nonexistent');
	}

	public function testCreateDbConnectionThrowsWhenModuleIsWrongType(): void
	{
		$this->manager->mockApp->registerModule('badmod', new \stdClass());
		$this->expectException(TConfigurationException::class);
		$this->manager->exposedCreateDbConnection('badmod');
	}

	public function testCreateDbConnectionReturnsTDbConnectionFromValidDataSource(): void
	{
		$connMock       = $this->createMock(TDbConnection::class);
		$datasourceMock = $this->createMock(TDataSourceConfig::class);
		$datasourceMock->method('getDbConnection')->willReturn($connMock);

		$this->manager->mockApp->registerModule('db', $datasourceMock);

		$this->assertSame($connMock, $this->manager->exposedCreateDbConnection('db'));
	}

	public function testCreateDbConnectionCallsGetDbConnectionOnDataSource(): void
	{
		$connMock       = $this->createMock(TDbConnection::class);
		$datasourceMock = $this->createMock(TDataSourceConfig::class);
		$datasourceMock->expects($this->once())
					   ->method('getDbConnection')
					   ->willReturn($connMock);

		$this->manager->mockApp->registerModule('db', $datasourceMock);
		$this->manager->exposedCreateDbConnection('db');
	}

	// ==================================================================
	// 17. getDbConnection() — public method
	// ==================================================================

	public function testGetDbConnectionSetsConnectionActiveOnFirstCall(): void
	{
		$connMock       = $this->createMock(TDbConnection::class);
		$connMock->expects($this->once())->method('setActive')->with(true);

		$datasourceMock = $this->createMock(TDataSourceConfig::class);
		$datasourceMock->method('getDbConnection')->willReturn($connMock);

		$this->manager->mockApp->registerModule('db', $datasourceMock);
		$this->manager->setConnectionID('db');
		$this->manager->getDbConnection();
	}

	public function testGetDbConnectionReturnsSameInstanceOnSecondCall(): void
	{
		$connMock       = $this->createMock(TDbConnection::class);
		$connMock->method('setActive');

		$datasourceMock = $this->createMock(TDataSourceConfig::class);
		$datasourceMock->method('getDbConnection')->willReturn($connMock);

		$this->manager->mockApp->registerModule('db', $datasourceMock);
		$this->manager->setConnectionID('db');

		$this->assertSame(
			$this->manager->getDbConnection(),
			$this->manager->getDbConnection()
		);
	}

	public function testGetDbConnectionCallsCreateDbConnectionOnlyOnce(): void
	{
		$connMock       = $this->createMock(TDbConnection::class);
		$connMock->method('setActive');

		$datasourceMock = $this->createMock(TDataSourceConfig::class);
		$datasourceMock->expects($this->once())
					   ->method('getDbConnection')
					   ->willReturn($connMock);

		$this->manager->mockApp->registerModule('db', $datasourceMock);
		$this->manager->setConnectionID('db');

		$this->manager->getDbConnection();
		$this->manager->getDbConnection(); // second call — must use cache
	}

	public function testGetDbConnectionPopulatesInternalCacheField(): void
	{
		$connMock       = $this->createMock(TDbConnection::class);
		$connMock->method('setActive');

		$datasourceMock = $this->createMock(TDataSourceConfig::class);
		$datasourceMock->method('getDbConnection')->willReturn($connMock);

		$this->manager->mockApp->registerModule('db', $datasourceMock);
		$this->manager->setConnectionID('db');

		$this->assertNull($this->manager->readDbConnectionField(),
			'_dbConnection must be null before first call');

		$this->manager->getDbConnection();

		$this->assertSame($connMock, $this->manager->readDbConnectionField(),
			'_dbConnection must be populated after first call');
	}

	public function testGetDbConnectionThrowsWhenConnectionIdIsEmpty(): void
	{
		// _connectionID defaults to '' → createDbConnection throws.
		$this->expectException(TConfigurationException::class);
		$this->manager->getDbConnection();
	}
}