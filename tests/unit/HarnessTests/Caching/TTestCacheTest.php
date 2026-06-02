<?php

/**
 * TTestCacheTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\ICache;
use Prado\Caching\TCache;

/**
 * Tests for {@see TTestCache}, the array-backed {@see TCache} harness.
 *
 * Verifies the storage contract, the fakeable clock seam, and that the exposers reach
 * their protected targets (key generation/hashing/prefix).
 *
 * @package System.Harness.Caching
 */
class TTestCacheTest extends PHPUnit\Framework\TestCase
{
	private function newCache(): TTestCache
	{
		$cache = new TTestCache();
		$cache->setPrimaryCache(false);
		$cache->init(null);
		return $cache;
	}

	public function testIsACache(): void
	{
		$this->assertInstanceOf(TCache::class, new TTestCache());
		$this->assertInstanceOf(ICache::class, new TTestCache());
		$this->assertTrue(TTestCache::getIsAvailable());
	}

	public function testSetGetDeleteFlushRoundTrip(): void
	{
		$cache = $this->newCache();
		$this->assertTrue($cache->set('k', ['v' => 1]));
		$this->assertSame(['v' => 1], $cache->get('k'));
		$this->assertTrue($cache->delete('k'));
		$this->assertFalse($cache->get('k'));

		$cache->set('a', 1);
		$cache->flush();
		$this->assertFalse($cache->get('a'));
	}

	public function testAddOnlyStoresWhenAbsent(): void
	{
		$cache = $this->newCache();
		$this->assertTrue($cache->add('k', 'first'));
		$this->assertFalse($cache->add('k', 'second'));
		$this->assertSame('first', $cache->get('k'));
	}

	public function testFakeNowDrivesExpiry(): void
	{
		$cache = $this->newCache();
		$cache->fakeNow = 1_000_000;
		$cache->set('k', 'v', 10); // expires at 1_000_010
		$this->assertSame('v', $cache->get('k'));

		$cache->fakeNow = 1_000_011; // one second past expiry
		$this->assertFalse($cache->get('k'));
	}

	public function testFakeMicrotimeOverride(): void
	{
		$cache = $this->newCache();
		$cache->fakeMicrotime = 123.5;
		$this->assertSame(123.5, $cache->pubMicrotime());
	}

	public function testKeyGenerationExposers(): void
	{
		$cache = $this->newCache();
		$cache->setKeyPrefix('pfx_');
		$this->assertSame('pfx_my_key', $cache->pubGenerateToken('my_key'));
		$this->assertSame(sha1('pfx_my_key'), $cache->pubHashToken('pfx_my_key'));
		$this->assertSame(sha1('pfx_my_key'), $cache->pubGenerateUniqueKey('my_key'));
		$this->assertSame('pfx_', $cache->pubGetKeyPrefix());
	}
}
