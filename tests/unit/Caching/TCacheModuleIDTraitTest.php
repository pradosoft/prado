<?php

/**
 * TCacheModuleIDTraitTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Caching;

use Prado\Caching\ICache;
use Prado\Caching\TCacheModuleIDTrait;
use Prado\Exceptions\TConfigurationException;
use Prado\Security\TSecurityManager;
use Prado\TComponent;
use Prado\Test\Unit\Harness\Caching\TTestCache;
use Prado\Test\Unit\Harness\TTestApplication;

/**
 * A component using {@see TCacheModuleIDTrait}, with its protected methods exposed.
 */
class TCacheModuleIDTraitTestUser extends TComponent
{
	use TCacheModuleIDTrait;

	public function pubResolveCacheModule(bool $required = true): ?ICache
	{
		return $this->resolveCacheModule($required);
	}

	public function pubClaimCacheKey(ICache $cache, string $key, int $ttl): bool
	{
		return $this->claimCacheKey($cache, $key, $ttl);
	}
}

class TCacheModuleIDTraitTest extends \PHPUnit\Framework\TestCase
{
	private ?TTestApplication $app = null;

	protected function setUp(): void
	{
		$this->app = new TTestApplication();
	}

	protected function tearDown(): void
	{
		$this->app?->restoreApplication();
		$this->app = null;
	}

	private function newCache(): TTestCache
	{
		$cache = new TTestCache();
		$cache->setPrimaryCache(false);
		$cache->init(null);
		return $cache;
	}

	public function testCacheModuleIdDefaultsToEmptyAndIsAString(): void
	{
		$user = new TCacheModuleIDTraitTestUser();
		$this->assertSame('', $user->getCacheModuleID());
		$user->setCacheModuleID(12);
		$this->assertSame('12', $user->getCacheModuleID());
	}

	public function testResolvesTheNamedModule(): void
	{
		$cache = $this->newCache();
		$this->app->setModule('named', $cache);
		$user = new TCacheModuleIDTraitTestUser();
		$user->setCacheModuleID('named');
		$this->assertSame($cache, $user->pubResolveCacheModule());
	}

	/**
	 * @dataProvider invalidModuleProvider
	 */
	public function testRejectsANamedModuleThatIsNotACache(bool $register): void
	{
		if ($register) {
			$this->app->setModule('notcache', new TSecurityManager());
		}
		$user = new TCacheModuleIDTraitTestUser();
		$user->setCacheModuleID('notcache');
		try {
			$user->pubResolveCacheModule(false);
			$this->fail('A module that is not an ICache resolved.');
		} catch (TConfigurationException $e) {
			$this->assertSame('cachemoduleid_invalid', $e->getErrorCode());
		}
	}

	public static function invalidModuleProvider(): array
	{
		return ['not a cache' => [true], 'missing' => [false]];
	}

	public function testFallsBackToThePrimaryCache(): void
	{
		$cache = $this->newCache();
		$this->app->setCache($cache);
		$this->assertSame($cache, (new TCacheModuleIDTraitTestUser())->pubResolveCacheModule());
	}

	public function testWithoutACacheRequiredThrowsAndOptionalIsNull(): void
	{
		$user = new TCacheModuleIDTraitTestUser();
		$this->assertNull($user->pubResolveCacheModule(false));
		$this->expectException(TConfigurationException::class);
		$this->expectExceptionMessage(TCacheModuleIDTraitTestUser::class);
		$user->pubResolveCacheModule();
	}

	public function testClaimCacheKeyClaimsOnceAndHoldsAtLeastASecond(): void
	{
		$cache = $this->newCache();
		$user = new TCacheModuleIDTraitTestUser();
		$this->assertTrue($user->pubClaimCacheKey($cache, 'nonce', 0));
		$this->assertFalse($user->pubClaimCacheKey($cache, 'nonce', 60));
		$entry = $cache->store[$cache->pubGenerateUniqueKey('nonce')];
		$this->assertSame($cache->getClock()->time() + 1, $entry[1]);
	}
}
