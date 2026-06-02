<?php

/**
 * TCacheTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\ICache;
use Prado\Caching\ICacheDependency;
use Prado\Exceptions\TConfigurationException;
use Prado\TApplication;

/**
 * Unit tests for the abstract {@see \Prado\Caching\TCache} base, exercised through the
 * {@see TTestCache} harness (an array-backed concrete TCache). Covers the public ICache
 * behavior (key prefixing/hashing, dependency wrapping, empty-value delete, ArrayAccess),
 * primary-cache registration, and the time()/microtime() clock seams.
 */
class TCacheTest extends PHPUnit\Framework\TestCase
{
	private TApplication $app;

	protected function setUp(): void
	{
		// A fresh application per test → getCache() starts null for primary-cache tests.
		$this->app = new TApplication(__DIR__ . '/mockapp');
	}

	private function newCache(bool $primary = false): TTestCache
	{
		$cache = new TTestCache();
		$cache->setPrimaryCache($primary);
		$cache->init(null);
		return $cache;
	}

	public function testIsAnICache(): void
	{
		$this->assertInstanceOf(ICache::class, $this->newCache());
		$this->assertTrue(TTestCache::getIsAvailable());
	}

	public function testSetGetRoundTrip(): void
	{
		$cache = $this->newCache();
		$this->assertTrue($cache->set('k', ['a' => 1]));
		$this->assertSame(['a' => 1], $cache->get('k'));
		$this->assertFalse($cache->get('missing'));
	}

	public function testUnchangedDependencyKeepsValueChangedDependencyMisses(): void
	{
		$cache = $this->newCache();
		$unchanged = new class implements ICacheDependency {
			public function getHasChanged(): bool
			{
				return false;
			}
		};
		$cache->set('a', 'v', 0, $unchanged);
		$this->assertSame('v', $cache->get('a'));

		$changed = new class implements ICacheDependency {
			public function getHasChanged(): bool
			{
				return true;
			}
		};
		$cache->set('b', 'v', 0, $changed);
		$this->assertFalse($cache->get('b'), 'A changed dependency must make get() a miss.');
	}

	public function testEmptyValueWithZeroExpireDeletes(): void
	{
		$cache = $this->newCache();
		$cache->set('k', 'value');
		$this->assertTrue($cache->set('k', '', 0)); // delegates to delete()
		$this->assertFalse($cache->get('k'));
	}

	public function testEmptyValueWithNonZeroExpireIsStored(): void
	{
		$cache = $this->newCache();
		$this->assertTrue($cache->set('k', '', 10));
		$this->assertSame('', $cache->get('k'));
	}

	public function testAddOnlyStoresWhenAbsent(): void
	{
		$cache = $this->newCache();
		$this->assertTrue($cache->add('k', 'first'));
		$this->assertFalse($cache->add('k', 'second'));
		$this->assertSame('first', $cache->get('k'));
	}

	public function testAddRejectsEmptyValueWithZeroExpire(): void
	{
		$cache = $this->newCache();
		$this->assertFalse($cache->add('k', '', 0));
		$this->assertFalse($cache->get('k'));
	}

	public function testDelete(): void
	{
		$cache = $this->newCache();
		$cache->set('k', 'v');
		$this->assertTrue($cache->delete('k'));
		$this->assertFalse($cache->get('k'));
	}

	public function testArrayAccess(): void
	{
		$cache = $this->newCache();
		$cache['k'] = 'v';
		$this->assertTrue(isset($cache['k']));
		$this->assertSame('v', $cache['k']);
		unset($cache['k']);
		$this->assertFalse(isset($cache['k']));
		$this->assertFalse($cache['k']);
	}

	public function testKeyPrefixAndGeneration(): void
	{
		$cache = $this->newCache();
		$cache->setKeyPrefix('pfx_');
		$this->assertSame('pfx_', $cache->pubGetKeyPrefix());
		$this->assertSame('pfx_my_key', $cache->pubGenerateToken('my_key'));
		$this->assertSame(sha1('pfx_my_key'), $cache->pubHashToken('pfx_my_key'));
		$this->assertSame(sha1('pfx_my_key'), $cache->pubGenerateUniqueKey('my_key'));
	}

	public function testTimeAndMicrotimeSeamsDriveExpiry(): void
	{
		$cache = $this->newCache();
		$cache->fakeNow = 1_000_000;
		$cache->set('k', 'v', 10);
		$this->assertSame('v', $cache->get('k'));
		$this->assertSame(1_000_000, $cache->pubTime());

		$cache->fakeNow = 1_000_010; // exactly at expiry → expired (expire <= now)
		$this->assertFalse($cache->get('k'));

		$cache->fakeMicrotime = 5.5;
		$this->assertSame(5.5, $cache->pubMicrotime());
	}

	public function testPrimaryCacheRegistersAndDuplicateThrows(): void
	{
		$first = $this->newCache(true);
		$this->assertSame($first, $this->app->getCache());

		$second = new TTestCache();
		$second->setPrimaryCache(true);
		$this->expectException(TConfigurationException::class);
		$second->init(null); // app already has a primary cache → cache_primary_duplicated
	}

	public function testNonPrimaryCacheDoesNotRegister(): void
	{
		$this->newCache(false);
		$this->assertNull($this->app->getCache());
	}
}
