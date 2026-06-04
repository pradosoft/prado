<?php

/**
 * TTestPsrStreamTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Psr\Http\Message\StreamInterface;

/**
 * Tests for the {@see TTestPsrStream} harness, the dependency-free PSR-7 stream double.
 * Pins the StreamInterface contract that consumer tests rely on.
 */
class TTestPsrStreamTest extends PHPUnit\Framework\TestCase
{
	public function testIsStreamInterface(): void
	{
		$this->assertInstanceOf(StreamInterface::class, new TTestPsrStream());
	}

	public function testReadSeekTellEof(): void
	{
		$s = new TTestPsrStream('hello');
		$this->assertSame(5, $s->getSize());
		$this->assertSame('he', $s->read(2));
		$this->assertSame(2, $s->tell());
		$this->assertFalse($s->eof());
		$this->assertSame('llo', $s->getContents());
		$this->assertTrue($s->eof());
		$s->rewind();
		$this->assertSame(0, $s->tell());
		$this->assertSame('hello', (string) $s);
	}

	public function testWriteGrowsBuffer(): void
	{
		$s = new TTestPsrStream();
		$this->assertSame(3, $s->write('abc'));
		$this->assertSame(3, $s->write('def'));
		$this->assertSame(6, $s->getSize());
		$s->rewind();
		$this->assertSame('abcdef', $s->getContents());
	}

	public function testSeekWhence(): void
	{
		$s = new TTestPsrStream('0123456789');
		$s->seek(3);
		$this->assertSame('3', $s->read(1));
		$s->seek(2, SEEK_CUR);
		$this->assertSame('6', $s->read(1));
		$s->seek(-1, SEEK_END);
		$this->assertSame('9', $s->read(1));
	}

	public function testCapabilityFlagsAndThrows(): void
	{
		$ro = new TTestPsrStream('x', true, false, true);
		$this->assertTrue($ro->isReadable());
		$this->assertFalse($ro->isWritable());
		$this->expectException(\RuntimeException::class);
		$ro->write('nope');
	}

	public function testNonReadableThrowsOnRead(): void
	{
		$wo = new TTestPsrStream('', false, true, true);
		$this->assertFalse($wo->isReadable());
		$this->expectException(\RuntimeException::class);
		$wo->read(1);
	}

	public function testNonSeekableThrowsOnSeek(): void
	{
		$ns = new TTestPsrStream('abc', true, true, false);
		$this->assertFalse($ns->isSeekable());
		$this->expectException(\RuntimeException::class);
		$ns->seek(0);
	}

	public function testDetachAndClose(): void
	{
		$s = new TTestPsrStream('data');
		$this->assertNull($s->detach());
		$this->assertNull($s->getSize());
		$this->assertSame('', (string) $s);

		$s2 = new TTestPsrStream('more');
		$s2->close();
		$this->assertTrue($s2->eof());
	}

	public function testMetadata(): void
	{
		$s = new TTestPsrStream('abc');
		$this->assertIsArray($s->getMetadata());
		$this->assertTrue($s->getMetadata('seekable'));
		$this->assertSame('r+b', $s->getMetadata('mode'));
		$this->assertNull($s->getMetadata('no_such_key'));
	}
}
