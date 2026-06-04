<?php

/**
 * TTestFileCacheDependencyTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\TFileCacheDependency;

/**
 * Tests for {@see TTestFileCacheDependency}: the file-name and timestamp `*Direct` seams.
 *
 * @package System.Harness.Caching
 */
class TTestFileCacheDependencyTest extends PHPUnit\Framework\TestCase
{
	private string $file;

	protected function setUp(): void
	{
		$this->file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado_ttestfiledep_' . getmypid() . '.txt';
		file_put_contents($this->file, 'x');
	}

	protected function tearDown(): void
	{
		@unlink($this->file);
	}

	public function testIsAFileCacheDependency(): void
	{
		$this->assertInstanceOf(TFileCacheDependency::class, new TTestFileCacheDependency($this->file));
	}

	public function testSetFileNameDirect(): void
	{
		$dep = new TTestFileCacheDependency($this->file);
		$dep->pubSetFileNameDirect('/some/other/path');
		$this->assertSame('/some/other/path', $dep->getFileName());
	}

	public function testSetTimestamp(): void
	{
		$dep = new TTestFileCacheDependency($this->file);
		$dep->pubSetTimestamp(123456);
		$this->assertSame(123456, $dep->getTimestamp());
	}
}
