<?php

/**
 * TTestDirectoryCacheDependencyTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\TDirectoryCacheDependency;

/**
 * Tests for {@see TTestDirectoryCacheDependency}: directory `*Direct` accessor,
 * validation, and timestamp-generation exposers.
 *
 * @package System.Harness.Caching
 */
class TTestDirectoryCacheDependencyTest extends PHPUnit\Framework\TestCase
{
	private string $dir;

	protected function setUp(): void
	{
		$this->dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado_ttestdirdep_' . getmypid();
		@mkdir($this->dir, 0o755, true);
		file_put_contents($this->dir . DIRECTORY_SEPARATOR . 'a.txt', 'a');
	}

	protected function tearDown(): void
	{
		foreach (glob($this->dir . DIRECTORY_SEPARATOR . '*') ?: [] as $f) {
			@unlink($f);
		}
		@rmdir($this->dir);
	}

	private function newDependency(): TTestDirectoryCacheDependency
	{
		return new TTestDirectoryCacheDependency($this->dir);
	}

	public function testIsADirectoryCacheDependency(): void
	{
		$this->assertInstanceOf(TDirectoryCacheDependency::class, $this->newDependency());
	}

	public function testDirectoryDirectAccessor(): void
	{
		$dep = $this->newDependency();
		$this->assertNotNull($dep->pubGetDirectoryDirect());
		$dep->pubSetDirectoryDirect(null);
		$this->assertNull($dep->pubGetDirectoryDirect());
	}

	public function testGenerateTimestampsIncludesFile(): void
	{
		$dep = $this->newDependency();
		$timestamps = $dep->pubGenerateTimestamps($this->dir);
		$this->assertIsArray($timestamps);
		$this->assertArrayHasKey($this->dir . DIRECTORY_SEPARATOR . 'a.txt', $timestamps);
	}

	public function testValidateExposersReturnBool(): void
	{
		$dep = $this->newDependency();
		$this->assertIsBool($dep->pubValidateDirectory($this->dir));
		$this->assertIsBool($dep->pubValidateFile($this->dir . DIRECTORY_SEPARATOR . 'a.txt'));
	}
}
