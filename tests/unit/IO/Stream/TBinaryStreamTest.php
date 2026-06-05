<?php

use Prado\Exceptions\TIOException;
use Prado\IO\Stream\TBinaryStream;
use Prado\IO\TByteOrder;
use Prado\IO\TStream;
use Psr\Http\Message\StreamInterface;

class TBinaryStreamTest extends PHPUnit\Framework\TestCase
{
	private function over(string $bytes, ?int $order = null): TBinaryStream
	{
		return new TBinaryStream(TStream::fromString($bytes), $order);
	}

	public function testIsStreamInterface()
	{
		$b = $this->over('');
		self::assertInstanceOf(StreamInterface::class, $b);
		self::assertTrue($b->isReadable());
	}

	public function testTypedReadsBigEndian()
	{
		$b = $this->over("\x12\x34\x56\x78\x9A", TByteOrder::BigEndian);
		self::assertSame(0x12, $b->readUInt8());
		self::assertSame(0x3456, $b->readUInt16());
		self::assertSame(0x789A, $b->readUInt16());
	}

	public function testTypedReadsLittleEndian()
	{
		$b = $this->over("\x01\x02\x03\x04", TByteOrder::LittleEndian);
		self::assertSame(0x04030201, $b->readUInt32());
	}

	public function testPerCallOrderOverride()
	{
		$b = $this->over("\x01\x02", TByteOrder::BigEndian);
		self::assertSame(0x0201, $b->readUInt16(TByteOrder::LittleEndian));   // override the BE default
	}

	public function testSignedTypes()
	{
		$b = $this->over('', TByteOrder::LittleEndian);
		// build the bytes with the writer side of the behavior-free path
		$src = TStream::fromMemory();
		$src->write(pack('c', -5) . pack('v', -1000 & 0xFFFF) . pack('V', -70000 & 0xFFFFFFFF));
		$src->seek(0);
		$b = new TBinaryStream($src, TByteOrder::LittleEndian);
		self::assertSame(-5, $b->readInt8());
		self::assertSame(-1000, $b->readInt16());
		self::assertSame(-70000, $b->readInt32());
	}

	public function testFloatAndDouble()
	{
		$src = TStream::fromMemory();
		$src->write(pack('G', 1.5) . pack('E', 3.141592653589793));   // big-endian float/double
		$src->seek(0);
		$b = new TBinaryStream($src, TByteOrder::BigEndian);
		self::assertEqualsWithDelta(1.5, $b->readFloat(), 1e-6);
		self::assertEqualsWithDelta(3.141592653589793, $b->readDouble(), 1e-12);
	}

	public function test64Bit()
	{
		if (PHP_INT_SIZE < 8) {
			self::markTestSkipped('64-bit integers require a 64-bit PHP build.');
		}
		$b = $this->over("\x01\x23\x45\x67\x89\xAB\xCD\xEF", TByteOrder::BigEndian);
		self::assertSame(0x0123456789ABCDEF, $b->readInt64());
	}

	public function testReadBytes()
	{
		$b = $this->over('header-and-body');
		self::assertSame('', $b->readBytes(0), 'A zero-length read returns empty without touching the stream.');
		self::assertSame('header', $b->readBytes(6));
		self::assertSame('-and-body', $b->getContents());
	}

	public function testUnexpectedEofThrows()
	{
		$b = $this->over("\x01");   // only one byte
		self::expectException(TIOException::class);
		$b->readUInt32();
	}

	public function testPlainReadStaysConsistentWithTypedReads()
	{
		// The buffer backs both: a typed read then a PSR read see the same position.
		$b = $this->over("\xAAhelloXYZ", TByteOrder::BigEndian);
		self::assertSame(0xAA, $b->readUInt8());
		self::assertSame('hello', $b->read(5));
		self::assertSame('XYZ', $b->getContents());
	}

	public function testTellTracksBytesRead()
	{
		$b = $this->over("\x01\x02\x03\x04\x05\x06\x07\x08");
		$b->readUInt16();
		self::assertSame(2, $b->tell());
		$b->readUInt32();
		self::assertSame(6, $b->tell());
	}

	public function testSeekRepositions()
	{
		$b = $this->over("\x00\x01\x02\x03\x04\x05\x06\x07");
		$b->readUInt32();
		$b->seek(1);                            // SEEK_SET
		self::assertSame(0x01, $b->readUInt8());
		$b->seek(2, SEEK_CUR);                  // skip two from the current position
		self::assertSame(0x04, $b->readUInt8());
		$b->seek(-1, SEEK_END);
		self::assertSame(0x07, $b->readUInt8());
	}

	public function testEofAfterDraining()
	{
		$b = $this->over("\x01\x02");
		self::assertFalse($b->eof());
		$b->readUInt16();
		self::assertSame('', $b->read(1));
		self::assertTrue($b->eof());
	}

	public function testReadsAcrossShortReadsFromNonSeekablePipe()
	{
		// A pipe may return fewer bytes than asked; readBytes() loops to assemble the field.
		$res = TTestIOHelper::pipeResource("\xDE\xAD\xBE\xEF");
		$inner = TTestIOHelper::resourceStream($res, false);
		$b = new TBinaryStream($inner, TByteOrder::BigEndian);
		self::assertSame(0xDEADBEEF, $b->readUInt32());
		TTestIOHelper::closeAny($res);
	}

	public function testByteOrderProperty()
	{
		$b = $this->over('');
		self::assertNull($b->getByteOrder());
		$b->setByteOrder(TByteOrder::BigEndian);
		self::assertSame(TByteOrder::BigEndian, $b->getByteOrder());
	}
}
