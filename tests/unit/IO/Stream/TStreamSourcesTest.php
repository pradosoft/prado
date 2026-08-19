<?php

use Prado\IO\Stream\TAppendStream;
use Prado\IO\Stream\TBufferStream;
use Prado\IO\Stream\TDroppingStream;
use Prado\IO\Stream\TFnStream;
use Prado\IO\Stream\TLazyOpenStream;
use Prado\IO\Stream\TPumpStream;
use Prado\IO\TStream;

class TStreamSourcesTest extends PHPUnit\Framework\TestCase
{
	// ---- TFnStream ----

	public function testFnStreamDelegatesAndDefaults()
	{
		$s = new TFnStream([
			'read' => fn (int $n) => substr('hello world', 0, $n),
			'eof' => fn () => false,
			'isReadable' => fn () => true,
		]);
		self::assertTrue($s->isReadable());
		self::assertFalse($s->isWritable());      // default
		self::assertSame('hello', $s->read(5));
		self::assertNull($s->getSize());          // observer default
		self::assertSame(0, $s->tell());          // observer default
		self::assertSame([], $s->getMetadata());  // observer default
		self::assertNull($s->getMetadata('uri'));
	}

	public function testFnStreamTransferMethodsThrowWithoutTheirClosure()
	{
		$s = new TFnStream([]);
		foreach (['seek' => fn () => $s->seek(0), 'write' => fn () => $s->write('x'), 'read' => fn () => $s->read(1), 'getContents' => fn () => $s->getContents()] as $method => $call) {
			try {
				$call();
				self::fail("{$method} without a closure must throw.");
			} catch (\RuntimeException $e) {
				self::assertStringContainsString($method, $e->getMessage());
			}
		}
	}

	public function testFnStreamRewindFallsBackToSeekZero()
	{
		$seeks = [];
		$s = new TFnStream(['seek' => function (int $offset, int $whence) use (&$seeks) {
			$seeks[] = [$offset, $whence];
		}]);
		$s->rewind();
		self::assertSame([[0, SEEK_SET]], $seeks, 'rewind without its closure calls seek(0).');

		self::expectException(\RuntimeException::class);
		(new TFnStream([]))->rewind();   // neither rewind nor seek: the fallback throws
	}

	public function testFnStreamToStringSwallowsAClosureException()
	{
		$s = new TFnStream(['__toString' => function () {
			throw new \DomainException('boom');
		}]);
		self::assertSame('', (string) $s, 'PSR-7 forbids __toString to throw.');
	}

	public function testFnStreamRefusesUnserialization()
	{
		// A string callable is serializable, so a crafted payload could otherwise choose
		// what runs when the stream is used.
		$payload = serialize(new TFnStream(['read' => 'strtoupper']));
		self::expectException(\LogicException::class);
		unserialize($payload);
	}

	// ---- TBufferStream ----

	public function testBufferStreamFifo()
	{
		$b = new TBufferStream();
		self::assertTrue($b->eof());
		self::assertSame(5, $b->write('hello'));
		$b->write(' world');
		self::assertSame(11, $b->getSize());
		self::assertSame('hel', $b->read(3));
		self::assertSame('lo wor', $b->read(6));
		self::assertSame('ld', $b->getContents());
		self::assertTrue($b->eof());
	}

	public function testBufferStreamNotSeekable()
	{
		$b = new TBufferStream();
		self::assertFalse($b->isSeekable());
		self::expectException(\RuntimeException::class);
		$b->seek(0);
	}

	// ---- TPumpStream ----

	public function testPumpStreamProducesOnDemand()
	{
		$n = 0;
		$pump = new TPumpStream(function () use (&$n) {
			return $n++ < 3 ? "line{$n}\n" : '';
		});
		self::assertTrue($pump->isReadable());
		self::assertSame("line1\nline2\nline3\n", $pump->getContents());
		self::assertTrue($pump->eof());
	}

	public function testPumpStreamPartialRead()
	{
		$pump = new TPumpStream(fn (int $len) => str_repeat('a', $len), 100);
		self::assertSame(100, $pump->getSize());
		self::assertSame('aaaa', $pump->read(4));
		self::assertSame(4, $pump->tell());
	}

	public function testPumpStreamNotWritable()
	{
		$pump = new TPumpStream(fn () => '');
		self::expectException(\RuntimeException::class);
		$pump->write('x');
	}

	public function testPumpStreamBuffersOverDelivery()
	{
		$calls = 0;
		$pump = new TPumpStream(function () use (&$calls) {
			$calls++;
			return 'abcdefghij';   // ten bytes however few were asked for
		});
		self::assertSame('abc', $pump->read(3));
		self::assertSame('def', $pump->read(3));
		self::assertSame(1, $calls, 'The second read serves from the buffered surplus.');
		self::assertSame(6, $pump->tell());
	}

	public function testPumpStreamExhaustionKeepsTheStreamReadable()
	{
		$pump = new TPumpStream(fn () => '');
		self::assertSame('', $pump->read(10));
		self::assertTrue($pump->eof());
		self::assertTrue($pump->isReadable(), 'An exhausted source is end of stream, and the stream stays usable.');
		self::assertSame('', $pump->read(10), 'Reading at end of stream returns "" and does not throw.');
	}

	public function testPumpStreamDetachedIsUnusable()
	{
		$pump = new TPumpStream(fn () => 'data', 100);
		$pump->close();
		self::assertFalse($pump->isReadable());
		self::assertTrue($pump->eof());
		self::assertNull($pump->getSize(), 'The declared size is gone once detached.');
		self::assertSame('', (string) $pump, '__toString on a detached pump yields "" without throwing.');
		self::expectException(\RuntimeException::class);
		$pump->read(1);
	}

	public function testPumpStreamToStringSwallowsASourceException()
	{
		$pump = new TPumpStream(function () {
			throw new \DomainException('source failure');
		});
		self::assertSame('', (string) $pump, 'PSR-7 forbids __toString to throw.');
	}

	public function testPumpStreamReadZeroReturnsEmptyWithoutPumping()
	{
		$calls = 0;
		$pump = new TPumpStream(function () use (&$calls) {
			$calls++;
			return 'x';
		});
		self::assertSame('', $pump->read(0));
		self::assertSame(0, $calls, 'A zero-length read never invokes the source.');
	}

	// ---- TAppendStream ----

	public function testAppendStreamConcatenates()
	{
		$a = new TAppendStream([TStream::fromString('HEAD'), TStream::fromString('BODY')]);
		self::assertSame('HEADBODY', (string) $a);
		self::assertSame(8, $a->getSize());
	}

	public function testAppendStreamReadAcrossParts()
	{
		$a = new TAppendStream([TStream::fromString('abc'), TStream::fromString('def')]);
		self::assertSame('abcde', $a->read(5));
		self::assertSame('f', $a->read(5));
		self::assertTrue($a->eof());
	}

	public function testAppendStreamSeekRewinds()
	{
		$a = new TAppendStream([TStream::fromString('12'), TStream::fromString('34')]);
		self::assertSame('1234', $a->getContents());
		$a->rewind();
		self::assertSame('1234', $a->getContents());
	}

	public function testAppendStreamClosedIsUnusable()
	{
		$all = new TAppendStream([TStream::fromString('HEAD'), TStream::fromString('BODY')]);
		$all->close();
		self::assertFalse($all->isReadable());
		self::assertTrue($all->eof());
		self::assertNull($all->getSize(), 'A closed sequence has no size.');
		self::assertSame('', (string) $all, '__toString on a closed sequence yields "" without throwing.');
		self::expectException(\RuntimeException::class);
		$all->read(1);
	}

	public function testAppendStreamToStringSwallowsAReadFailure()
	{
		$failing = new TFnStream([
			'isReadable' => fn () => true,
			'eof' => fn () => false,
			'read' => function () {
				throw new \DomainException('part failure');
			},
		]);
		$all = new TAppendStream([$failing]);
		self::assertSame('', (string) $all, 'PSR-7 forbids __toString to throw.');
	}

	public function testAppendStreamRejectsUnreadable()
	{
		$unreadable = TStream::fromFile('php://output', 'w');
		$a = new TAppendStream();
		self::expectException(\InvalidArgumentException::class);
		$a->add($unreadable);
	}

	// ---- TDroppingStream ----

	public function testDroppingStreamCapsWrites()
	{
		$d = new TDroppingStream(TStream::fromMemory(), 8);
		self::assertSame(5, $d->write('hello'));
		self::assertSame(3, $d->write('world'));   // only 3 of 5 fit
		self::assertSame(0, $d->write('!!!'));      // full
		$d->seek(0);
		self::assertSame('hellowor', $d->getContents());
	}

	// ---- TLazyOpenStream ----

	public function testLazyOpenCloseWithoutUseNeverRunsTheFactory()
	{
		$calls = 0;
		$lazy = new TLazyOpenStream(function () use (&$calls) {
			$calls++;
			return TStream::fromString('opened');
		});
		$lazy->close();
		self::assertNull($lazy->detach());
		self::assertSame(0, $calls, 'Disposing of an unused lazy stream does not open it.');
		self::assertFalse($lazy->isOpened());
	}

	public function testLazyOpenRefusesUnserialization()
	{
		$payload = serialize(new TLazyOpenStream('rand'));   // a string callable is serializable
		self::expectException(\LogicException::class);
		unserialize($payload);
	}

	public function testPumpStreamRefusesUnserialization()
	{
		$payload = serialize(new TPumpStream('strtoupper'));   // a string callable is serializable
		self::expectException(\LogicException::class);
		unserialize($payload);
	}

	public function testLazyOpenStreamDefersUntilUse()
	{
		$opened = 0;
		$lazy = new TLazyOpenStream(function () use (&$opened) {
			$opened++;
			return TStream::fromString('lazy data');
		});
		self::assertFalse($lazy->isOpened());
		self::assertSame(0, $opened);
		self::assertSame('lazy data', $lazy->getContents());
		self::assertTrue($lazy->isOpened());
		self::assertSame(1, $opened);
		$lazy->seek(0);
		self::assertSame('lazy data', $lazy->getContents());
		self::assertSame(1, $opened);   // opened only once
	}
}
