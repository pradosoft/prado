<?php

namespace Prado\Test\Unit\Web\UI;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Security\IUser;
use Prado\Security\TAuthManager;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Web\THttpSession;
use Prado\Web\UI\TCachePageStatePersister;
use Prado\Web\UI\TCachePageStatePersisterTimeoutMode;

/**
 * Reports fixed login and session lifetimes, so a test drives the resolution chain
 * without configuring the modules behind them.
 */
class TFixedSourcesCachePageStatePersister extends TCachePageStatePersister
{
	public int $authTimeout = 0;

	public int $sessionTimeout = 0;

	protected function getAuthTimeout(): int
	{
		return $this->authTimeout;
	}

	protected function getSessionTimeout(): int
	{
		return $this->sessionTimeout;
	}
}

/**
 * Starts from the `Auto` mode, so a test proves the default reaches the constructor
 * through late static binding.
 */
class TAutoCachePageStatePersister extends TCachePageStatePersister
{
	public const DEFAULT_CACHE_TIMEOUT_MODE = TCachePageStatePersisterTimeoutMode::Auto;
}

class TCachePageStatePersisterTest extends \PHPUnit\Framework\TestCase
{
	protected ?TTestApplication $app = null;

	protected $gcMaxLifetime;

	protected function setUp(): void
	{
		$this->app = new TTestApplication(__DIR__ . '/../app');
		$this->gcMaxLifetime = ini_get('session.gc_maxlifetime');
	}

	protected function tearDown(): void
	{
		ini_set('session.gc_maxlifetime', $this->gcMaxLifetime);
		if ($this->app !== null) {
			$this->app->restoreApplication();
			$this->app = null;
		}
	}

	/**
	 * @return TFixedSourcesCachePageStatePersister a persister whose sources report the given lifetimes.
	 */
	protected function newPersister(string $mode, int $auth, int $session, int $fixed = 1800): TFixedSourcesCachePageStatePersister
	{
		$persister = new TFixedSourcesCachePageStatePersister();
		$persister->setCacheTimeout($fixed);
		$persister->setCacheTimeoutMode($mode);
		$persister->authTimeout = $auth;
		$persister->sessionTimeout = $session;
		return $persister;
	}

	/**
	 * @return IUser a user that is authenticated, or a guest.
	 */
	protected function newUser(bool $guest): IUser
	{
		$user = $this->createMock(IUser::class);
		$user->method('getIsGuest')->willReturn($guest);
		return $user;
	}

	// ---- CacheTimeoutMode property ----

	public function testCacheTimeoutModeDefaultsToFixed()
	{
		$persister = new TCachePageStatePersister();
		self::assertEquals(TCachePageStatePersister::DEFAULT_CACHE_TIMEOUT_MODE, $persister->getCacheTimeoutMode());
		self::assertEquals(TCachePageStatePersisterTimeoutMode::Fixed, $persister->getCacheTimeoutMode());
		self::assertEquals(1800, $persister->getEffectiveCacheTimeout());
	}

	public function testSubclassDefaultReachesTheConstructor()
	{
		self::assertEquals(TCachePageStatePersisterTimeoutMode::Auto, (new TAutoCachePageStatePersister())->getCacheTimeoutMode());
	}

	public function testSetCacheTimeoutModeAcceptsEachValue()
	{
		$persister = new TCachePageStatePersister();
		foreach (['Fixed', 'Session', 'Auth', 'Auto'] as $mode) {
			$persister->setCacheTimeoutMode($mode);
			self::assertEquals($mode, $persister->getCacheTimeoutMode());
		}
		$persister->setCacheTimeoutMode('auto');
		self::assertEquals(TCachePageStatePersisterTimeoutMode::Auto, $persister->getCacheTimeoutMode(), 'the mode is case insensitive');
	}

	public function testSetCacheTimeoutModeRejectsAnUnknownValue()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TCachePageStatePersister())->setCacheTimeoutMode('Forever');
	}

	public function testCacheTimeoutModeIsReachedAsAPageSubProperty()
	{
		$page = new \Prado\Web\UI\TPage();
		$page->setStatePersisterClass(TCachePageStatePersister::class);
		$page->setSubProperty('StatePersister.CacheTimeoutMode', 'Session');
		self::assertEquals(TCachePageStatePersisterTimeoutMode::Session, $page->getStatePersister()->getCacheTimeoutMode());
	}

	// ---- Resolution chain ----

	public static function resolutionData(): array
	{
		// mode, auth lifetime, session lifetime, expected
		return [
			'Fixed ignores both sources' => ['Fixed', 3600, 1440, 1800],
			'Session uses the session' => ['Session', 3600, 1440, 1440],
			'Session without a session' => ['Session', 3600, 0, 1800],
			'Auth uses the login' => ['Auth', 3600, 1440, 3600],
			'Auth without a login' => ['Auth', 0, 1440, 1800],
			'Auto prefers the login' => ['Auto', 3600, 1440, 3600],
			'Auto falls to the session' => ['Auto', 0, 1440, 1440],
			'Auto falls to CacheTimeout' => ['Auto', 0, 0, 1800],
		];
	}

	/**
	 * @dataProvider resolutionData
	 */
	public function testEffectiveCacheTimeout(string $mode, int $auth, int $session, int $expected)
	{
		self::assertEquals($expected, $this->newPersister($mode, $auth, $session)->getEffectiveCacheTimeout());
	}

	public function testCacheTimeoutEndsTheChainEvenWhenItIsZero()
	{
		self::assertEquals(0, $this->newPersister('Auto', 0, 0, 0)->getEffectiveCacheTimeout());
	}

	// ---- The sources, against configured modules ----

	public function testSessionTimeoutReadsTheSessionModule()
	{
		$session = new THttpSession();
		$session->setTimeout(2400);
		$this->app->setModule('session', $session);

		$persister = new TCachePageStatePersister();
		$persister->setCacheTimeoutMode('Session');
		self::assertEquals(2400, $persister->getEffectiveCacheTimeout());
	}

	public function testAuthTimeoutReadsTheAuthManagerForAnAuthenticatedUser()
	{
		$auth = new TAuthManager();
		$auth->setAuthExpire(3600);
		$auth->setAllowAutoLogin(false);
		$this->app->setModule('auth', $auth);
		$this->app->setUser($this->newUser(false));

		$persister = new TCachePageStatePersister();
		$persister->setCacheTimeoutMode('Auth');
		self::assertEquals(3600, $persister->getEffectiveCacheTimeout());
	}

	public function testAuthTimeoutIgnoresAGuest()
	{
		$auth = new TAuthManager();
		$auth->setAuthExpire(3600);
		$this->app->setModule('auth', $auth);
		$this->app->setUser($this->newUser(true));

		$persister = new TCachePageStatePersister();
		$persister->setCacheTimeoutMode('Auth');
		self::assertEquals(1800, $persister->getEffectiveCacheTimeout());
	}

	public function testAuthTimeoutIgnoresAutoLogin()
	{
		$auth = new TAuthManager();
		$auth->setAuthExpire(3600);
		$auth->setAllowAutoLogin(true);
		$this->app->setModule('auth', $auth);
		$this->app->setUser($this->newUser(false));

		$persister = new TCachePageStatePersister();
		$persister->setCacheTimeoutMode('Auth');
		self::assertEquals(1800, $persister->getEffectiveCacheTimeout(), 'an auto-login cookie renews an expired login');
	}

	public function testAuthTimeoutIgnoresANeverExpiringLogin()
	{
		$auth = new TAuthManager();
		$auth->setAuthExpire(0);
		$this->app->setModule('auth', $auth);
		$this->app->setUser($this->newUser(false));

		$persister = new TCachePageStatePersister();
		$persister->setCacheTimeoutMode('Auth');
		self::assertEquals(1800, $persister->getEffectiveCacheTimeout(), 'AuthExpire 0 must not make the cached state permanent');
	}
}
