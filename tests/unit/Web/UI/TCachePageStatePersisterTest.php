<?php

namespace Prado\Test\Unit\Web\UI;

use Prado\Caching\TMemoryCache;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\THttpException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\TModule;
use Prado\Util\Log\TLogger;
use Prado\Security\IUser;
use Prado\Security\TAuthManager;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Test\Unit\Harness\Web\TTestMemorySession;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\THttpSession;
use Prado\Web\UI\TCachePageStatePersister;
use Prado\Web\UI\TCachePageStatePersisterTimeoutMode;
use Prado\Web\UI\TPage;

/**
 * A page whose posted client state a test sets, without a request.
 */
class TCachePersisterTestPage extends TPage
{
	public ?string $requestClientState = null;

	public function getRequestClientState()
	{
		return $this->requestClientState;
	}
}

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

	public function testFallingThroughToAZeroCacheTimeoutLogsAWarning()
	{
		Prado::getLogger()->deleteLogs(null, TCachePageStatePersister::class);

		$this->newPersister('Auto', 0, 0, 0)->getEffectiveCacheTimeout();

		$logs = Prado::getLogger()->getLogs(TLogger::WARNING, TCachePageStatePersister::class);
		self::assertCount(1, $logs);
		self::assertStringContainsString("CacheTimeoutMode 'Auto'", $logs[0][TLogger::LOG_MESSAGE]);
		self::assertStringContainsString('without expiry', $logs[0][TLogger::LOG_MESSAGE]);
	}

	public function testAZeroCacheTimeoutIsQuietWhenItIsTheModeAskedFor()
	{
		Prado::getLogger()->deleteLogs(null, TCachePageStatePersister::class);

		self::assertEquals(0, $this->newPersister('Fixed', 0, 0, 0)->getEffectiveCacheTimeout());
		self::assertEquals(60, $this->newPersister('Auto', 60, 0, 0)->getEffectiveCacheTimeout(), 'a source applied; nothing to warn about');

		self::assertCount(0, Prado::getLogger()->getLogs(TLogger::WARNING, TCachePageStatePersister::class));
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
	// ---- Page, module ID, key prefix, and timeout properties ----

	public function testPageProperty()
	{
		$persister = new TCachePageStatePersister();
		self::assertNull($persister->getPage());
		$page = new TPage();
		$persister->setPage($page);
		self::assertSame($page, $persister->getPage());
	}

	public function testCacheModuleIDProperty()
	{
		$persister = new TCachePageStatePersister();
		self::assertSame('', $persister->getCacheModuleID());
		$persister->setCacheModuleID('pagecache');
		self::assertSame('pagecache', $persister->getCacheModuleID());
	}

	public function testKeyPrefixProperty()
	{
		$persister = new TCachePageStatePersister();
		self::assertSame('statepersister', $persister->getKeyPrefix());
		$persister->setKeyPrefix('state');
		self::assertSame('state', $persister->getKeyPrefix());
	}

	public function testSetCacheTimeoutAcceptsZeroAndRejectsANegativeValue()
	{
		$persister = new TCachePageStatePersister();
		$persister->setCacheTimeout('0');
		self::assertSame(0, $persister->getCacheTimeout());
		$this->expectException(TInvalidDataValueException::class);
		$persister->setCacheTimeout(-1);
	}

	// ---- The cache ----

	public function testGetCacheUsesTheApplicationCacheOnce()
	{
		$cache = new TMemoryCache();
		$this->app->setCache($cache);
		$persister = new TCachePageStatePersister();
		self::assertSame($cache, $persister->getCache());
		$this->app->setCache(new TMemoryCache());
		self::assertSame($cache, $persister->getCache(), 'the first cache found is kept');
	}

	public function testGetCacheWithoutAnApplicationCacheThrows()
	{
		$this->expectException(TConfigurationException::class);
		$this->expectExceptionMessage('requires a cache module');
		(new TCachePageStatePersister())->getCache();
	}

	public function testGetCacheUsesTheCacheModuleID()
	{
		$cache = new TMemoryCache();
		$this->app->setModule('pagecache', $cache);
		$persister = new TCachePageStatePersister();
		$persister->setCacheModuleID('pagecache');
		self::assertSame($cache, $persister->getCache());
	}

	public function testGetCacheWithAMissingCacheModuleIDThrows()
	{
		$persister = new TCachePageStatePersister();
		$persister->setCacheModuleID('nosuchcache');
		$this->expectException(TConfigurationException::class);
		$this->expectExceptionMessage('does not point to a valid cache module');
		$persister->getCache();
	}

	public function testGetCacheWithACacheModuleIDOfAModuleThatIsNoCacheThrows()
	{
		$this->app->setModule('notacache', new class () extends TModule {
		});
		$persister = new TCachePageStatePersister();
		$persister->setCacheModuleID('notacache');
		$this->expectException(TConfigurationException::class);
		$this->expectExceptionMessage('does not point to a valid cache module');
		$persister->getCache();
	}

	// ---- The sources, without an application or a module ----

	public function testSessionTimeoutIsZeroWithoutASessionModule()
	{
		$persister = new TCachePageStatePersister();
		$persister->setCacheTimeoutMode('Session');
		self::assertEquals(1800, $persister->getEffectiveCacheTimeout());
	}

	public function testSessionTimeoutLoadsALazySessionModule()
	{
		self::assertTrue(class_exists(TTestMemorySession::class), 'the class map resolves only a loaded test class');
		PradoUnit::invoke($this->app, 'setLazyModule', 'lazysession', [TTestMemorySession::class, [], null]);
		$this->app->setModule('lazysession', null);
		ini_set('session.gc_maxlifetime', '2700');

		$persister = new TCachePageStatePersister();
		$persister->setCacheTimeoutMode('Session');
		self::assertEquals(2700, $persister->getEffectiveCacheTimeout());
		self::assertInstanceOf(TTestMemorySession::class, $this->app->getModule('lazysession'));
	}

	public function testTheSourcesAreZeroWithoutAnApplication()
	{
		$saved = PradoUnit::getStaticProp(Prado::class, '_application');
		PradoUnit::setStaticProp(Prado::class, '_application', null);
		try {
			$persister = new TCachePageStatePersister();
			self::assertSame(0, PradoUnit::invoke($persister, 'getAuthTimeout'));
			self::assertSame(0, PradoUnit::invoke($persister, 'getSessionTimeout'));
			self::assertNull(PradoUnit::invoke($persister, 'findModule', THttpSession::class));
		} finally {
			PradoUnit::setStaticProp(Prado::class, '_application', $saved);
		}
	}

	// ---- Saving and loading ----

	/**
	 * @return array{TCachePageStatePersister, TCachePersisterTestPage, TMemoryCache} a persister of a page and its cache.
	 */
	protected function newSavingPersister(): array
	{
		$cache = new TMemoryCache();
		$this->app->setCache($cache);
		$page = new TCachePersisterTestPage();
		$page->setEnableStateValidation(false);
		$page->setEnableStateEncryption(false);
		$page->setEnableStateCompression(false);
		$page->setPagePath('Home');
		$persister = new TCachePageStatePersister();
		$persister->setPage($page);
		$persister->setCacheTimeout(600);
		return [$persister, $page, $cache];
	}

	public function testSaveCachesTheStateAndLoadReadsItBack()
	{
		[$persister, $page, $cache] = $this->newSavingPersister();
		$state = ['a' => 1, 'b' => [2, 3]];

		$persister->save($state);

		$clientState = $page->getClientState();
		self::assertNotEmpty($clientState);
		$page->requestClientState = $clientState;
		self::assertSame($state, $persister->load());
	}

	public function testTheKeyHoldsThePrefixThePagePathAndTheTimestamp()
	{
		[$persister, $page] = $this->newSavingPersister();
		$persister->setKeyPrefix('pfx');
		$key = PradoUnit::invoke($persister, 'calculateKey', '12.5');
		self::assertStringStartsWith('pfx:', $key);
		self::assertStringEndsWith('Home12.5', $key);
	}

	public function testLoadWithoutClientStateThrows()
	{
		[$persister] = $this->newSavingPersister();
		$this->expectException(THttpException::class);
		$this->expectExceptionMessage('Page state is corrupted');
		$persister->load();
	}

	public function testLoadOfAnExpiredStateThrows()
	{
		[$persister, $page, $cache] = $this->newSavingPersister();
		$persister->save(['a' => 1]);
		$page->requestClientState = $page->getClientState();
		$cache->flush();

		$this->expectException(THttpException::class);
		$this->expectExceptionMessage('Page state is corrupted');
		$persister->load();
	}
}
