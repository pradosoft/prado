<?php

use Prado\Exceptions\TIOException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\Stream\TReservedSpaceMode;
use Prado\IO\Stream\TReservedSpaceStream;
use Prado\IO\TStream;

class TReservedSpaceStreamTest extends PHPUnit\Framework\TestCase
{
	private function reserved(string $data, array $spaces, string $mode = TReservedSpaceMode::Clip): TReservedSpaceStream
	{
		$s = new TReservedSpaceStream(TStream::fromString($data), $spaces, $mode);
		$s->seek(0);
		return $s;
	}

	public function testNormalizesAndMergesSpaces()
	{
		$s = new TReservedSpaceStream(TStream::fromString(str_repeat('.', 20)), [[8, 4], [10, 5], [2, 2]]);
		self::assertSame([[2, 2], [8, 7]], $s->getReservedSpaces(), 'Overlapping/touching spaces merge, sorted by offset.');
	}

	public function testAddReservedSpace()
	{
		$s = new TReservedSpaceStream(TStream::fromString(str_repeat('.', 20)), [[2, 2]]);
		$s->addReservedSpace(8, 4);
		self::assertSame([[2, 2], [8, 4]], $s->getReservedSpaces());
	}

	public function testInvalidSpaceThrows()
	{
		self::expectException(TInvalidDataValueException::class);
		new TReservedSpaceStream(TStream::fromString('....'), [[1, 0]]);   // non-positive length
	}

	public function testAddressingIsOneToOne()
	{
		$s = $this->reserved('ABCDEFGH####IJKL', [[8, 4]]);
		self::assertSame(16, $s->getSize(), 'getSize() reports the full inner size.');
		$s->seek(12);
		self::assertSame(12, $s->tell(), 'tell() is the physical position.');
	}

	public function testClipReadStopsAtBoundaryAndResumes()
	{
		$s = $this->reserved('ABCDEFGH####IJKL', [[8, 4]]);
		self::assertSame('ABCDEFGH', $s->read(100), 'Read clips at the reserved boundary.');
		self::assertSame(8, $s->tell());
		self::assertSame('', $s->read(100), 'A position at the reserved start reads nothing.');
		$s->seek(12);
		self::assertSame('IJKL', $s->read(100), 'Reading resumes past the reserved space.');
	}

	public function testClipWriteStopsAtBoundary()
	{
		$s = $this->reserved(str_repeat('.', 20), [[8, 4]]);
		self::assertSame(8, $s->write(str_repeat('A', 12)), 'Write clips at the reserved boundary.');
		$s->seek(0);
		self::assertSame('AAAAAAAA............', (string) $s->getStream(), 'The reserved bytes are untouched.');
	}

	public function testSkipReadJumpsOverReservedSpace()
	{
		$s = $this->reserved('ABCDEFGH####IJKL', [[8, 4]], TReservedSpaceMode::Skip);
		self::assertSame('ABCDEFGHIJKL', $s->read(100), 'Skip mode reads across the reserved space.');
	}

	public function testSkipWriteJumpsOverReservedSpace()
	{
		$s = $this->reserved(str_repeat('.', 16), [[8, 4]], TReservedSpaceMode::Skip);
		self::assertSame(12, $s->write(str_repeat('A', 12)));
		$s->seek(0);
		self::assertSame('AAAAAAAA####AAAA', str_replace('.', '#', (string) $s->getStream()), 'Reserved bytes stay as the original (dots), free bytes written.');
	}

	public function testFailReadOverlappingThrows()
	{
		$s = $this->reserved('ABCDEFGH####IJKL', [[8, 4]], TReservedSpaceMode::Fail);
		self::expectException(TIOException::class);
		$s->read(100);
	}

	public function testFailReadUpToBoundarySucceeds()
	{
		$s = $this->reserved('ABCDEFGH####IJKL', [[8, 4]], TReservedSpaceMode::Fail);
		self::assertSame('ABCDEFGH', $s->read(8), 'A read ending exactly at the boundary does not overlap.');
	}

	public function testFailWriteOverlappingThrows()
	{
		$s = $this->reserved(str_repeat('.', 20), [[8, 4]], TReservedSpaceMode::Fail);
		self::expectException(TIOException::class);
		$s->write(str_repeat('A', 12));
	}

	public function testModeAccessor()
	{
		$s = new TReservedSpaceStream(TStream::fromString('....'));
		self::assertSame(TReservedSpaceMode::Clip, $s->getMode());
		$s->setMode(TReservedSpaceMode::Skip);
		self::assertSame(TReservedSpaceMode::Skip, $s->getMode());
	}

	public function testSetModeRejectsAnUnknownMode()
	{
		$s = new TReservedSpaceStream(TStream::fromString('....'));
		self::expectException(TInvalidDataValueException::class);
		$s->setMode('bogus');
	}

	public function testFailReadNearEofIgnoresAnUnreachableSpace()
	{
		// The stream ends at 6 and the reserved space starts at 8: a large read request can
		// never touch it, so Fail mode returns the remaining bytes without throwing.
		$s = $this->reserved('ABCDEF', [[8, 4]], TReservedSpaceMode::Fail);
		self::assertSame('ABCDEF', $s->read(100));
	}

	public function testFailReadFromInsideAReservedSpaceThrows()
	{
		$s = $this->reserved('ABCDEFGH####IJKL', [[8, 4]], TReservedSpaceMode::Fail);
		$s->seek(9);
		self::expectException(TIOException::class);
		$s->read(1);
	}

	public function testFailWriteUpToBoundarySucceeds()
	{
		$s = $this->reserved(str_repeat('.', 20), [[8, 4]], TReservedSpaceMode::Fail);
		self::assertSame(8, $s->write(str_repeat('A', 8)), 'A write ending exactly at the boundary does not overlap.');
	}
}
