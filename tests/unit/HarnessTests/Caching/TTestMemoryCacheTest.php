<?php

/**
 * TTestMemoryCacheTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\TMemoryCache;

/**
 * Tests for {@see TTestMemoryCache}, verifying the harness exposers for the store core,
 * serialize helpers, backing persistence, and the late-static-binding fixture subclasses.
 *
 * @package System.Harness.Caching
 */
class TTestMemoryCacheTest extends PHPUnit\Framework\TestCase
{
	private TTestMemoryCache $cache;

	protected function setUp(): void
	{
		$this->cache = new TTestMemoryCache();
		$this->cache->setPrimaryCache(false);
		$this->cache->init(null);
	}

	public function testIsAMemoryCache(): void
	{
		$this->assertInstanceOf(TMemoryCache::class, $this->cache);
		$this->assertTrue(TTestMemoryCache::getIsAvailable());
		$this->assertFalse($this->cache->pubGetSerializeValues());
	}

	public function testFakeClockOverrides(): void
	{
		$this->cache->fakeNow = 7000;
		$this->cache->fakeMicrotime = 12.25;
		$this->assertSame(7000, $this->cache->pubTime());
		$this->assertSame(12.25, $this->cache->pubMicrotime());
	}

	public function testStoreCoreRoundTrips(): void
	{
		$key = $this->cache->pubGenerateUniqueKey('k');
		$this->assertTrue($this->cache->pubWriteStore($key, 'payload', 0));
		$this->assertSame('payload', $this->cache->pubReadStore($key));
		$this->assertSame('payload', $this->cache->pubGetSerializedValue($key));
		$this->assertFalse($this->cache->pubAddStore($key, 'other', 0));
		$this->assertTrue($this->cache->pubAddStore($this->cache->pubGenerateUniqueKey('k2'), 'fresh', 0));
	}

	public function testStoreCoreExpiry(): void
	{
		$this->cache->fakeNow = 1_000_000;
		$key = $this->cache->pubGenerateUniqueKey('k');
		$this->cache->pubWriteStore($key, 'v', 10);
		$this->assertSame('v', $this->cache->pubReadStore($key));
		$this->cache->fakeNow = 1_000_011;
		$this->assertFalse($this->cache->pubReadStore($key));
	}

	public function testValueContractExposers(): void
	{
		$key = $this->cache->pubGenerateUniqueKey('vk');
		$this->assertTrue($this->cache->pubSetValue($key, 'rawpayload', 0));
		$this->assertSame('rawpayload', $this->cache->pubGetValue($key));
		$this->assertFalse($this->cache->pubAddValue($key, 'other', 0));
		$this->assertTrue($this->cache->pubDeleteValue($key));
		$this->assertFalse($this->cache->pubGetValue($key));
	}

	public function testStoreDirectExposers(): void
	{
		$this->cache->pubSetStore([]);
		$this->assertSame([], $this->cache->pubGetStore());
		$this->cache->pubSetStoreEntry('e', ['data' => 'd', 'expire' => 0]);
		$this->assertTrue($this->cache->pubHasStoreEntry('e'));
		$this->assertSame(['data' => 'd', 'expire' => 0], $this->cache->pubGetStoreEntry('e'));
		$this->cache->pubClearStoreEntry('e');
		$this->assertFalse($this->cache->pubHasStoreEntry('e'));
	}

	public function testSerializeHelperExposers(): void
	{
		$s = $this->cache->pubSerialize(['a' => 1]);
		$this->assertSame(['a' => 1], $this->cache->pubUnserialize($s));
	}

	public function testBackingFileSaveLoadRoundTrip(): void
	{
		$file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado_ttestmem_backing_' . getmypid() . '.bin';
		@unlink($file);
		// BackingFile is frozen after init(), so configure a fresh cache before init().
		$cache = new TTestMemoryCache();
		$cache->setPrimaryCache(false);
		$cache->setBackingFile($file);
		$cache->init(null);

		$store = ['k' => ['data' => 'v', 'expire' => 0]];
		$this->assertTrue($cache->pubSaveToBacking($store));
		$this->assertSame($store, $cache->pubLoadFromBacking());
		@unlink($file);
	}

	public function testFileSeamExclusiveLockParam(): void
	{
		$file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado_ttestmem_seam_' . getmypid() . '.bin';
		$this->assertNotFalse($this->cache->pubPutContents($file, 'x', true));
		$this->assertSame('x', $this->cache->pubGetContents($file));
		@unlink($file);
	}

	public function testSizeExposers(): void
	{
		$this->cache->pubSetCurrentSizeDirect(50);
		$this->assertSame(50, $this->cache->pubGetCurrentSizeDirect());
		$this->cache->pubSetSizeFingerprintDirect('fp');
		$this->assertSame('fp', $this->cache->pubGetSizeFingerprintDirect());
		$this->assertIsInt($this->cache->pubComputeCurrentSize());
	}

	public function testCustomConstantFixturesUseLateStaticBinding(): void
	{
		$ck = new TTestMemoryCacheCustomKey();
		$this->assertStringStartsWith('custom.key', $ck->getBackingCacheKey());

		$mp = new TTestMemoryCacheCustomMergePolicy();
		$this->assertSame(TMemoryCache::REPLACE, $mp->getMergePolicy());
	}
}
