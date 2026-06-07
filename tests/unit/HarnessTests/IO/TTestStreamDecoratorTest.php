<?php

use Prado\IO\Stream\TStreamDecorator;
use Prado\IO\TStream;
use Psr\Http\Message\StreamInterface;

/**
 * Tests for the {@see TTestStreamDecorator} harness double itself.
 */
class TTestStreamDecoratorTest extends PHPUnit\Framework\TestCase
{
	public function testIsAStreamDecorator()
	{
		$d = new TTestStreamDecorator(TStream::fromString('x'));
		self::assertInstanceOf(TStreamDecorator::class, $d);
		self::assertInstanceOf(StreamInterface::class, $d);
		$d->close();
	}

	public function testEagerModeForwardsToInner()
	{
		$inner = TStream::fromString('payload');
		$d = new TTestStreamDecorator($inner);
		self::assertSame($inner, $d->getStream(), 'Eager mode exposes the given inner stream.');
		self::assertSame('payload', (string) $d);
		self::assertSame(0, $d->builds, 'Eager mode never builds a lazy inner stream.');
		$d->close();
	}

	public function testLazyModeBuildsOnceOnFirstUse()
	{
		$d = new TTestStreamDecorator(null, 'lazy-built');
		self::assertSame(0, $d->builds, 'The inner stream is not built until first access.');
		self::assertSame('lazy', $d->read(4));
		self::assertSame(1, $d->builds);
		self::assertSame('-built', $d->getContents());
		self::assertSame(1, $d->builds, 'The lazy inner stream is built once and reused.');
	}

	public function testBareModeWithoutInnerThrowsOnUse()
	{
		// No inner stream and no lazy contents: the first forwarded call hits the
		// uninitialized inner stream, per the decorator contract.
		$d = new TTestStreamDecorator();
		$this->expectException(\Error::class);
		$d->read(1);
	}
}
