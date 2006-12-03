<?php

require_once(dirname(__FILE__).'/../phpunit2.php');

/**
 * @package System.Caching
 */
class TFileCacheDependencyTest extends PHPUnit2_Framework_TestCase
{
	public function setUp()
	{
	}

	public function tearDown()
	{
	}

	public function testFileName()
	{
		$dependency=new TFileCacheDependency(__FILE__);
		$this->assertEquals($dependency->getFileName(),__FILE__);
		$this->assertEquals($dependency->getTimestamp(),filemtime(__FILE__));

		$dependency=new TFileCacheDependency(dirname(__FILE__).'/foo.txt');
		$this->assertFalse($dependency->getTimestamp());
	}

	public function testHasChanged()
	{
		$tempFile=dirname(__FILE__).'/temp/foo.txt';
		@unlink($tempFile);
		$fw=fopen($tempFile,"w");
		fwrite($fw,"test");
		fclose($fw);
		clearstatcache();

		$dependency=new TFileCacheDependency($tempFile);
		$str=serialize($dependency);

		// test file not changed
		sleep(2);
		$dependency=unserialize($str);
		$this->assertFalse($dependency->getHasChanged());

		// change file
		$fw=fopen($tempFile,"w");
		fwrite($fw,"test again");
		fclose($fw);
		clearstatcache();

		// test file changed
		sleep(2);
		$dependency=unserialize($str);
		$this->assertTrue($dependency->getHasChanged());

		@unlink($tempFile);
	}
}

?>