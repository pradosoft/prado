<?php

use Prado\Caching\TFileCacheDependency;

class TFileCacheDependencyTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
	}

	protected function tearDown(): void
	{
	}

	public function testFileName()
	{
		$dependency = new TFileCacheDependency(__FILE__);
		$this->assertEquals($dependency->getFileName(), __FILE__);
		$this->assertEquals($dependency->getTimestamp(), filemtime(__FILE__));

		$dependency = new TFileCacheDependency(__DIR__ . '/foo.txt');
		$this->assertFalse($dependency->getTimestamp());
	}

	public function testHasChanged()
	{
		$tempFile = __DIR__ . '/temp/foo.txt';
		@unlink($tempFile);
		$fw = fopen($tempFile, "w");
		fwrite($fw, "test");
		fclose($fw);
		clearstatcache();

		$dependency = new TFileCacheDependency($tempFile);
		$str = serialize($dependency);

		// test file not changed
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
