<?php

/**
 * TTestSerializingCacheTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\TSerializingCache;

/**
 * Tests for {@see TTestSerializingCache}, the array-backed {@see TSerializingCache} harness.
 *
 * Verifies the serialized-string storage contract, the fakeable clock, and that the
 * pipeline exposers ({@see TTestSerializingCache::pubSerializeValue()} etc.) reach their
 * protected targets, including encode/decode round-trips.
 *
 * @package System.Harness.Caching
 */
class TTestSerializingCacheTest extends PHPUnit\Framework\TestCase
{
	private function newCache(): TTestSerializingCache
	{
		$cache = new TTestSerializingCache();
		$cache->setPrimaryCache(false);
		$cache->init(null);
		return $cache;
	}

	public function testIsASerializingCache(): void
	{
		$this->assertInstanceOf(TSerializingCache::class, new TTestSerializingCache());
		$this->assertTrue(TTestSerializingCache::getIsAvailable());
	}

	public function testRoundTripThroughPipeline(): void
	{
		$cache = $this->newCache();
		$cache->set('k', ['a' => 1, 'b' => [2, 3]]);
		$this->assertSame(['a' => 1, 'b' => [2, 3]], $cache->get('k'));
		// The persisted payload is the serialized string, inspectable via onlyStored().
		$this->assertSame(serialize([['a' => 1, 'b' => [2, 3]], null]), $cache->onlyStored());
	}

	public function testSerializeUnserializeExposers(): void
	{
		$cache = $this->newCache();
		$s = $cache->pubSerializeValue(['x' => 1]);
		$this->assertSame(['x' => 1], $cache->pubUnserializeValue($s));
	}

	public function testEncodeDecodeRoundTrips(): void
	{
		// Encoding is frozen after init(), so use an un-initialized cache to vary it.
		$cache = new TTestSerializingCache();
		$cache->setPrimaryCache(false);

		$cache->setEncoding(TSerializingCache::ENCODING_BASE64);
		$this->assertSame(base64_encode('raw'), $cache->pubEncode('raw'));
		$this->assertSame('raw', $cache->pubDecode(base64_encode('raw')));
		$this->assertFalse($cache->pubDecode('@@@not-base64@@@'));

		$cache->setEncoding(TSerializingCache::ENCODING_HEX);
		$this->assertSame(bin2hex('raw'), $cache->pubEncode('raw'));
		$this->assertSame('raw', $cache->pubDecode(bin2hex('raw')));
		$this->assertFalse($cache->pubDecode('xyz')); // odd-length / non-hex

		$cache->setEncoding(TSerializingCache::ENCODING_NONE);
		$this->assertSame('raw', $cache->pubEncode('raw'));
		$this->assertSame('raw', $cache->pubDecode('raw'));
	}

	public function testFakeNowDrivesExpiry(): void
	{
		$cache = $this->newCache();
		$cache->fakeNow = 1_000_000;
		$cache->set('k', 'v', 10);
		$this->assertSame('v', $cache->get('k'));
		$cache->fakeNow = 1_000_011;
		$this->assertFalse($cache->get('k'));
		$this->assertFalse($cache->pubGetSerializedValue('k'));
	}
}
