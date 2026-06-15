<?php

use Prado\IO\TStream;
use Psr\Http\Message\StreamInterface;

/**
 * Unit tests for {@see \Prado\IO\Stream\TStreamDecorator} via a bare concrete subclass.
 * The decorator's forwarding contract is verified here once; its concrete children
 * (TBinaryStream, TLimitStream, TCachingStream, …) only test what they override.
 */
class TStreamDecoratorTest extends PHPUnit\Framework\TestCase
{
	private function decorate(string $contents = 'hello world'): TTestStreamDecorator
	{
		return new TTestStreamDecorator(TStream::fromString($contents), null);
	}

	public function testIsStreamInterfaceAndExposesInner()
	{
		$inner = TStream::fromString('abc');
		$d = new TTestStreamDecorator($inner);
		self::assertInstanceOf(StreamInterface::class, $d);
		self::assertSame($inner, $d->getStream());
		$d->close();
	}

	public function testForwardsReadPositionAndCapabilities()
	{
		$d = $this->decorate('hello world');
		self::assertTrue($d->isReadable());
		self::assertTrue($d->isWritable());
		self::assertTrue($d->isSeekable());
		self::assertSame(11, $d->getSize());
		self::assertFalse($d->eof());
		self::assertSame('hello', $d->read(5));
		self::assertSame(5, $d->tell());
		self::assertSame(' world', $d->getContents());
		self::assertTrue($d->eof());
		$d->close();
	}

	public function testForwardsSeekAndRewind()
	{
		$d = $this->decorate('0123456789');
		$d->seek(4);
		self::assertSame(4, $d->tell());
		self::assertSame('4', $d->read(1));
		$d->seek(2, SEEK_CUR);
		self::assertSame('7', $d->read(1));
		$d->rewind();
		self::assertSame(0, $d->tell());
		$d->close();
	}

	public function testForwardsWrite()
	{
		$d = new TTestStreamDecorator(TStream::fromMemory());
		self::assertSame(3, $d->write('xyz'));
		$d->rewind();
		self::assertSame('xyz', $d->getContents());
		$d->close();
	}

	public function testForwardsMetadata()
	{
		$d = $this->decorate('m');
		self::assertIsArray($d->getMetadata());
		self::assertTrue((bool) $d->getMetadata('seekable'));
		self::assertNull($d->getMetadata('no_such_key'));
		$d->close();
	}

	public function testToStringRewindsAndReturnsFullContents()
	{
		$d = $this->decorate('abcdef');
		$d->read(3);                              // advance the cursor
		self::assertSame('abcdef', (string) $d, '__toString rewinds a seekable stream.');
		$d->close();
	}

	public function testDetachForwardsAndLeavesInnerDetached()
	{
		$inner = TStream::fromString('x');
		$d = new TTestStreamDecorator($inner);
		$resource = $d->detach();
		self::assertTrue(is_resource($resource));
		self::assertNull($inner->detach(), 'The inner stream no longer holds the resource.');
		fclose($resource);
	}

	public function testCloseForwardsToInner()
	{
		$inner = TStream::fromString('y');
		$d = new TTestStreamDecorator($inner);
		$d->close();
		self::assertFalse($inner->isReadable(), 'Closing the decorator closes the inner stream.');
	}

	public function testLazyGetStreamOverrideIsHonored()
	{
		// A null-constructed decorator that builds its inner lazily via getStream().
		$d = new TTestStreamDecorator(null, 'lazy-built');
		self::assertSame(0, $d->builds);
		self::assertSame('lazy', $d->read(4), 'Forwarding pulls the inner stream from the override.');
		self::assertSame(1, $d->builds);
		self::assertSame('-built', $d->getContents());
		self::assertSame(1, $d->builds, 'The inner stream is built once and reused.');
	}

	public function testNullInnerWithoutOverrideThrows()
	{
		// Constructing with null without overriding getStream() is a programming error:
		// the first forwarded call hits the uninitialized inner stream.
		$d = new TTestStreamDecorator();
		$this->expectException(\Error::class);
		$d->read(1);
	}
}
