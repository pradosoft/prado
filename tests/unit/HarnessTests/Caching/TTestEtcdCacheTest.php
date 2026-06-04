<?php

/**
 * TTestEtcdCacheTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\TEtcdCache;

/**
 * Tests for {@see TTestEtcdCache}. Verifies type and the clock seam; the serialized
 * contract and {@see request()} require a live etcd service / cURL and are exercised by
 * the live TEtcdCacheTest.
 *
 * @package System.Harness.Caching
 */
class TTestEtcdCacheTest extends PHPUnit\Framework\TestCase
{
	private function newCache(): TTestEtcdCache
	{
		$cache = new TTestEtcdCache();
		$cache->setPrimaryCache(false);
		return $cache;
	}

	public function testIsAnEtcdCache(): void
	{
		$this->assertInstanceOf(TEtcdCache::class, $this->newCache());
	}

	public function testFakeClockOverrides(): void
	{
		$cache = $this->newCache();
		$cache->fakeNow = 33;
		$this->assertSame(33, $cache->pubTime());
		$cache->fakeMicrotime = 4.5;
		$this->assertSame(4.5, $cache->pubMicrotime());
	}

	public function testDirDefault(): void
	{
		$this->assertSame('pradocache', $this->newCache()->getDir());
	}
}
