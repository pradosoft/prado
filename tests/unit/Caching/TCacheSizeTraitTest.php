<?php

/**
 * TCacheSizeTraitTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\ICacheSize;

/**
 * Unit tests for {@see \Prado\Caching\TCacheSizeTrait}, exercised through the
 * {@see TTestFileCache} harness (which uses the trait). Covers size-string parsing,
 * the MaximumSize property, over-capacity detection, and the running-size accessors.
 */
class TCacheSizeTraitTest extends PHPUnit\Framework\TestCase
{
	private string $dir;

	protected function setUp(): void
	{
		$this->dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado_sizetrait_' . getmypid();
		@mkdir($this->dir, 0o755, true);
	}

	protected function tearDown(): void
	{
		foreach (glob($this->dir . DIRECTORY_SEPARATOR . '*') ?: [] as $f) {
			@unlink($f);
		}
		@rmdir($this->dir);
	}

	private function newCache(): TTestFileCache
	{
		$cache = new TTestFileCache($this->dir);
		$cache->setPrimaryCache(false);
		$cache->init(null);
		return $cache;
	}

	public function testMaximumSizeDefaultsToZero(): void
	{
		$this->assertSame(0, $this->newCache()->getMaximumSize());
	}

	/**
	 * @dataProvider sizeStringProvider
	 */
	public function testSetMaximumSizeParsesSizeStrings(int|string $input, int $expected): void
	{
		$cache = $this->newCache();
		$cache->setMaximumSize($input);
		$this->assertSame($expected, $cache->getMaximumSize());
	}

	public static function sizeStringProvider(): array
	{
		return [
			'plain int'       => [4_194_304, 4_194_304],
			'plain digits'    => ['4194304', 4_194_304],
			'K suffix'        => ['1K', 1_024],
			'M suffix'        => ['2M', 2_097_152],
			'G suffix'        => ['1G', 1_073_741_824],
			'KB suffix'       => ['256KB', 262_144],
			'plain B suffix'  => ['512B', 512],
			'lowercase b'     => ['512b', 512],
			'lowercase m'     => ['1m', 1_048_576],
			'whitespace'      => [' 512 K ', 524_288],
			'invalid → zero'  => ['not-a-size', 0],
			'negative → zero' => [-5, 0],
		];
	}

	public function testIsOverCapacity(): void
	{
		$cache = $this->newCache();
		$cache->setMaximumSize(100);
		$cache->pubSetCurrentSizeDirect(200);
		$this->assertTrue($cache->isOverCapacity());
		$cache->pubSetCurrentSizeDirect(50);
		$this->assertFalse($cache->isOverCapacity());
	}

	public function testIsOverCapacityFalseWhenUnlimited(): void
	{
		$cache = $this->newCache(); // MaximumSize 0 (unlimited)
		$cache->pubSetCurrentSizeDirect(1_000_000);
		$this->assertFalse($cache->isOverCapacity(),
			'An unlimited cache (MaximumSize=0) is never over capacity.');
	}

	public function testCurrentSizeReflectsStoredBytes(): void
	{
		$cache = $this->newCache();
		$cache->setMaximumSize(1_000_000);
		$this->assertSame(0, $cache->getCurrentSize());
		$cache->set('k', str_repeat('x', 100));
		$this->assertGreaterThan(0, $cache->getCurrentSize());
	}

	public function testSizeNotComputedConstant(): void
	{
		$this->assertSame(-1, ICacheSize::SIZE_NOT_COMPUTED);
	}
}
