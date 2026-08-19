<?php

use Prado\IO\Behaviors\TStreamNoSeekBehavior;
use Prado\IO\TStream;
use Prado\Util\TBehavior;
use Prado\Util\TCallChain;

/** A permissive dyIsSeekable behavior, to prove the no-seek denial wins over a chained yes. */
class TPermissiveSeekBehavior extends TBehavior
{
	public function dyIsSeekable(bool $seekable, ?TCallChain $chain = null): bool
	{
		if ($chain !== null) {
			return (bool) $chain->dyIsSeekable(true);
		}
		return true;
	}
}

class TStreamNoSeekBehaviorTest extends PHPUnit\Framework\TestCase
{
	private function stream(): TStream
	{
		$s = TStream::fromString('forward only');
		$s->attachBehavior('noseek', new TStreamNoSeekBehavior());
		return $s;
	}

	public function testReportsNotSeekable()
	{
		// The dyIsSeekable hook forces the capability false, so isSeekable() agrees with seek().
		$s = $this->stream();
		self::assertFalse($s->isSeekable());
		$s->detachBehavior('noseek');
		self::assertTrue($s->isSeekable());
		$s->close();
	}

	public function testSeekThrowsAndCursorStays()
	{
		$s = $this->stream();
		self::assertSame('forward', $s->read(7));
		try {
			$s->seek(0);
			self::fail('seek() must throw while the no-seek behavior is attached.');
		} catch (\RuntimeException $e) {
			self::assertTrue(true);
		}
		self::assertSame(' only', $s->read(5));   // cursor never moved back
		$s->close();
	}

	public function testReadAndWriteStillWork()
	{
		$s = TStream::fromMemory();
		$s->attachBehavior('noseek', new TStreamNoSeekBehavior());
		self::assertSame(5, $s->write('hello'));
		$s->close();
	}

	public function testRewindIsBlockedToo()
	{
		// rewind() is a seek to the start; leaving it open would defeat the veto.
		$s = $this->stream();
		$s->read(7);
		self::expectException(\RuntimeException::class);
		$s->rewind();
	}

	public function testDisablingTheBehaviorRestoresSeeking()
	{
		$s = $this->stream();
		self::assertFalse($s->isSeekable());
		$s->asa('noseek')->setEnabled(false);
		self::assertTrue($s->isSeekable(), 'A disabled behavior no longer vetoes.');
		$s->seek(0);
		self::assertSame('forward', $s->read(7));
		$s->close();
	}

	public function testDenialWinsOverAPermissiveChainedBehavior()
	{
		$s = TStream::fromString('forward only');
		$s->attachBehavior('noseek', new TStreamNoSeekBehavior());
		$s->attachBehavior('permissive', new TPermissiveSeekBehavior());
		self::assertFalse($s->isSeekable(), 'The denial stands regardless of the chained result.');
		$s->detachBehavior('noseek');
		self::assertTrue($s->isSeekable(), 'The permissive behavior alone leaves seeking on.');
		$s->close();
	}

	public function testDetachRestoresSeeking()
	{
		$s = $this->stream();
		try {
			$s->seek(0);
			self::fail('seek() must throw while attached.');
		} catch (\RuntimeException $e) {
			self::assertTrue(true);
		}
		$s->detachBehavior('noseek');
		$s->seek(0);                              // no longer vetoed
		self::assertSame('forward only', $s->read(12));
		$s->close();
	}
}
