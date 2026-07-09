<?php

use Prado\IO\Stream\TBufferStream;
use Psr\Http\Message\StreamInterface;

class TBufferStreamTest extends PHPUnit\Framework\TestCase
{
	public function testIsAStreamInterface()
	{
		self::assertInstanceOf(StreamInterface::class, new TBufferStream());
	}

	public function testFifoWriteThenRead()
	{
		$b = new TBufferStream();
		self::assertSame(3, $b->write('AAA'));
		self::assertSame(3, $b->write('BBB'));
		self::assertSame(6, $b->getSize());
		self::assertSame('AAAB', $b->read(4), 'Reads drain from the front.');
		self::assertSame('BB', $b->read(100), 'A read past the end returns only what remains.');
		self::assertSame('', $b->read(100));
		self::assertTrue($b->eof());
	}

	public function testInPlaceAppendAccumulates()
	{
		$b = new TBufferStream();
		for ($i = 0; $i < 1000; $i++) {
			$b->write('x');
		}
		self::assertSame(1000, $b->getSize(), 'Repeated in-place appends accumulate correctly.');
		self::assertSame(str_repeat('x', 1000), (string) $b);
	}

	public function testToStringIsNonDestructive()
	{
		$b = new TBufferStream();
		$b->write('keep');
		self::assertSame('keep', (string) $b);
		self::assertSame('keep', (string) $b, 'Casting to string does not drain the buffer.');
		self::assertSame(4, $b->getSize());
	}

	public function testGetContentsDrainsAndIsCowSafe()
	{
		$b = new TBufferStream();
		$b->write('payload');
		$contents = $b->getContents();
		self::assertSame('payload', $contents);
		self::assertSame(0, $b->getSize(), 'getContents() drains the buffer.');

		// A subsequent write must not mutate the already-returned string (by-reference safety).
		$b->write('other');
		self::assertSame('payload', $contents, 'The returned contents are independent of later writes.');
	}

	public function testReadNonPositiveLengthReturnsEmpty()
	{
		$b = new TBufferStream();
		$b->write('data');
		self::assertSame('', $b->read(0));
		self::assertSame('', $b->read(-5));
		self::assertSame(4, $b->getSize(), 'A non-positive read leaves the buffer intact.');
	}

	public function testCapabilitiesAndCloseIsTerminal()
	{
		$b = new TBufferStream();
		$b->write('data');
		self::assertTrue($b->isReadable());
		self::assertTrue($b->isWritable());
		self::assertFalse($b->isSeekable());

		$b->close();
		self::assertNull($b->getSize(), 'A closed stream has an unknown size.');
		self::assertTrue($b->eof());
		self::assertFalse($b->isReadable(), 'A closed stream is no longer readable.');
		self::assertFalse($b->isWritable(), 'A closed stream is no longer writable.');
	}

	public function testPositionOperationsThrow()
	{
		$b = new TBufferStream();
		self::expectException(\RuntimeException::class);
		$b->tell();
	}

	public function testSeekThrows()
	{
		$b = new TBufferStream();
		self::expectException(\RuntimeException::class);
		$b->seek(0);
	}

	public function testRewindThrows()
	{
		$b = new TBufferStream();
		self::expectException(\RuntimeException::class);
		$b->rewind();
	}

	public function testCompactionPreservesDataAcrossTheChunkBoundary()
	{
		$b = new TBufferStream();
		$in = '';
		for ($i = 0; $i < 4000; $i++) {
			$in .= sprintf('%09d|', $i);   // 40000 unique bytes, so any mis-slice would show
		}
		$b->write($in);

		$out = '';
		while (!$b->eof()) {
			$out .= $b->read(1337);        // odd reads drive the pos >= CHUNK_SIZE compaction mid-drain
		}
		self::assertSame($in, $out, 'Compaction preserves every byte across the chunk boundary.');
		self::assertSame(0, $b->getSize());
		self::assertTrue($b->eof());
	}

	public function testInterleavedWriteBehindAConsumedPrefix()
	{
		$b = new TBufferStream();
		$b->write('abc');
		self::assertSame('ab', $b->read(2));   // consumes a prefix, leaving _bufferPos > 0
		$b->write('def');                       // append behind the consumed prefix
		self::assertSame(4, $b->getSize(), 'The unread count spans the old remainder and the new bytes.');
		self::assertSame('cdef', $b->read(4), 'FIFO order holds across a write behind a read.');
		self::assertTrue($b->eof());
	}

	public function testWriteMidLargeDrainStreamsTheTailIntact()
	{
		$b = new TBufferStream();
		$b->write(str_repeat('A', 20000));
		$b->read(9000);
		$b->read(9000);              // pos crosses CHUNK_SIZE and outweighs the remainder: compaction fires
		$b->write('TAIL');

		$rest = '';
		while (!$b->eof()) {
			$rest .= $b->read(5000);
		}
		self::assertSame(2004, strlen($rest));
		self::assertSame('TAIL', substr($rest, -4), 'A write during a large drain comes out last, intact.');
	}

	public function testToStringAfterPartialReadReturnsTheUnreadRemainder()
	{
		$b = new TBufferStream();
		$b->write('abcdef');
		self::assertSame('ab', $b->read(2));
		self::assertSame('cdef', (string) $b);
		self::assertSame('cdef', (string) $b, 'Casting after a partial read stays non-destructive.');
		self::assertSame(4, $b->getSize());
		self::assertSame('cd', $b->read(2));
	}

	public function testDetachReturnsNullAndIsTerminal()
	{
		$b = new TBufferStream();
		$b->write('data');
		self::assertNull($b->detach(), 'A buffer has no resource to return.');
		self::assertNull($b->getSize());
		self::assertTrue($b->eof());
		self::assertFalse($b->isReadable());
		self::assertFalse($b->isWritable());
	}

	public function testReadAfterCloseThrows()
	{
		$b = new TBufferStream();
		$b->write('data');
		$b->close();
		self::expectException(\RuntimeException::class);
		$b->read(1);
	}

	public function testWriteAfterCloseThrows()
	{
		$b = new TBufferStream();
		$b->close();
		self::expectException(\RuntimeException::class);
		$b->write('x');
	}

	public function testGetContentsAfterDetachThrows()
	{
		$b = new TBufferStream();
		$b->write('data');
		$b->detach();
		self::expectException(\RuntimeException::class);
		$b->getContents();
	}

	public function testEofFlipsBackToFalseAfterAWrite()
	{
		$b = new TBufferStream();
		self::assertTrue($b->eof());
		$b->write('x');
		self::assertFalse($b->eof());
		$b->read(1);
		self::assertTrue($b->eof());
		$b->write('y');
		self::assertFalse($b->eof(), 'A write after draining reopens the buffer.');
	}

	public function testGetMetadataIsEmpty()
	{
		$b = new TBufferStream();
		self::assertSame([], $b->getMetadata());
		self::assertNull($b->getMetadata('uri'));
	}

	public function testResetRevivesAClosedBufferAndIsFluent()
	{
		$b = new TBufferStream();
		$b->write('data');
		$b->close();
		self::assertFalse($b->isWritable(), 'The stream is closed.');

		self::assertSame($b, $b->reset(), 'reset() returns the buffer for chaining.');
		self::assertTrue($b->isWritable(), 'reset() revives a closed buffer.');
		self::assertTrue($b->isReadable());
		self::assertSame(0, $b->getSize());
		self::assertTrue($b->eof());

		$b->write('again');
		self::assertSame('again', $b->read(5), 'A revived buffer round-trips again.');
	}

	public function testResetDiscardsPendingBytesOnAnOpenBuffer()
	{
		$b = new TBufferStream();
		$b->write('unread');
		$b->reset();
		self::assertSame(0, $b->getSize(), 'reset() discards pending bytes.');
		self::assertTrue($b->eof());
		self::assertSame(2, $b->write('ok'));   // still usable
	}
}
