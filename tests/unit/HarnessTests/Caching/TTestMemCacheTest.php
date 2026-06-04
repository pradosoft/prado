<?php

/**
 * TTestMemCacheTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\TMemCache;

/**
 * Tests for {@see TTestMemCache}. The handle seam (getCacheDirect/setCacheDirect) is
 * verified with an injected dummy object — no extension required; {@see newMemcached()} is
 * exercised only when the `memcached` extension is available.
 *
 * @package System.Harness.Caching
 */
class TTestMemCacheTest extends PHPUnit\Framework\TestCase
{
	private function newCache(): TTestMemCache
	{
		$cache = new TTestMemCache();
		$cache->setPrimaryCache(false);
		return $cache;
	}

	public function testIsAMemCache(): void
	{
		$this->assertInstanceOf(TMemCache::class, $this->newCache());
	}

	public function testFakeClockOverrides(): void
	{
		$cache = $this->newCache();
		$cache->fakeNow = 11;
		$this->assertSame(11, $cache->pubTime());
	}

	public function testCacheDirectHandleRoundTrips(): void
	{
		$cache = $this->newCache();
		$handle = new \stdClass();
		$cache->pubSetCacheDirect($handle);
		$this->assertSame($handle, $cache->pubGetCacheDirect());
	}

	public function testNewMemcachedWhenAvailable(): void
	{
		if (!TMemCache::getIsAvailable()) {
			$this->markTestSkipped('memcached extension not available.');
		}
		$this->assertInstanceOf(\Memcached::class, $this->newCache()->pubNewMemcached(null));
	}
}
