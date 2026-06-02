<?php

/**
 * TRedisCacheTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\TRedisCache;
use Prado\Exceptions\TConfigurationException;

/**
 * Unit tests for {@see TRedisCache}, via the {@see TTestRedisCache} harness. Connection
 * properties, availability, and the backend-handle seam are tested without a server;
 * live operations are exercised only when the `redis` extension and a server are present
 * (otherwise skipped).
 */
class TRedisCacheTest extends PHPUnit\Framework\TestCase
{
	private function newCache(): TTestRedisCache
	{
		$cache = new TTestRedisCache();
		$cache->setPrimaryCache(false);
		return $cache;
	}

	public function testIsAvailableReturnsBool(): void
	{
		$this->assertIsBool(TRedisCache::getIsAvailable());
	}

	public function testConnectionPropertyDefaults(): void
	{
		$cache = $this->newCache();
		$this->assertSame('localhost', $cache->getHost());
		$this->assertSame(6379, $cache->getPort());
		$this->assertSame(0, $cache->getIndex());
	}

	public function testConnectionPropertyRoundTripBeforeInit(): void
	{
		$cache = $this->newCache();
		$cache->setHost('redis.example.test');
		$cache->setPort(6380);
		$cache->setSocket('/var/run/redis/redis.sock');
		$cache->setIndex(2);
		$this->assertSame('redis.example.test', $cache->getHost());
		$this->assertSame(6380, $cache->getPort());
		$this->assertSame('/var/run/redis/redis.sock', $cache->getSocket());
		$this->assertSame(2, $cache->getIndex());
	}

	public function testInitThrowsWhenExtensionUnavailable(): void
	{
		if (TRedisCache::getIsAvailable()) {
			$this->markTestSkipped('redis extension present; cannot exercise the unavailable path.');
		}
		$this->expectException(TConfigurationException::class);
		$this->newCache()->init(null);
	}

	public function testCacheDirectHandleInjection(): void
	{
		$cache = $this->newCache();
		$this->assertNull($cache->pubGetCacheDirect());
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
