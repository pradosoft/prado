<?php

/**
 * TRedisCacheTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Caching;

use Prado\Caching\TRedisCache;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Test\Unit\Harness\Caching\TTestRedis;
use Prado\Test\Unit\Harness\Caching\TTestRedisCache;

/**
 * A TRedisCache over {@see TTestRedis}, with the availability check switchable.
 */
class TRedisCacheTestCache extends TTestRedisCache
{
	public static bool $available = true;
	public ?object $redis = null;

	public static function getIsAvailable(): bool
	{
		return static::$available;
	}

	protected function newRedis(): object
	{
		return $this->redis ??= new TTestRedis();
	}
}

/**
 * Unit tests for {@see TRedisCache}, via the {@see TTestRedisCache} harness. Connection
 * properties, availability, and the backend-handle seam are tested without a server;
 * live operations are exercised only when the `redis` extension and a server are present
 * (otherwise skipped).
 */
class TRedisCacheTest extends \PHPUnit\Framework\TestCase
{
	protected function tearDown(): void
	{
		TRedisCacheTestCache::$available = true;
	}

	private function newCache(): TTestRedisCache
	{
		$cache = new TTestRedisCache();
		$cache->setPrimaryCache(false);
		return $cache;
	}

	/**
	 * @return TRedisCacheTestCache a cache whose Redis instance is a {@see TTestRedis}.
	 */
	private function newFakeCache(): TRedisCacheTestCache
	{
		if (!extension_loaded('redis')) {
			$this->markTestSkipped('redis extension not available.');
		}
		$cache = new TRedisCacheTestCache();
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

	public function testInitThrowsWhenReportedUnavailable(): void
	{
		TRedisCacheTestCache::$available = false;
		$cache = new TRedisCacheTestCache();
		$cache->setPrimaryCache(false);
		try {
			$cache->init(null);
			$this->fail('init() did not throw while unavailable.');
		} catch (TConfigurationException $e) {
			$this->assertSame('rediscache_extension_required', $e->getErrorCode());
		}
		$this->assertNull($cache->pubGetCacheDirect());
	}

	public function testInitConnectsOverTcp(): void
	{
		$cache = $this->newFakeCache();
		$cache->setHost('redis.example.test');
		$cache->setPort('6380');
		$cache->setIndex('3');
		$cache->init(null);

		$this->assertSame([
			['connect', 'redis.example.test', 6380],
			['setOption', \Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP],
			['select', 3],
		], $cache->redis->calls);
		$this->assertTrue($cache->getIsInitialized());
	}

	public function testInitConnectsOverASocket(): void
	{
		$cache = $this->newFakeCache();
		$cache->setSocket('/var/run/redis/redis.sock');
		$cache->init(null);
		$this->assertSame(['connect', '/var/run/redis/redis.sock'], $cache->redis->calls[0]);
	}

	public function testValuesGoThroughTheRedisInstance(): void
	{
		$cache = $this->newFakeCache();
		$cache->init(null);
		$redis = $cache->redis;
		$redis->calls = [];

		$this->assertTrue($cache->pubSetValue('a', 'one', 0));
		$this->assertTrue($cache->pubSetValue('b', 'two', 30));
		$this->assertFalse($cache->pubAddValue('a', 'other', 0));
		$this->assertTrue($cache->pubAddValue('c', 'three', 10));
		$this->assertSame([
			['set', 'a', []],
			['set', 'b', ['ex' => 30]],
			['set', 'a', ['nx']],
			['set', 'c', ['nx', 'ex' => 10]],
		], $redis->calls);
		$this->assertSame('one', $cache->pubGetValue('a'));
		$this->assertTrue($cache->pubDeleteValue('a'));
		$this->assertTrue($cache->pubDeleteValue('a'));
		$this->assertFalse($cache->pubGetValue('a'));
		$this->assertTrue($cache->flush());
		$this->assertSame([], $redis->data);
	}

	public function testDestructClosesTheRedisInstance(): void
	{
		$cache = $this->newFakeCache();
		$cache->init(null);
		$redis = $cache->redis;
		unset($cache);
		$this->assertSame(['close'], end($redis->calls));
	}

	/**
	 * @dataProvider frozenSetterProvider
	 */
	public function testConnectionPropertiesCannotChangeAfterInit(string $setter, mixed $value): void
	{
		$cache = $this->newFakeCache();
		$cache->init(null);
		$this->expectException(TInvalidOperationException::class);
		$cache->$setter($value);
	}

	public static function frozenSetterProvider(): array
	{
		return [
			'Host'   => ['setHost', 'other'],
			'Port'   => ['setPort', 6380],
			'Socket' => ['setSocket', '/tmp/redis.sock'],
			'Index'  => ['setIndex', 1],
		];
	}
}
