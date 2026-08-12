<?php

use Prado\IO\Stream\TFreeSpaceStream;
use Prado\IO\TStream;

class TFreeSpaceStreamTest extends PHPUnit\Framework\TestCase
{
	public function testContiguousContents()
	{
		$f = new TFreeSpaceStream(TStream::fromString('AAA####BBB'), [[3, 4]]);
		self::assertSame('AAABBB', (string) $f, 'The reserved span is excluded from the logical view.');
	}

	public function testGetSizeIsInnerMinusReserved()
	{
		$f = new TFreeSpaceStream(TStream::fromString('AAA####BBB'), [[3, 4]]);
		self::assertSame(6, $f->getSize());
	}

	public function testReadSkipsReservedSpace()
	{
		$f = new TFreeSpaceStream(TStream::fromString('AAA####BBB'), [[3, 4]]);
		$f->seek(0);
		self::assertSame('AAABBB', $f->read(100));
	}

	public function testTellAndSeekAreLogical()
	{
		$f = new TFreeSpaceStream(TStream::fromString('AAA####BBB'), [[3, 4]]);
		$f->seek(4);                                  // logical 4 -> physical 8
		self::assertSame(4, $f->tell());
		self::assertSame('BB', $f->read(100));
	}

	public function testSeekCurAndEnd()
	{
		$f = new TFreeSpaceStream(TStream::fromString('AAA####BBB'), [[3, 4]]);
		$f->seek(2);
		$f->seek(1, SEEK_CUR);                        // logical 3
		self::assertSame(3, $f->tell());
		self::assertSame('BBB', $f->read(100));

		$f->seek(-2, SEEK_END);                       // logical size 6 - 2 = 4
		self::assertSame(4, $f->tell());
		self::assertSame('BB', $f->read(100));
	}

	public function testEofAtLogicalEnd()
	{
		$f = new TFreeSpaceStream(TStream::fromString('AAA####BBB'), [[3, 4]]);
		$f->seek(0);
		$f->read(100);
		self::assertTrue($f->eof());
	}

	public function testWriteFlowsThroughFreeSpace()
	{
		$f = new TFreeSpaceStream(TStream::fromString(str_repeat('.', 10)), [[3, 4]]);
		self::assertSame(6, $f->write('AAABBB'));
		$f->seek(0);
		self::assertSame('AAA....BBB', (string) $f->getStream(), 'The reserved bytes are left untouched.');
	}

	public function testReservedAtStart()
	{
		$f = new TFreeSpaceStream(TStream::fromString('####AAA'), [[0, 4]]);
		self::assertSame(3, $f->getSize());
		self::assertSame('AAA', (string) $f);
	}

	public function testConstructorPositionsPastALeadingReservedSpace()
	{
		$f = new TFreeSpaceStream(TStream::fromString('####AAA'), [[0, 4]]);
		self::assertSame('AAA', $f->read(100), 'The first read needs no explicit seek.');
		self::assertSame(0, (new TFreeSpaceStream(TStream::fromString('####AAA'), [[0, 4]]))->tell(), 'The start of the free space is logical zero.');
	}

	public function testSeekToANegativePositionThrows()
	{
		$f = new TFreeSpaceStream(TStream::fromString('AAA####BBB'), [[3, 4]]);
		self::expectException(\RuntimeException::class);
		$f->seek(-5);
	}

	public function testSeekCurLandingNegativeThrows()
	{
		$f = new TFreeSpaceStream(TStream::fromString('AAA####BBB'), [[3, 4]]);
		$f->seek(1);
		self::expectException(\RuntimeException::class);
		$f->seek(-3, SEEK_CUR);
	}

	public function testSeekWithAnUnknownWhenceThrows()
	{
		$f = new TFreeSpaceStream(TStream::fromString('AAA####BBB'), [[3, 4]]);
		self::expectException(\RuntimeException::class);
		$f->seek(2, 99);
	}

	public function testNonSeekableInnerDiscardsReservedSpace()
	{
		// A pipe cannot seek, so the skip reads and discards the reserved bytes.
		$res = TTestIOHelper::pipeResource('AAA####BBB');
		$f = new TFreeSpaceStream(TTestIOHelper::resourceStream($res, false), [[3, 4]]);
		self::assertSame('AAABBB', $f->read(100), 'Reserved bytes are discarded on a non-seekable inner stream.');
		TTestIOHelper::closeAny($res);
	}
}
