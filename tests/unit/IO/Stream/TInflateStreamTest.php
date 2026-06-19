<?php

use Prado\IO\Stream\IStreamDecoratorPooling;
use Prado\IO\Stream\TDeflateStream;
use Prado\IO\Stream\TInflateStream;
use Prado\IO\TStream;

class TInflateStreamTest extends PHPUnit\Framework\TestCase
{
	private string $plain = 'The quick brown fox. The quick brown fox. The quick brown fox.';

	public function testDecodesNativeZlib()
	{
		$compressed = gzcompress($this->plain);   // zlib (ZLIB_ENCODING_DEFLATE)
		$out = (new TInflateStream(TStream::fromString($compressed)))->getContents();
		self::assertSame($this->plain, $out);
	}

	public function testDecodesNativeGzip()
	{
		$compressed = gzencode($this->plain);      // gzip (ZLIB_ENCODING_GZIP)
		$out = (new TInflateStream(TStream::fromString($compressed), ZLIB_ENCODING_GZIP))->getContents();
		self::assertSame($this->plain, $out);
	}

	public function testDecodesNativeRaw()
	{
		$compressed = gzdeflate($this->plain);     // raw (ZLIB_ENCODING_RAW)
		$out = (new TInflateStream(TStream::fromString($compressed), ZLIB_ENCODING_RAW))->getContents();
		self::assertSame($this->plain, $out);
	}

	public function testRoundTripWithDeflateStream()
	{
		$path = tempnam(sys_get_temp_dir(), 'pradozlib');
		$deflate = new TDeflateStream(TStream::fromFile($path, 'wb'));
		$deflate->write($this->plain);
		$deflate->close();   // write-side: flushes the compressed bytes to the file
		$inflated = (new TInflateStream(TStream::fromFile($path, 'rb')))->getContents();
		@unlink($path);
		self::assertSame($this->plain, $inflated);
	}

	public function testEofAndTellTrackTransformedBytes()
	{
		$s = new TInflateStream(TStream::fromString(gzcompress($this->plain)));
		self::assertFalse($s->eof());
		$all = $s->getContents();
		self::assertSame(strlen($this->plain), strlen($all));
		self::assertTrue($s->eof());
		self::assertSame(strlen($this->plain), $s->tell());
		self::assertNull($s->getSize(), 'The decompressed size is not known ahead of time.');
	}

	public function testForwardOnly()
	{
		$s = new TInflateStream(TStream::fromString(gzcompress($this->plain)));
		self::assertFalse($s->isSeekable());
		self::assertFalse($s->isWritable());
		$this->expectException(\RuntimeException::class);
		$s->seek(0);
	}

	public function testRecycleReusesTheStreamForANewInput()
	{
		$s = new TInflateStream(TStream::fromString(gzcompress('alpha alpha alpha')));
		self::assertInstanceOf(IStreamDecoratorPooling::class, $s);
		self::assertSame('alpha alpha alpha', $s->getContents());
		self::assertTrue($s->eof());

		$s->recycle(TStream::fromString(gzcompress('beta beta beta beta')));   // recycle for a new source
		self::assertFalse($s->eof(), 'A recycled stream is not at end.');
		self::assertSame(0, $s->tell(), 'A recycled stream restarts its position.');
		self::assertSame('beta beta beta beta', $s->getContents());
	}

	public function testReleaseUnbindsTheInnerStream()
	{
		$inner = TStream::fromString(gzcompress('payload'));
		$s = new TInflateStream($inner);
		self::assertSame('payload', $s->getContents());

		self::assertSame($inner, $s->release(), 'release() returns the inner stream.');

		$s->recycle(TStream::fromString(gzcompress('again')));   // unbound, then reused
		self::assertSame('again', $s->getContents());
	}

	public function testReadZeroOrNegativeLengthReturnsEmpty()
	{
		$s = new TInflateStream(TStream::fromString(gzcompress($this->plain)));
		self::assertSame('', $s->read(0));
		self::assertSame('', $s->read(-5));
		self::assertSame(0, $s->tell(), 'A zero-length read consumes nothing.');
		self::assertSame($this->plain, $s->getContents());
	}

	public function testDecodesEmptyInput()
	{
		$s = new TInflateStream(TStream::fromString(gzcompress('')));
		self::assertSame('', $s->getContents());
		self::assertTrue($s->eof());
	}

	public function testSmallReadsSpanMultipleChunks()
	{
		$plain = str_repeat('The quick brown fox jumps. ', 4000);   // > the 8192 chunk, after decompression
		$s = new TInflateStream(TStream::fromString(gzcompress($plain)));
		$out = '';
		while (!$s->eof()) {
			$out .= $s->read(100);   // small reads exercise the offset buffer and its compaction
		}
		self::assertSame($plain, $out);
		self::assertSame(strlen($plain), $s->tell());
	}

	public function testIsReadableReflectsTheInnerStream()
	{
		$s = new TInflateStream(TStream::fromString(gzcompress($this->plain)));
		self::assertTrue($s->isReadable());
	}

	public function testRecycleWithoutAStreamResetsOverTheRewoundInner()
	{
		$inner = TStream::fromString(gzcompress($this->plain));
		$s = new TInflateStream($inner);
		self::assertSame($this->plain, $s->getContents());

		$inner->rewind();
		$s->recycle();   // keep the inner, reset the decode state
		self::assertFalse($s->eof());
		self::assertSame(0, $s->tell());
		self::assertSame($this->plain, $s->getContents());
	}

	public function testReleaseTwiceThenUseThrows()
	{
		$s = new TInflateStream(TStream::fromString(gzcompress($this->plain)));
		self::assertNotNull($s->release(), 'The first release returns the inner stream.');
		self::assertNull($s->release(), 'A second release reports no bound stream.');

		$this->expectException(\Error::class);
		$s->getContents();   // unbound: reading throws
	}
}
