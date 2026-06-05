<?php

/**
 * TTestStreamTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\IO\TStream;
use Psr\Http\Message\StreamInterface;

/**
 * Tests for the {@see TTestStream} harness, the instrumented TStream used to exercise
 * the capability override seam. Pins the force-flag and counter contract that the
 * TStream unit tests depend on.
 *
 */
class TTestStreamTest extends PHPUnit\Framework\TestCase
{
	protected function tearDown(): void
	{
		TTestIOHelper::removeTempFiles();
	}

	private function readWriteSeekStream(): TTestStream
	{
		return new TTestStream(TTestIOHelper::memoryResource());   // php://memory r+b
	}

	public function testIsConcreteTStream(): void
	{
		$s = $this->readWriteSeekStream();
		$this->assertInstanceOf(TStream::class, $s);
		$this->assertInstanceOf(StreamInterface::class, $s);
		$this->assertTrue($s->isReadable() && $s->isWritable() && $s->isSeekable());
		$s->close();
	}

	public function testForceSeekableOverridesEveryCapabilitySurface(): void
	{
		$s = $this->readWriteSeekStream();
		$this->assertTrue($s->rawSeekable(), 'The underlying handle is really seekable.');
		$s->forceSeekable = false;
		$this->assertFalse($s->isSeekable(), 'PSR isSeekable() honors the override.');
		$this->assertTrue($s->rawSeekable(), 'The raw stored flag is unchanged.');
		$s->close();
	}

	public function testForceReadableAndWritableOverride(): void
	{
		$s = $this->readWriteSeekStream();
		$s->forceReadable = false;
		$s->forceWritable = false;
		$this->assertFalse($s->isReadable());
		$this->assertFalse($s->isWritable());
		$this->assertTrue($s->rawReadable());
		$this->assertTrue($s->rawWritable());
		$s->close();
	}

	public function testCallCountersIncrement(): void
	{
		$s = $this->readWriteSeekStream();
		$this->assertSame(0, $s->writeCalls);
		$this->assertSame(0, $s->readCalls);
		$s->write('abc');
		$s->seek(0);
		$s->read(3);
		$this->assertSame(1, $s->writeCalls);
		$this->assertSame(1, $s->readCalls);
		$s->close();
	}
}
