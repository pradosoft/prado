<?php

use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\TByteOrder;
use Prado\IO\TStream;
use Prado\IO\Util\TBitReader;
use Prado\IO\Util\TBitFieldFormat;
use Prado\IO\Util\TBitWriter;

class TBitWriterTest extends PHPUnit\Framework\TestCase
{
	private function dump(TStream $s): string
	{
		$s->seek(0);
		return $s->getContents();
	}

	public function testWriteAcrossByteBoundary()
	{
		$s = TStream::fromMemory();
		$w = new TBitWriter($s);
		$w->writeBits(0xA, 4);
		$w->writeBits(0xBCD, 12);
		self::assertSame("\xAB\xCD", $this->dump($s));
	}

	public function testMultiByteFieldUsesOneStreamWrite()
	{
		// A 32-bit field emits its four bytes in a single underlying write().
		$dst = new TTestStream(TTestIOHelper::memoryResource());
		$w = new TBitWriter($dst);
		$w->writeBits(0x12345678, 32);
		self::assertSame(1, $dst->writeCalls, 'Completed bytes are written in one call.');
		$dst->seek(0);
		self::assertSame("\x12\x34\x56\x78", $dst->getContents());
		$dst->close();
	}

	public function testFlushZeroPadsPartialByte()
	{
		$s = TStream::fromMemory();
		$w = new TBitWriter($s);
		$w->writeBits(0b101, 3);
		self::assertSame(1, $w->flush());          // 0b101 -> 0b1010_0000
		self::assertSame("\xA0", $this->dump($s));
	}

	public function testPartialByteNotWrittenWithoutFlush()
	{
		// Documented gotcha: a trailing partial byte stays buffered until flush().
		$s = TStream::fromMemory();
		$w = new TBitWriter($s);
		$w->writeBits(0b101, 3);
		self::assertSame('', $this->dump($s), 'The 3 pending bits are not on the stream yet.');
		$w->flush();
		self::assertSame("\xA0", $this->dump($s));
	}

	public function testFlushNoPendingBitsWritesNothing()
	{
		$s = TStream::fromMemory();
		$w = new TBitWriter($s);
		$w->writeBits(0xFF, 8);
		self::assertSame(0, $w->flush());
		self::assertSame("\xFF", $this->dump($s));
	}

	public function testRoundTripWithReader()
	{
		$s = TStream::fromMemory();
		$w = new TBitWriter($s);
		$w->writeBits(5, 3);
		$w->writeBits(0x1234, 16);
		$w->writeBits(-3, 5, TBitFieldFormat::Signed);
		$w->flush();

		$r = new TBitReader(TStream::fromString($this->dump($s)));
		self::assertSame(5, $r->readBits(3));
		self::assertSame(0x1234, $r->readBits(16));
		self::assertSame(-3, $r->readBits(5, TBitFieldFormat::Signed));
	}

	public function testLSBFirst()
	{
		$s = TStream::fromMemory();
		$w = new TBitWriter($s);
		$w->setLSBFirst(true);
		$w->writeBits(0x80, 8);                    // mirrored => 0x01
		self::assertSame("\x01", $this->dump($s));
	}

	public function testFloat32RoundTrip()
	{
		$s = TStream::fromMemory();
		$w = new TBitWriter($s);
		$w->writeBits(1.5, 32, TBitFieldFormat::Float);
		$w->flush();
		self::assertSame("\x3F\xC0\x00\x00", $this->dump($s));
	}

	public function testInvalidBitsThrows()
	{
		$w = new TBitWriter(TStream::fromMemory());
		self::expectException(TInvalidDataValueException::class);
		$w->writeBits(0, PHP_INT_SIZE * 8 + 1);
	}

	public function testAlign()
	{
		$s = TStream::fromMemory();
		$w = new TBitWriter($s);
		$w->writeBits(0b111, 3);
		$w->align(8);
		self::assertSame(8, $w->getCurrentBitIndex());
		$w->writeBits(0xAA, 8);
		$w->flush();
		self::assertSame("\xE0\xAA", $this->dump($s));
	}

	public function testConfigDefaults()
	{
		$w = new TBitWriter(TStream::fromMemory());
		self::assertFalse($w->getLSBFirst());
		self::assertFalse($w->getFloatConvert());
		self::assertSame(TByteOrder::BigEndian, $w->getByteOrder());
		self::assertSame(0, $w->getCurrentBitIndex());
	}

	public function testLittleEndianByteOrderSwapsBytes()
	{
		$s = TStream::fromMemory();
		$w = new TBitWriter($s);
		$w->setByteOrder(TByteOrder::LittleEndian);
		$w->writeBits(0x0102, 16);
		$w->flush();
		self::assertSame("\x02\x01", $this->dump($s), 'Little-endian order swaps the bytes.');
	}

	public function testByteOrderRoundTrip()
	{
		$s = TStream::fromMemory();
		$w = new TBitWriter($s);
		$w->setByteOrder(TByteOrder::LittleEndian);
		$w->writeBits(0x12345678, 32);
		$w->flush();

		$r = new TBitReader(TStream::fromString($this->dump($s)));
		$r->setByteOrder(TByteOrder::LittleEndian);
		self::assertSame(0x12345678, $r->readBits(32));
	}

	public function test64BitRoundTrip()
	{
		if (PHP_INT_SIZE < 8) {
			self::markTestSkipped('64-bit integers require a 64-bit PHP build.');
		}
		$s = TStream::fromMemory();
		$w = new TBitWriter($s);
		$w->writeBits(0x0123456789ABCDEF, 64);
		$w->flush();
		self::assertSame("\x01\x23\x45\x67\x89\xAB\xCD\xEF", $this->dump($s));

		$r = new TBitReader(TStream::fromString($this->dump($s)));
		self::assertSame(0x0123456789ABCDEF, $r->readBits(64));
	}

	public function testWritesWideFieldsInOnePass()
	{
		// 40/48/56-bit fields emit 5/6/7 bytes through the batched pack path.
		if (PHP_INT_SIZE < 8) {
			self::markTestSkipped('Fields wider than 32 bits require a 64-bit PHP build.');
		}
		$cases = [
			[40, 0x123456789A, "\x12\x34\x56\x78\x9A"],
			[48, 0x123456789ABC, "\x12\x34\x56\x78\x9A\xBC"],
			[56, 0x123456789ABCDE, "\x12\x34\x56\x78\x9A\xBC\xDE"],
		];
		foreach ($cases as [$width, $value, $expected]) {
			$s = TStream::fromMemory();
			$w = new TBitWriter($s);
			$w->writeBits($value, $width);
			$w->flush();
			self::assertSame($expected, $this->dump($s), "{$width}-bit field");
		}
	}

	public function testBigEndianByteOrderIsNoOp()
	{
		$s = TStream::fromMemory();
		$w = new TBitWriter($s);
		$w->setByteOrder(TByteOrder::BigEndian);
		$w->writeBits(0x0102, 16);
		$w->flush();
		self::assertSame("\x01\x02", $this->dump($s), 'Big-endian leaves the bytes in order.');
	}

	public function testLittleEndianFlipsWholeByteWidths()
	{
		// 24-bit exercises packBytes' 3-byte arm; the wider widths need a 64-bit build.
		$cases = [[24, 0xAABBCC, "\xCC\xBB\xAA"]];
		if (PHP_INT_SIZE >= 8) {
			$cases[] = [40, 0x123456789A, "\x9A\x78\x56\x34\x12"];
			$cases[] = [48, 0x123456789ABC, "\xBC\x9A\x78\x56\x34\x12"];
			$cases[] = [56, 0x123456789ABCDE, "\xDE\xBC\x9A\x78\x56\x34\x12"];
		}
		foreach ($cases as [$width, $value, $expected]) {
			$s = TStream::fromMemory();
			$w = new TBitWriter($s);
			$w->setByteOrder(TByteOrder::LittleEndian);
			$w->writeBits($value, $width);
			$w->flush();
			self::assertSame($expected, $this->dump($s), "{$width}-bit little-endian");
		}
	}

	public function testByteOrderIgnoredForNonByteWidths()
	{
		$s = TStream::fromMemory();
		$w = new TBitWriter($s);
		$w->setByteOrder(TByteOrder::LittleEndian);
		$w->writeBits(0xABC, 12);                 // not a whole-byte width: no reversal
		$w->flush();
		self::assertSame("\xAB\xC0", $this->dump($s));
	}

	public function testFloat8RoundTrip()
	{
		$s = TStream::fromMemory();
		$w = new TBitWriter($s);
		$w->writeBits(0.5, 8, TBitFieldFormat::Float);
		$w->flush();
		self::assertSame(1, strlen($this->dump($s)), 'An 8-bit float is one byte.');
		$r = new TBitReader(TStream::fromString($this->dump($s)));
		self::assertEqualsWithDelta(0.5, $r->readBits(8, TBitFieldFormat::Float), 1e-6);
	}

	public function testFloat64RoundTrip()
	{
		if (PHP_INT_SIZE < 8) {
			self::markTestSkipped('64-bit floats require a 64-bit PHP build.');
		}
		$s = TStream::fromMemory();
		$w = new TBitWriter($s);
		$w->writeBits(0.5, 64, TBitFieldFormat::Float);
		$w->flush();
		$r = new TBitReader(TStream::fromString($this->dump($s)));
		self::assertSame(0.5, $r->readBits(64, TBitFieldFormat::Float));
	}

	public function testWideFieldOn32BitPhpThrows()
	{
		if (PHP_INT_SIZE >= 8) {
			self::markTestSkipped('Requires a 32-bit PHP build (where 33-64 bit fields are rejected).');
		}
		self::expectException(TInvalidDataValueException::class);
		(new TBitWriter(TStream::fromMemory()))->writeBits(0, 40);
	}

	public function testWritesWideFieldAcrossAPartialBuffer()
	{
		// 4 pending bits then a 60-bit field exercises the batched emit after a partial byte.
		if (PHP_INT_SIZE < 8) {
			self::markTestSkipped('Fields wider than 32 bits require a 64-bit PHP build.');
		}
		$s = TStream::fromMemory();
		$w = new TBitWriter($s);
		$w->writeBits(0x1, 4);
		$w->writeBits(0xABCDEF012345678, 60);
		$w->flush();
		self::assertSame("\x1A\xBC\xDE\xF0\x12\x34\x56\x78", $this->dump($s));

		$r = new TBitReader(TStream::fromString($this->dump($s)));
		self::assertSame(0x1, $r->readBits(4));
		self::assertSame(0xABCDEF012345678, $r->readBits(60));
	}

	public function testOversizedValueIsTruncated()
	{
		$s = TStream::fromMemory();
		$w = new TBitWriter($s);
		$w->writeBits(0x1FF, 8);                    // only the low 8 bits are written
		$w->flush();
		self::assertSame("\xFF", $this->dump($s));
	}

	public function testNegativeUnsignedWritesLowBits()
	{
		$s = TStream::fromMemory();
		$w = new TBitWriter($s);
		$w->writeBits(-1, 4);                       // two's-complement low 4 bits -> 0xF
		$w->flush();
		self::assertSame("\xF0", $this->dump($s));
	}

	public function testFloat16RoundTrip()
	{
		$s = TStream::fromMemory();
		$w = new TBitWriter($s);
		$w->writeBits(1.0, 16, TBitFieldFormat::Float);
		$w->flush();
		self::assertSame("\x3C\x00", $this->dump($s));   // fp16 1.0
	}

	public function testFloatConvertScalesFromFieldRange()
	{
		$s = TStream::fromMemory();
		$w = new TBitWriter($s);
		$w->setFloatConvert(true);
		$w->writeBits(65535.0, 16, TBitFieldFormat::Float);   // 65535 / (2^16 - 1) = 1.0
		$w->flush();
		self::assertSame("\x3C\x00", $this->dump($s));
	}

	public function testFloatInvalidWidthThrows()
	{
		$w = new TBitWriter(TStream::fromMemory());
		self::expectException(TInvalidDataValueException::class);
		$w->writeBits(1.0, 12, TBitFieldFormat::Float);
	}

	public function testWriteZeroBitsIsNoOp()
	{
		$s = TStream::fromMemory();
		$w = new TBitWriter($s);
		$w->writeBits(0xFF, 0);
		self::assertSame(0, $w->getCurrentBitIndex());
		self::assertSame('', $this->dump($s));
	}

	public function testAlignAlreadyAlignedAndNonPositive()
	{
		$s = TStream::fromMemory();
		$w = new TBitWriter($s);
		$w->writeBits(0xAB, 8);
		$w->align(8);                               // already aligned: no padding
		$w->align(0);                               // non-positive: no-op
		self::assertSame(8, $w->getCurrentBitIndex());
		$w->flush();
		self::assertSame("\xAB", $this->dump($s));
	}

	public function testLSBFirstMultiByte()
	{
		$s = TStream::fromMemory();
		$w = new TBitWriter($s);
		$w->setLSBFirst(true);
		$w->writeBits(0x8040, 16);                  // each completed byte mirrored -> 0x01 0x02
		$w->flush();
		self::assertSame("\x01\x02", $this->dump($s));
	}

	public function testCurrentBitIndexProgression()
	{
		$s = TStream::fromMemory();
		$w = new TBitWriter($s);
		$w->writeBits(0, 3);
		self::assertSame(3, $w->getCurrentBitIndex());
		$w->writeBits(0, 13);
		self::assertSame(16, $w->getCurrentBitIndex());
	}
}
