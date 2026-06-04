<?php

/**
 * TEtcdCacheTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\TEtcdCache;
use Prado\Exceptions\TConfigurationException;

/**
 * Unit tests for {@see TEtcdCache}, via the {@see TTestEtcdCache} harness. Host/Port/Dir
 * properties and availability are tested without a server; the HTTP request path and the
 * serialized contract require a live etcd instance and are not exercised here.
 */
class TEtcdCacheTest extends PHPUnit\Framework\TestCase
{
	private function newCache(): TTestEtcdCache
	{
		$cache = new TTestEtcdCache();
		$cache->setPrimaryCache(false);
		return $cache;
	}

	public function testIsAvailableReflectsCurl(): void
	{
		$this->assertSame(function_exists('curl_version'), TEtcdCache::getIsAvailable());
	}

	public function testPropertyDefaults(): void
	{
		$cache = $this->newCache();
		$this->assertSame('localhost', $cache->getHost());
		$this->assertSame(2379, $cache->getPort());
		$this->assertSame('pradocache', $cache->getDir());
	}

	public function testPropertyRoundTrip(): void
	{
		$cache = $this->newCache();
		$cache->setHost('etcd.example.test');
		$cache->setPort(12379);
		$cache->setDir('myapp');
		$this->assertSame('etcd.example.test', $cache->getHost());
		$this->assertSame(12379, $cache->getPort());
		$this->assertSame('myapp', $cache->getDir());
	}

	public function testInitThrowsWhenCurlUnavailable(): void
	{
		if (TEtcdCache::getIsAvailable()) {
			$this->markTestSkipped('cURL present; cannot exercise the unavailable path.');
		}
		$this->expectException(TConfigurationException::class);
		$this->newCache()->init(null);
	}

	public function testFakeClockSeam(): void
	{
		$cache = $this->newCache();
		$cache->fakeNow = 4242;
		$this->assertSame(4242, $cache->pubTime());
	}

	/**
	 * @dataProvider frozenSetterProvider
	 */
	public function testConfigPropertiesCannotChangeAfterInit(string $setter, mixed $value): void
	{
		if (!TEtcdCache::getIsAvailable()) {
			$this->markTestSkipped('cURL required to initialize TEtcdCache.');
		}
		$cache = $this->newCache();
		$cache->init(null);
		$this->expectException(\Prado\Exceptions\TInvalidOperationException::class);
		$cache->$setter($value);
	}

	public static function frozenSetterProvider(): array
	{
		return [
			'Host' => ['setHost', 'other.host'],
			'Port' => ['setPort', 9999],
			'Dir'  => ['setDir', 'otherdir'],
		];
	}
}
