<?php

/**
 * TTestDbCacheTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\TDbCache;

/**
 * Tests for {@see TTestDbCache}. Exercises the seams that need no live database
 * (the cache-initialized flag, connection-activation type, clock); the serialized
 * contract is covered by the live TDbCacheTest.
 *
 * @package System.Harness.Caching
 */
class TTestDbCacheTest extends PHPUnit\Framework\TestCase
{
	private function newCache(): TTestDbCache
	{
		$cache = new TTestDbCache();
		$cache->setPrimaryCache(false);
		return $cache;
	}

	public function testIsADbCache(): void
	{
		$this->assertInstanceOf(TDbCache::class, $this->newCache());
	}

	public function testFakeClockOverrides(): void
	{
		$cache = $this->newCache();
		$cache->fakeNow = 555;
		$cache->fakeMicrotime = 1.5;
		$this->assertSame(555, $cache->pubTime());
		$this->assertSame(1.5, $cache->pubMicrotime());
	}

	public function testCacheInitializedFlagRoundTrips(): void
	{
		$cache = $this->newCache();
		$this->assertFalse($cache->pubGetIsCacheInitialized());
		$cache->pubSetIsCacheInitialized(true);
		$this->assertTrue($cache->pubGetIsCacheInitialized());
	}

	public function testConnectionActivationTypeDefaultsToTrue(): void
	{
		$this->assertTrue($this->newCache()->pubGetDbConnectionActivationType());
	}

	public function testCustomDbConnectionDefaultsToNull(): void
	{
		$this->assertNull($this->newCache()->pubGetCustomDbConnection());
	}
}
