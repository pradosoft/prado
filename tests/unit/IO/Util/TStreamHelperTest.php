<?php

use Prado\IO\TStream;
use Prado\IO\Util\TStreamHelper;

class TStreamHelperTest extends PHPUnit\Framework\TestCase
{
	public function testCopyToStreamCopiesAll()
	{
		$src = TStream::fromString('hello world');
		$dst = TStream::fromMemory();
		$n = TStreamHelper::copyToStream($src, $dst);
		self::assertSame(11, $n);
		$dst->rewind();
		self::assertSame('hello world', $dst->getContents());
	}

	public function testCopyToStreamRespectsMaxLength()
	{
		$src = TStream::fromString('hello world');
		$dst = TStream::fromMemory();
		$n = TStreamHelper::copyToStream($src, $dst, 5);
		self::assertSame(5, $n);
		$dst->rewind();
		self::assertSame('hello', $dst->getContents());
		self::assertSame(' world', $src->getContents(), 'The source is left after the copied region.');
	}

	public function testCopyToStreamEmptySourceCopiesNothing()
	{
		$src = TStream::fromMemory();   // empty
		$dst = TStream::fromMemory();
		self::assertSame(0, TStreamHelper::copyToStream($src, $dst), 'Copying an empty source copies zero bytes.');
		$dst->rewind();
		self::assertSame('', $dst->getContents());
	}

	public function testHashMatchesNativeAndRestoresPosition()
	{
		$data = 'The quick brown fox';
		$s = TStream::fromString($data);
		$s->seek(4);                                   // a non-zero starting position
		self::assertSame(hash('sha256', $data), TStreamHelper::hash($s));
		self::assertSame(4, $s->tell(), 'The position is restored after hashing.');
	}

	public function testHashRawOutputAndAlgorithm()
	{
		$data = 'payload';
		$s = TStream::fromString($data);
		self::assertSame(hash('crc32b', $data), TStreamHelper::hash($s, 'crc32b'));
		self::assertSame(hash('sha1', $data, true), TStreamHelper::hash($s, 'sha1', true));
	}

	public function testReadLineStopsAtNewline()
	{
		$s = TStream::fromString("first\nsecond\nthird");
		self::assertSame("first\n", TStreamHelper::readLine($s));
		self::assertSame("second\n", TStreamHelper::readLine($s));
		self::assertSame('third', TStreamHelper::readLine($s), 'The last line has no trailing newline.');
		self::assertSame('', TStreamHelper::readLine($s), 'End of stream yields an empty line.');
	}

	public function testReadLineRespectsMaxLength()
	{
		$s = TStream::fromString("abcdef\n");
		self::assertSame('abc', TStreamHelper::readLine($s, 4), 'Reads up to maxLength - 1 bytes.');
	}
}
