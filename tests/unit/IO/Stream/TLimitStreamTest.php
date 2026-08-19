<?php

use Prado\IO\Stream\TLimitStream;
use Prado\IO\TStream;

class TLimitStreamTest extends PHPUnit\Framework\TestCase
{
	private function whole(): TStream
	{
		return TStream::fromString('HEADER<payload>TRAILER');
	}

	public function testWindowContents()
	{
		$body = new TLimitStream($this->whole(), 9, 6);
		self::assertSame('<payload>', (string) $body);
	}

	public function testGetSizeCapsToLimit()
	{
		$body = new TLimitStream($this->whole(), 9, 6);
		self::assertSame(9, $body->getSize());
	}

	public function testGetSizeUnlimitedIsRemainder()
	{
		$body = new TLimitStream($this->whole(), -1, 6);
		self::assertSame(strlen('HEADER<payload>TRAILER') - 6, $body->getSize());
	}

	public function testReportInnerSizeFlipsGetSize()
	{
		$full = strlen('HEADER<payload>TRAILER');
		$body = new TLimitStream($this->whole(), 9, 6);
		self::assertFalse($body->getReportInnerSize());
		self::assertSame(9, $body->getSize(), 'Default reports the window length.');

		$body->setReportInnerSize(true);
		self::assertSame($full, $body->getSize(), 'Flipped, getSize() reports the inner stream size.');

		// The constructor option sets it directly.
		$viaCtor = new TLimitStream($this->whole(), 9, 6, true);
		self::assertSame($full, $viaCtor->getSize());
	}

	public function testReadCapsToWindowAndEof()
	{
		$body = new TLimitStream($this->whole(), 9, 6);
		self::assertSame('<payl', $body->read(5));
		self::assertSame('oad>', $body->read(100));   // capped to remaining 4
		self::assertTrue($body->eof());
		self::assertSame('', $body->read(10));
	}

	public function testTellIsRelativeToWindow()
	{
		$body = new TLimitStream($this->whole(), 9, 6);
		self::assertSame(0, $body->tell());
		$body->read(3);
		self::assertSame(3, $body->tell());
	}

	public function testSeekSetAndCur()
	{
		$body = new TLimitStream($this->whole(), 9, 6);
		$body->seek(4);
		self::assertSame('load', $body->read(4));
		$body->seek(0);
		$body->seek(2, SEEK_CUR);
		self::assertSame(2, $body->tell());
	}

	public function testSeekEndThrows()
	{
		$body = new TLimitStream($this->whole(), 9, 6);
		self::expectException(\RuntimeException::class);
		$body->seek(0, SEEK_END);
	}

	public function testSeekBeforeTheWindowStartThrows()
	{
		$body = new TLimitStream($this->whole(), 9, 6);
		self::expectException(\RuntimeException::class);
		$body->seek(-1);
	}

	public function testSeekCurLandingBeforeTheWindowThrows()
	{
		$body = new TLimitStream($this->whole(), 9, 6);
		$body->seek(2);
		self::expectException(\RuntimeException::class);
		$body->seek(-5, SEEK_CUR);
	}

	public function testSeekUnknownWhenceThrows()
	{
		$body = new TLimitStream($this->whole(), 9, 6);
		self::expectException(\RuntimeException::class);
		$body->seek(1, 99);
	}
}
