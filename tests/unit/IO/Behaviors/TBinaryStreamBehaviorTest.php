<?php

use Prado\Exceptions\TIOException;
use Prado\IO\Behaviors\TBinaryStreamBehavior;
use Prado\IO\TStream;
use Prado\IO\TByteOrder;

class TBinaryStreamBehaviorTest extends PHPUnit\Framework\TestCase
{
	private function stream(?int $order = null): TStream
	{
		$s = TStream::fromMemory();
		$s->attachBehavior('binary', new TBinaryStreamBehavior($order));
		return $s;
	}

	public function testByteOrderProperty()
	{
		$b = new TBinaryStreamBehavior();
		self::assertNull($b->getByteOrder());
		$b->setByteOrder(TByteOrder::BigEndian);
		self::assertSame(TByteOrder::BigEndian, $b->getByteOrder());
	}

	public function testBigEndianRoundTrip()
	{
		$s = $this->stream(TByteOrder::BigEndian);
		$s->writeUInt32(0x01020304);
		$s->seek(0);
		self::assertSame("\x01\x02\x03\x04", $s->read(4));   // bytes are big-endian
		$s->seek(0);
		self::assertSame(0x01020304, $s->readUInt32());
		$s->close();
	}

	public function testLittleEndianAndPerCallOverride()
	{
		$s = $this->stream(TByteOrder::BigEndian);
		$s->writeUInt16(0x0102, TByteOrder::LittleEndian);   // override the BE default
		$s->seek(0);
		self::assertSame("\x02\x01", $s->read(2));
		$s->seek(0);
		self::assertSame(0x0102, $s->readUInt16(TByteOrder::LittleEndian));
		$s->close();
	}

	public function testSignedTypes()
	{
		$s = $this->stream(TByteOrder::LittleEndian);
		$s->writeInt8(-5);
		$s->writeInt16(-1000);
		$s->writeInt32(-70000);
		$s->seek(0);
		self::assertSame(-5, $s->readInt8());
		self::assertSame(-1000, $s->readInt16());
		self::assertSame(-70000, $s->readInt32());
		$s->close();
	}

	public function testFloatAndDouble()
	{
		$s = $this->stream(TByteOrder::BigEndian);
		$s->writeFloat(1.5);
		$s->writeDouble(3.141592653589793);
		$s->seek(0);
		self::assertEqualsWithDelta(1.5, $s->readFloat(), 0.0001);
		self::assertEqualsWithDelta(3.141592653589793, $s->readDouble(), 1e-12);
		$s->close();
	}

	public function testUnexpectedEofThrows()
	{
		$s = TStream::fromString("\x01");   // only one byte
		$s->attachBehavior('binary', new TBinaryStreamBehavior());
		self::expectException(TIOException::class);
		$s->readUInt32();
	}

	public function testRoundTripDefaultMachineOrder()
	{
		$s = $this->stream();   // null → machine order
		$s->writeInt64(-1234567890123);
		$s->seek(0);
		self::assertSame(-1234567890123, $s->readInt64());
		$s->close();
	}

	public function testFullTypeMatrixRoundTrip()
	{
		$s = $this->stream(TByteOrder::BigEndian);
		$s->writeUInt8(200);
		$s->writeInt8(-100);
		$s->writeUInt16(0xFFFF);
		$s->writeInt16(-30000);
		$s->writeUInt32(0xFFFFFFFF);
		$s->writeInt32(-2000000000);
		$s->writeInt64(-1234567890123456);
		$s->seek(0);
		self::assertSame(200, $s->readUInt8());
		self::assertSame(-100, $s->readInt8());
		self::assertSame(0xFFFF, $s->readUInt16());
		self::assertSame(-30000, $s->readInt16());
		self::assertSame(0xFFFFFFFF, $s->readUInt32());
		self::assertSame(-2000000000, $s->readInt32());
		self::assertSame(-1234567890123456, $s->readInt64());
		$s->close();
	}

	public function testUInt64RoundTripAndHighBit()
	{
		if (PHP_INT_SIZE < 8) {
			self::markTestSkipped('64-bit integers require a 64-bit PHP build.');
		}
		$s = $this->stream(TByteOrder::BigEndian);
		$s->writeUInt64(0x0123456789ABCDEF);
		$s->writeUInt64(-1);                          // all-ones bit pattern
		$s->seek(0);
		self::assertSame("\x01\x23\x45\x67\x89\xAB\xCD\xEF", $s->read(8));
		$s->seek(0);
		self::assertSame(0x0123456789ABCDEF, $s->readUInt64());
		self::assertSame(-1, $s->readUInt64(), 'uint64 above PHP_INT_MAX reads as a negative int.');
		$s->close();
	}

	public function testUInt8WrapsLikePack()
	{
		$s = $this->stream();
		$s->writeUInt8(256);                          // wraps to 0
		$s->writeUInt8(-1);                           // wraps to 255
		$s->seek(0);
		self::assertSame(0, $s->readUInt8());
		self::assertSame(255, $s->readUInt8());
		$s->close();
	}

	public function testEightBitIgnoresByteOrder()
	{
		// 8-bit methods take no order and never byte-reverse.
		$s = $this->stream(TByteOrder::LittleEndian);
		$s->writeUInt8(0xAB);
		$s->seek(0);
		self::assertSame("\xAB", $s->read(1));
		$s->seek(0);
		self::assertSame(0xAB, $s->readUInt8());
		$s->close();
	}

	public function testFloatDoubleLittleEndian()
	{
		$s = $this->stream(TByteOrder::LittleEndian);
		$s->writeFloat(1.5);
		$s->seek(0);
		self::assertSame("\x00\x00\xC0\x3F", $s->read(4));   // 1.5f little-endian
		$s->seek(0);
		self::assertEqualsWithDelta(1.5, $s->readFloat(), 1e-6);
		$s->close();

		$s = $this->stream(TByteOrder::LittleEndian);
		$s->writeDouble(-2.5);
		$s->seek(0);
		self::assertEqualsWithDelta(-2.5, $s->readDouble(), 1e-12);
		$s->close();
	}

	public function testWriteReturnsByteCount()
	{
		$s = $this->stream();
		self::assertSame(1, $s->writeUInt8(1));
		self::assertSame(2, $s->writeUInt16(1));
		self::assertSame(4, $s->writeUInt32(1));
		self::assertSame(8, $s->writeInt64(1));
		$s->close();
	}

	public function testReadExactAcrossPipeChunks()
	{
		// A non-seekable pipe may return fewer bytes than asked; readExact() loops.
		$res = TTestIOHelper::pipeResource("\x01\x02\x03\x04");
		$s = TTestIOHelper::resourceStream($res, false);
		$s->attachBehavior('binary', new TBinaryStreamBehavior(TByteOrder::BigEndian));
		self::assertSame(0x01020304, $s->readUInt32());
		TTestIOHelper::closeAny($res);
	}

	public function testPartialValueAtEofThrows()
	{
		$s = TStream::fromString("\x01\x02");          // only 2 of 4 bytes
		$s->attachBehavior('binary', new TBinaryStreamBehavior());
		self::expectException(TIOException::class);
		$s->readUInt32();
	}

	public function testUnattachedBehaviorThrows()
	{
		$b = new TBinaryStreamBehavior();
		self::expectException(TIOException::class);
		$b->readUInt8();                               // no owner -> no stream
	}

	public function testNonStreamOwnerThrows()
	{
		$owner = new \Prado\TComponent();
		$b = new TBinaryStreamBehavior();
		$owner->attachBehavior('binary', $b);
		self::expectException(TIOException::class);
		$owner->readUInt8();
	}
}
