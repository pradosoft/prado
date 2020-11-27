<?php

use Prado\Caching\TDirectoryCacheDependency;
use Prado\Exceptions\TInvalidDataValueException;

class TDirectoryCacheDependencyTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
	}

	protected function tearDown(): void
	{
	}

	public function testDirectoryName()
	{
		$directory = realpath(__DIR__ . '/temp');
		$dependency = new TDirectoryCacheDependency(__DIR__ . '/temp');
		$this->assertEquals($dependency->getDirectory(), $directory);

		try {
			$dependency = new TDirectoryCacheDependency(__DIR__ . '/temp2');
			$this->fail("Expected exception is not raised");
		} catch (TInvalidDataValueException $e) {
		}
	}

	public function testRecursiveCheck()
	{
		$directory = realpath(__DIR__ . '/temp');
		$dependency = new TDirectoryCacheDependency(__DIR__ . '/temp');
		$this->assertTrue($dependency->getRecursiveCheck());
		$dependency->setRecursiveCheck(false);
		$this->assertFalse($dependency->getRecursiveCheck());
	}

	public function testRecursiveLevel()
	{
		$directory = realpath(__DIR__ . '/temp');
		$dependency = new TDirectoryCacheDependency(__DIR__ . '/temp');
		$this->assertEquals($dependency->getRecursiveLevel(), -1);
		$dependency->setRecursiveLevel(5);
		$this->assertEquals($dependency->getRecursiveLevel(), 5);
	}

	public function testHasChanged()
	{
		$tempFile = __DIR__ . '/temp/foo.txt';
		@unlink($tempFile);
		$fw = fopen($tempFile, "w");
		fwrite($fw, "test");
		fclose($fw);
		clearstatcache();

		$dependency = new TDirectoryCacheDependency(dirname($tempFile));
		$str = serialize($dependency);

		// test directory not changed
		sleep(2);
		$dependency = unserialize($str);
		$this->assertFalse($dependency->getHasChanged());

		// change file
		$fw = fopen($tempFile, "w");
		fwrite($fw, "test again");
		fclose($fw);
		clearstatcache();

		// test file changed
		sleep(2);
		$dependency = unserialize($str);
		$this->assertTrue($dependency->getHasChanged());

		@unlink($tempFile);
	}
}
