<?php

/**
 * TTestRedisCacheTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\TRedisCache;

/**
 * Tests for {@see TTestRedisCache}. The handle seam is verified with an injected dummy
 * object — no extension required; {@see newRedis()} is exercised only when the `redis`
 * extension is available.
 *
 * @package System.Harness.Caching
 */
class TTestRedisCacheTest extends PHPUnit\Framework\TestCase
{
	private function newCache(): TTestRedisCache
	{
		$cache = new TTestRedisCache();
		$cache->setPrimaryCache(false);
		return $cache;
	}

	public function testIsARedisCache(): void
	{
		$this->assertInstanceOf(TRedisCache::class, $this->newCache());
	}

	public function testFakeClockOverrides(): void
	{
		$cache = $this->newCache();
		$cache->fakeNow = 22;
		$this->assertSame(22, $cache->pubTime());
	}

	public function testCacheDirectHandleRoundTrips(): void
	{
		$cache = $this->newCache();
		$handle = new \stdClass();
		$cache->pubSetCacheDirect($handle);
		$this->assertSame($handle, $cache->pubGetCacheDirect());
	}

	public function testNewRedisWhenAvailable(): void
	{
		if (!TRedisCache::getIsAvailable()) {
			$this->markTestSkipped('redis extension not available.');
		}
		$this->assertInstanceOf(\Redis::class, $this->newCache()->pubNewRedis());
	}
}
