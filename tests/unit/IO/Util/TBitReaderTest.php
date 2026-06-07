<?php

use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\TByteOrder;
use Prado\IO\TStream;
use Prado\IO\Util\TBitReader;
use Prado\IO\Util\TBitFieldFormat;

class TBitReaderTest extends PHPUnit\Framework\TestCase
{
	private function reader(string $bytes): TBitReader
	{
		return new TBitReader(TStream::fromString($bytes));
	}

	public function testReadAcrossByteBoundary()
	{
		$r = $this->reader("\xAB\xCD");
		self::assertSame(0xA, $r->readBits(4));
		self::assertSame(0xBCD, $r->readBits(12));
		self::assertSame(16, $r->getCurrentBitIndex());
	}

	public function testReadFullBytes()
	{
		$r = $this->reader("\x01\x02\x03\x04");
		self::assertSame(0x01020304, $r->readBits(32));
	}

	public function testMultiByteFieldUsesOneStreamRead()
	{
		// A 32-bit field is filled in a single underlying read(), not four 1-byte reads.
		$src = new TTestStream(TTestIOHelper::dataResource("\x12\x34\x56\x78"));
		$r = new TBitReader($src);
		self::assertSame(0x12345678, $r->readBits(32));
		self::assertSame(1, $src->readCalls, 'Multi-byte field reads the stream once.');
		$src->close();
	}

	public function testReadsCorrectlyFromNonSeekablePipe()
	{
		// A non-seekable pipe may hand back fewer bytes than asked; the fill loops to finish.
		$res = TTestIOHelper::pipeResource("\xDE\xAD\xBE\xEF");
		$src = TTestIOHelper::resourceStream($res, false);
		$r = new TBitReader($src);
		self::assertSame(0xDEADBEEF, $r->readBits(32));
		TTestIOHelper::closeAny($res);
	}

	public function testZeroBitsReturnsZero()
	{
		$r = $this->reader("\xFF");
		self::assertSame(0, $r->readBits(0));
		self::assertSame(0, $r->getCurrentBitIndex());
	}

	public function testSignedFormatSignExtends()
	{
		$r = $this->reader("\xFF");           // 0b1111_1111
		self::assertSame(-1, $r->readBits(8, TBitFieldFormat::Signed));
		$r = $this->reader("\x70");           // top nibble 0b0111 = 7
		self::assertSame(7, $r->readBits(4, TBitFieldFormat::Signed));
		$r = $this->reader("\xC0");           // top 3 bits 0b110 -> -2
		self::assertSame(-2, $r->readBits(3, TBitFieldFormat::Signed));
	}

	public function testLSBFirst()
	{
		$r = $this->reader("\x01");           // mirrored => 0b1000_0000
		$r->setLSBFirst(true);
		self::assertSame(1, $r->readBits(1));
	}

	public function testEndOfStreamReturnsFalseAndRestores()
	{
		$r = $this->reader("\x0F");
		self::assertSame(0x0, $r->readBits(4));
		self::assertFalse($r->readBits(8));   // only 4 bits remain
		self::assertSame(0xF, $r->readBits(4)); // buffer was restored
	}

	public function testAlign()
	{
		$r = $this->reader("\xAB\xCD");
		$r->readBits(3);
		self::assertTrue($r->align(8));
		self::assertSame(8, $r->getCurrentBitIndex());
		self::assertSame(0xCD, $r->readBits(8));
	}

	public function testInvalidBitsThrows()
	{
		$r = $this->reader("\x00");
		self::expectException(TInvalidDataValueException::class);
		$r->readBits(-1);
	}

	public function testFloat32RoundTripValue()
	{
		// 1.5f big-endian = 0x3FC00000
		$r = $this->reader("\x3F\xC0\x00\x00");
		self::assertEqualsWithDelta(1.5, $r->readBits(32, TBitFieldFormat::Float), 1e-6);
	}

	public function testConfigDefaults()
	{
		$r = $this->reader('');
		self::assertFalse($r->getLSBFirst());
		self::assertFalse($r->getFloatConvert());
		self::assertSame(TByteOrder::BigEndian, $r->getByteOrder());
		self::assertSame(0, $r->getCurrentBitIndex());
	}

	public function testBigEndianByteOrderDoesNotFlip()
	{
		$r = $this->reader("\x01\x02");
		$r->setByteOrder(TByteOrder::BigEndian);
		self::assertSame(0x0102, $r->readBits(16));
	}

	public function testLittleEndianByteOrderFlips()
	{
		$r = $this->reader("\x01\x02");
		$r->setByteOrder(TByteOrder::LittleEndian);
		self::assertSame(0x0201, $r->readBits(16));

		$r = $this->reader("\x01\x02\x03\x04");
		$r->setByteOrder(TByteOrder::LittleEndian);
		self::assertSame(0x04030201, $r->readBits(32));
	}

	public function testLittleEndianByteOrderFlipsWholeByteWidths()
	{
		// Byte order applies to any whole-byte width, not just 16/32/64.
		$r = $this->reader("\xAA\xBB\xCC");
		$r->setByteOrder(TByteOrder::LittleEndian);
		self::assertSame(0xCCBBAA, $r->readBits(24));
	}

	public function testNullByteOrderFollowsNativeOrder()
	{
		// null resolves to the machine's native order: a big-endian host quick-passes,
		// a little-endian host byte-reverses.
		$r = $this->reader("\x01\x02");
		$r->setByteOrder(null);
		self::assertNull($r->getByteOrder());
		$expected = (TByteOrder::native() === TByteOrder::BigEndian) ? 0x0102 : 0x0201;
		self::assertSame($expected, $r->readBits(16));
	}

	public function testByteOrderIgnoredForNonByteWidths()
	{
		// Byte order is undefined for a width that is not a byte multiple; the value passes through.
		$r = $this->reader("\xAB\xC0");
		$r->setByteOrder(TByteOrder::LittleEndian);
		self::assertSame(0xABC, $r->readBits(12));   // 12 bits: unflipped

		$r = $this->reader("\x80\x80\x80");          // 17 bits: 0x10101
		$r->setByteOrder(TByteOrder::LittleEndian);
		self::assertSame(0x10101, $r->readBits(17)); // unflipped
	}

	public function test64BitField()
	{
		if (PHP_INT_SIZE < 8) {
			self::markTestSkipped('64-bit integers require a 64-bit PHP build.');
		}
		$r = $this->reader("\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF");
		self::assertSame(-1, $r->readBits(64), 'All-ones 64-bit value is the bit pattern -1.');

		$r = $this->reader("\x01\x23\x45\x67\x89\xAB\xCD\xEF");
		self::assertSame(0x0123456789ABCDEF, $r->readBits(64));
	}

	public function testReadsWideMultiByteFields()
	{
		// 40/48/56-bit fields force 5/6/7-byte buffer fills (the unpack('J') path).
		if (PHP_INT_SIZE < 8) {
			self::markTestSkipped('Fields wider than 32 bits require a 64-bit PHP build.');
		}
		self::assertSame(0x123456789A, $this->reader("\x12\x34\x56\x78\x9A")->readBits(40));
		self::assertSame(0x123456789ABC, $this->reader("\x12\x34\x56\x78\x9A\xBC")->readBits(48));
		self::assertSame(0x123456789ABCDE, $this->reader("\x12\x34\x56\x78\x9A\xBC\xDE")->readBits(56));
	}

	public function testReadsWideFieldAcrossAPartialBuffer()
	{
		// A 4-bit read leaves 4 bits buffered, then a 56-bit read spans the leftover plus a
		// fresh multi-byte fill.
		if (PHP_INT_SIZE < 8) {
			self::markTestSkipped('Fields wider than 32 bits require a 64-bit PHP build.');
		}
		$r = $this->reader("\x1A\xBC\xDE\xF0\x12\x34\x56\x78");
		self::assertSame(0x1, $r->readBits(4));
		self::assertSame(0xABCDEF012345678, $r->readBits(60));
	}

	public function testFloat16()
	{
		$r = $this->reader("\x3C\x00");           // fp16 1.0
		self::assertEqualsWithDelta(1.0, $r->readBits(16, TBitFieldFormat::Float), 1e-6);
	}

	public function testFloatInvalidWidthThrows()
	{
		$r = $this->reader("\x00\x00");
		self::expectException(TInvalidDataValueException::class);
		$r->readBits(12, TBitFieldFormat::Float);
	}

	public function testFloatConvertScalesToFieldRange()
	{
		$r = $this->reader("\x3C\x00");           // fp16 1.0
		$r->setFloatConvert(true);
		self::assertSame(65535.0, $r->readBits(16, TBitFieldFormat::Float));   // 1.0 * (2^16 - 1)
	}

	public function testAlignAlreadyAligned()
	{
		$r = $this->reader("\xAB\xCD");
		$r->readBits(8);
		self::assertTrue($r->align(8), 'Aligning on a boundary is a no-op success.');
		self::assertSame(8, $r->getCurrentBitIndex());
		self::assertSame(0xCD, $r->readBits(8));
	}

	public function testAlignNonPositiveReturnsFalse()
	{
		$r = $this->reader("\xFF");
		self::assertFalse($r->align(0));
		self::assertFalse($r->align(-4));
	}

	public function testAlignReturnsFalseAtEof()
	{
		$r = $this->reader("\xF0");
		$r->readBits(4);
		self::assertFalse($r->align(16), 'Alignment past the end fails.');
	}

	public function testPartialFieldAtEofIsUnrecoverable()
	{
		// A whole-byte width from a byte-aligned buffer takes the fast path; reading a field
		// wider than the remaining data returns false and the consumed bytes are gone
		// (documented limitation, not a regression).
		$r = $this->reader("\x12\x34");           // 16 bits available
		self::assertFalse($r->readBits(24), 'Field wider than the stream fails (fast path).');
		self::assertFalse($r->readBits(16), 'The 16 consumed bits are not recoverable.');
	}

	public function testPartialFieldAtEofIsUnrecoverableViaGeneralLoop()
	{
		// A non-whole-byte width skips the aligned fast path, exercising the general consume
		// loop's EOF handling; the destructive contract is the same.
		$r = $this->reader("\x12\x34");           // 16 bits available
		self::assertFalse($r->readBits(20), 'A 20-bit field wider than the data fails (general loop).');
		self::assertFalse($r->readBits(4), 'The consumed bytes are not recoverable.');
	}

	public function testFailedWideReadKeepsPreExistingBufferedBits()
	{
		// On EOF the general loop restores the entry buffer, so bits buffered before the
		// failed read survive it, even though the bytes it read from the stream are lost.
		$r = $this->reader("\x12\x34");           // 16 bits available
		self::assertSame(0x1, $r->readBits(4));   // consumes the first byte, leaves 4 bits buffered
		self::assertFalse($r->readBits(24), 'A wide field past EOF from a misaligned buffer fails.');
		self::assertSame(0x2, $r->readBits(4), 'The 4 bits buffered before the failed read remain.');
	}

	public function testLSBFirstMultiByte()
	{
		// Each byte is bit-mirrored: 0x01 0x02 -> 0x80 0x40
		$r = $this->reader("\x01\x02");
		$r->setLSBFirst(true);
		self::assertSame(0x8040, $r->readBits(16));
	}

	public function testCurrentBitIndexProgression()
	{
		$r = $this->reader("\xFF\xFF\xFF");
		$r->readBits(3);
		self::assertSame(3, $r->getCurrentBitIndex());
		$r->readBits(13);
		self::assertSame(16, $r->getCurrentBitIndex());
		$r->readBits(0);
		self::assertSame(16, $r->getCurrentBitIndex(), 'A zero-bit read does not advance.');
	}
}
