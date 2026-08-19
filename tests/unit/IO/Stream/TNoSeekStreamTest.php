<?php

use Prado\IO\Stream\TNoSeekStream;
use Prado\IO\TStream;

class TNoSeekStreamTest extends PHPUnit\Framework\TestCase
{
	public function testForwardsReadsButReportsNotSeekable()
	{
		$s = new TNoSeekStream(TStream::fromString('hello'));
		self::assertFalse($s->isSeekable());
		self::assertTrue($s->isReadable());
		self::assertSame('hel', $s->read(3));
		self::assertSame('lo', $s->getContents());
		$s->close();
	}

	public function testRewindThrows()
	{
		// rewind is a seek to the start; forwarding it to the inner stream would bypass the block.
		$s = new TNoSeekStream(TStream::fromString('abc'));
		$s->read(2);
		self::expectException(\RuntimeException::class);
		$s->rewind();
	}

	public function testSeekThrows()
	{
		$s = new TNoSeekStream(TStream::fromString('x'));
		$this->expectException(\RuntimeException::class);
		$s->seek(0);
	}
}
