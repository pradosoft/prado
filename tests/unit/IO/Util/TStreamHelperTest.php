<?php

use Prado\IO\Stream\TFnStream;
use Prado\IO\Stream\TPumpStream;
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

	// ---- copyRange ------------------------------------------------------------

	public function testCopyRangeCopiesAnExactWindow()
	{
		$src = TStream::fromString('HEADER' . 'PAYLOAD' . 'TRAILER');
		$dst = TStream::fromMemory();
		self::assertSame(7, TStreamHelper::copyRange($src, 6, 7, $dst));
		$dst->rewind();
		self::assertSame('PAYLOAD', $dst->getContents());
	}

	public function testCopyRangeSpansMultipleChunks()
	{
		$payload = random_bytes(3 * TStreamHelper::CHUNK_SIZE + 123);
		$src = TStream::fromString('..' . $payload . '..');
		$dst = TStream::fromMemory();
		self::assertSame(strlen($payload), TStreamHelper::copyRange($src, 2, strlen($payload), $dst));
		$dst->rewind();
		self::assertSame($payload, $dst->getContents(), 'Every byte of a many-chunk range arrives in order.');
	}

	public function testCopyRangeCopiesNothingForAZeroLength()
	{
		$src = TStream::fromString('HEADER');
		$dst = TStream::fromMemory();
		self::assertSame(0, TStreamHelper::copyRange($src, 3, 0, $dst), 'A zero-length range copies zero bytes.');
		$dst->rewind();
		self::assertSame('', $dst->getContents());
	}

	public function testCopyRangeCopiesToTheSourceEnd()
	{
		$src = TStream::fromString('HEADER' . 'PAYLOAD');
		$dst = TStream::fromMemory();
		self::assertSame(7, TStreamHelper::copyRange($src, 6, 7, $dst), 'A range ending exactly at end of stream copies without throwing.');
		$dst->rewind();
		self::assertSame('PAYLOAD', $dst->getContents());
	}

	public function testCopyRangeRejectsANegativeLength()
	{
		$this->expectException(\InvalidArgumentException::class);
		TStreamHelper::copyRange(TStream::fromString('abc'), 0, -1, TStream::fromMemory());
	}

	public function testCopyRangeThrowsWhenTheSourceEndsBeforeTheRange()
	{
		$this->expectException(\RuntimeException::class);
		TStreamHelper::copyRange(TStream::fromString('short'), 0, 999, TStream::fromMemory());
	}

	public function testCopyRangeThrowsOnANonSeekableSource()
	{
		$pump = new TPumpStream(fn (int $n): string => str_repeat('x', $n));
		$this->expectException(\RuntimeException::class);
		TStreamHelper::copyRange($pump, 4, 4, TStream::fromMemory());
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

	public function testReadLineOnANonSeekableStreamDoesNotOvershoot()
	{
		$pump = new TPumpStream(function (int $n) {
			static $data = "line1\nline2\n";
			$chunk = substr($data, 0, $n);
			$data = substr($data, $n);
			return $chunk;
		});
		self::assertSame("line1\n", TStreamHelper::readLine($pump));
		self::assertSame("line2\n", TStreamHelper::readLine($pump), 'Byte-wise reads leave the next line intact.');
	}

	// ---- copyToString ---------------------------------------------------------

	public function testCopyToStringReadsAllAndSpansChunks()
	{
		$data = random_bytes(3 * TStreamHelper::CHUNK_SIZE + 123);
		self::assertSame($data, TStreamHelper::copyToString(TStream::fromString($data)));
	}

	public function testCopyToStringHonorsMaxLengthAndPosition()
	{
		$s = TStream::fromString('hello helper world');
		self::assertSame('hello', TStreamHelper::copyToString($s, 5));
		self::assertSame(' helper', TStreamHelper::copyToString($s, 7), 'The copy resumes at the stream position.');
		self::assertSame(' world', TStreamHelper::copyToString($s), 'Unbounded reads the remainder.');
	}

	// ---- copy/hash robustness -------------------------------------------------

	public function testCopyToStreamLoopsOverShortWrites()
	{
		// A destination accepting one byte per call; the copy must still land completely.
		$sink = '';
		$dest = new TFnStream([
			'isWritable' => fn () => true,
			'write' => function (string $bytes) use (&$sink) {
				$sink .= $bytes[0];
				return 1;
			},
		]);
		self::assertSame(6, TStreamHelper::copyToStream(TStream::fromString('abcdef'), $dest));
		self::assertSame('abcdef', $sink, 'Short writes are retried until the chunk lands.');
	}

	public function testCopyToStreamThrowsWhenTheDestinationStops()
	{
		$dest = new TFnStream([
			'isWritable' => fn () => true,
			'write' => fn () => 0,
		]);
		self::expectException(\RuntimeException::class);
		TStreamHelper::copyToStream(TStream::fromString('abc'), $dest);
	}

	public function testHashNonSeekableFromCurrentPosition()
	{
		$parts = ['alpha', 'beta', ''];
		$i = 0;
		$pump = new TPumpStream(function () use (&$parts, &$i) {
			return $parts[$i++] ?? '';
		});
		self::assertSame(hash('crc32b', 'alphabeta'), TStreamHelper::hash($pump, 'crc32b'), 'A non-seekable stream hashes from its current position.');
	}

}
