<?php

/**
 * TTestFileCacheTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\TFileCache;
use Prado\Exceptions\TConfigurationException;

/**
 * Tests for {@see TTestFileCache} (and the hash fixtures), verifying the harness exposers,
 * the fakeable clock, and the {@see TTestFileCache::$hashTokenCallback} injection.
 *
 * @package System.Harness.Caching
 */
class TTestFileCacheTest extends PHPUnit\Framework\TestCase
{
	private string $dir;
	private TTestFileCache $cache;

	protected function setUp(): void
	{
		$this->dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado_ttestfilecache_' . getmypid();
		@mkdir($this->dir, 0o755, true);
		$this->cache = new TTestFileCache($this->dir);
		$this->cache->setPrimaryCache(false);
		$this->cache->init(null);
	}

	protected function tearDown(): void
	{
		$this->cache->flush();
		foreach (glob($this->dir . DIRECTORY_SEPARATOR . '*') ?: [] as $f) {
			@unlink($f);
		}
		@rmdir($this->dir);
	}

	public function testIsAFileCache(): void
	{
		$this->assertInstanceOf(TFileCache::class, $this->cache);
		$this->assertTrue(TTestFileCache::getIsAvailable());
	}

	public function testFakeClockOverrides(): void
	{
		$this->cache->fakeNow = 4242;
		$this->cache->fakeMicrotime = 99.5;
		$this->assertSame(4242, $this->cache->pubTime());
		$this->assertSame(99.5, $this->cache->pubMicrotime());
	}

	public function testHashTokenCallbackOverridesHashing(): void
	{
		$this->assertSame(sha1('tok'), $this->cache->pubHashToken('tok'));
		$this->cache->hashTokenCallback = static fn (string $t): string => 'X' . strlen($t);
		$this->assertSame('X3', $this->cache->pubHashToken('tok'));
	}

	public function testFileSeamRoundTrips(): void
	{
		$file = $this->dir . DIRECTORY_SEPARATOR . 'seam.tmp';
		$this->assertNotFalse($this->cache->pubPutContents($file, 'hello'));
		$this->assertTrue($this->cache->pubIsFile($file));
		$this->assertSame('hello', $this->cache->pubGetContents($file));
		$dst = $file . '.moved';
		$this->assertTrue($this->cache->pubRename($file, $dst));
		$this->assertTrue($this->cache->pubChmod($dst, 0o600));
		$this->assertTrue($this->cache->pubTouch($dst));
		$this->assertTrue($this->cache->pubUnlink($dst));
		$this->assertFalse($this->cache->pubIsFile($dst));
	}

	public function testTempnamCreatesFileInDir(): void
	{
		$dir = $this->cache->getDirectory(); // realpath-resolved (handles /tmp → /private/tmp)
		$tmp = $this->cache->pubTempnam($dir, 'pfx-');
		$this->assertNotFalse($tmp);
		$this->assertStringStartsWith($dir, $tmp);
		@unlink($tmp);
	}

	public function testSerializedContractExposers(): void
	{
		$key = $this->cache->pubGenerateUniqueKey('k');
		$this->assertTrue($this->cache->pubSetSerializedValue($key, 'payload', 0));
		$this->assertSame('payload', $this->cache->pubGetSerializedValue($key));
		// add fails when present, succeeds when absent
		$this->assertFalse($this->cache->pubAddSerializedValue($key, 'other', 0));
		$key2 = $this->cache->pubGenerateUniqueKey('k2');
		$this->assertTrue($this->cache->pubAddSerializedValue($key2, 'fresh', 0));
	}

	public function testPathForUsesCacheDirectory(): void
	{
		$path = $this->cache->pubPathFor('abc');
		$this->assertStringStartsWith($this->cache->getDirectory(), $path);
		$this->assertStringEndsWith('.cache', $path);
	}

	public function testSizeDirectExposers(): void
	{
		$this->cache->pubSetCurrentSizeDirect(123);
		$this->assertSame(123, $this->cache->pubGetCurrentSizeDirect());
		$this->cache->pubSetSizeFingerprintDirect('fp');
		$this->assertSame('fp', $this->cache->pubGetSizeFingerprintDirect());
		$this->assertIsInt($this->cache->pubComputeCurrentSize());
		$this->assertIsString($this->cache->pubComputeSizeFingerprint());
	}

	public function testIdentityHashCallbackRejectedByInit(): void
	{
		$cache = new TTestFileCache($this->dir);
		$cache->setPrimaryCache(false);
		$cache->hashTokenCallback = static fn (string $token): string => $token; // identity
		$this->expectException(TConfigurationException::class);
		$cache->init(null);
	}

	public function testSlashHashCallbackRejectedByInit(): void
	{
		$cache = new TTestFileCache($this->dir);
		$cache->setPrimaryCache(false);
		$cache->hashTokenCallback = static fn (string $token): string => 'sub/dir/' . sha1($token);
		$this->expectException(TConfigurationException::class);
		$cache->init(null);
	}

	public function testBackslashHashCallbackRejectedByInit(): void
	{
		$cache = new TTestFileCache($this->dir);
		$cache->setPrimaryCache(false);
		$cache->hashTokenCallback = static fn (string $token): string => 'sub\\dir\\' . sha1($token);
		$this->expectException(TConfigurationException::class);
		$cache->init(null);
	}
}
