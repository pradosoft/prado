<?php

require_once(dirname(__FILE__).'/../phpunit2.php');

/**
 * @package System.Caching
 */
class TDirectoryCacheDependencyTest extends PHPUnit2_Framework_TestCase
{
	public function setUp()
	{
	}

	public function tearDown()
	{
	}

	public function testDirectoryName()
	{
		$directory=realpath(dirname(__FILE__).'/temp');
		$dependency=new TDirectoryCacheDependency(dirname(__FILE__).'/temp');
		$this->assertEquals($dependency->getDirectory(),$directory);

		try
		{
			$dependency=new TDirectoryCacheDependency(dirname(__FILE__).'/temp2');
			$this->fail("Expected exception is not raised");
		}
		catch(TInvalidDataValueException $e)
		{
		}
	}

	public function testRecursiveCheck()
	{
		$directory=realpath(dirname(__FILE__).'/temp');
		$dependency=new TDirectoryCacheDependency(dirname(__FILE__).'/temp');
		$this->assertTrue($dependency->getRecursiveCheck());
		$dependency->setRecursiveCheck(false);
		$this->assertFalse($dependency->getRecursiveCheck());
	}

	public function testRecursiveLevel()
	{
		$directory=realpath(dirname(__FILE__).'/temp');
		$dependency=new TDirectoryCacheDependency(dirname(__FILE__).'/temp');
		$this->assertEquals($dependency->getRecursiveLevel(),-1);
		$dependency->setRecursiveLevel(5);
		$this->assertEquals($dependency->getRecursiveLevel(),5);
	}

	public function testHasChanged()
	{
		$tempFile=dirname(__FILE__).'/temp/foo.txt';
		@unlink($tempFile);
		$fw=fopen($tempFile,"w");
		fwrite($fw,"test");
		fclose($fw);
		clearstatcache();

		$dependency=new TDirectoryCacheDependency(dirname($tempFile));
		$str=serialize($dependency);

		// test directory not changed
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