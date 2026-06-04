<?php

/**
 * TTestAPCCacheTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\TAPCCache;

/**
 * Tests for {@see TTestAPCCache}. Verifies type, the clock seam, and — only when the
 * `apcu` extension is available — the value-contract exposers.
 *
 * @package System.Harness.Caching
 */
class TTestAPCCacheTest extends PHPUnit\Framework\TestCase
{
	private function newCache(): TTestAPCCache
	{
		$cache = new TTestAPCCache();
		$cache->setPrimaryCache(false);
		return $cache;
	}

	public function testIsAnAPCCache(): void
	{
		$this->assertInstanceOf(TAPCCache::class, $this->newCache());
	}

	public function testFakeClockOverrides(): void
	{
		$cache = $this->newCache();
		$cache->fakeNow = 321;
		$this->assertSame(321, $cache->pubTime());
		$cache->fakeMicrotime = 9.5;
		$this->assertSame(9.5, $cache->pubMicrotime());
	}

	public function testValueContractExposersWhenAvailable(): void
	{
		if (!TAPCCache::getIsAvailable()) {
			$this->markTestSkipped('apcu extension not available.');
		}
		$cache = $this->newCache();
		$cache->init(null);
		$key = 'ttest_apc_' . getmypid();
		$this->assertTrue($cache->pubSetValue($key, 'v', 0));
		$this->assertSame('v', $cache->pubGetValue($key));
		$this->assertTrue($cache->pubDeleteValue($key));
	}
}
