<?php

/**
 * TTestIOHelperTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\IO\TStream;

/**
 * Tests for the {@see TTestIOHelper} harness — the resource/stream factories and
 * inspection helpers used across the IO unit tests (and any test touching streams).
 *
 * @package System.HarnessTests.IO
 */
class TTestIOHelperTest extends PHPUnit\Framework\TestCase
{
	protected function tearDown(): void
	{
		TTestIOHelper::removeTempFiles();
	}

	public function testRawResourceFactories(): void
	{
		$mem = TTestIOHelper::memoryResource();
		$this->assertTrue(is_resource($mem));
		$this->assertSame('php://memory', stream_get_meta_data($mem)['uri']);
		fclose($mem);

		$tmp = TTestIOHelper::tempResource();
		$this->assertTrue(is_resource($tmp));
		fclose($tmp);
	}

	public function testDataResourceIsSeededAndRewound(): void
	{
		$r = TTestIOHelper::dataResource('hello');
		$this->assertSame(0, ftell($r));
		$this->assertSame('hello', stream_get_contents($r));
		fclose($r);
	}

	public function testContentsReadsStreamAndResource(): void
	{
		$this->assertSame('abc', TTestIOHelper::contents(TTestIOHelper::dataResource('abc')));
		$this->assertSame('xyz', TTestIOHelper::contents(TTestIOHelper::dataStream('xyz')));
	}

	public function testPipeResourceIsNonSeekableAndYieldsData(): void
	{
		$pipe = TTestIOHelper::pipeResource('piped-bytes');
		$this->assertTrue(is_resource($pipe));
		$this->assertFalse(stream_get_meta_data($pipe)['seekable']);
		$this->assertSame('piped-bytes', stream_get_contents($pipe));
		TTestIOHelper::closeAny($pipe);
	}

	public function testStreamFactories(): void
	{
		$mem = TTestIOHelper::memoryStream();
		$this->assertInstanceOf(TStream::class, $mem);
		$mem->close();

		$data = TTestIOHelper::dataStream('seed');
		$this->assertSame('seed', TTestIOHelper::contents($data));
		$data->close();

		$res = TTestIOHelper::memoryResource();
		$stream = TTestIOHelper::resourceStream($res, true);
		$this->assertInstanceOf(TStream::class, $stream);
		$stream->close();
	}

	public function testTempFileCreatesSeedsAndRemoves(): void
	{
		$path = TTestIOHelper::tempFile('content', 'iotest');
		$this->assertFileExists($path);
		$this->assertSame('content', file_get_contents($path));
		TTestIOHelper::removeTempFiles();
		$this->assertFileDoesNotExist($path);
	}

	public function testCloseAnyHandlesPipeAndResource(): void
	{
		$res = TTestIOHelper::memoryResource();
		TTestIOHelper::closeAny($res);
		$this->assertFalse(is_resource($res));

		$pipe = TTestIOHelper::pipeResource('x');
		TTestIOHelper::closeAny($pipe);
		$this->assertFalse(is_resource($pipe));
	}
}
