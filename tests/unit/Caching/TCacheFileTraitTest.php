<?php

/**
 * TCacheFileTraitTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

/**
 * Unit tests for {@see \Prado\Caching\TCacheFileTrait}, exercised through the
 * {@see TTestFileCache} harness (which uses the trait). Covers the filesystem read/write
 * seams, including the optional exclusive-lock write parameter.
 */
class TCacheFileTraitTest extends PHPUnit\Framework\TestCase
{
	private string $dir;
	private TTestFileCache $cache;

	protected function setUp(): void
	{
		$this->dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado_filetrait_' . getmypid();
		@mkdir($this->dir, 0o755, true);
		$this->cache = new TTestFileCache($this->dir);
		$this->cache->setPrimaryCache(false);
		$this->cache->init(null);
	}

	protected function tearDown(): void
	{
		foreach (glob($this->dir . DIRECTORY_SEPARATOR . '*') ?: [] as $f) {
			@unlink($f);
		}
		@rmdir($this->dir);
	}

	public function testPutAndGetContentsRoundTrip(): void
	{
		$file = $this->dir . DIRECTORY_SEPARATOR . 'rt.bin';
		$this->assertSame(5, $this->cache->pubPutContents($file, 'hello'));
		$this->assertSame('hello', $this->cache->pubGetContents($file));
	}

	public function testPutContentsWithExclusiveLock(): void
	{
		$file = $this->dir . DIRECTORY_SEPARATOR . 'locked.bin';
		$this->assertNotFalse($this->cache->pubPutContents($file, 'data', true));
		$this->assertSame('data', $this->cache->pubGetContents($file));
	}

	public function testGetContentsReturnsFalseForMissingFile(): void
	{
		$missing = $this->dir . DIRECTORY_SEPARATOR . 'does_not_exist_' . uniqid() . '.bin';
		$this->assertFalse($this->cache->pubGetContents($missing));
	}
}
