<?php

use Prado\IO\Stream\IStreamDecoratorPooling;
use Prado\IO\Stream\TBufferStream;
use Prado\IO\Stream\TDeflateStream;
use Prado\IO\Stream\TInflateStream;
use Prado\IO\TStream;

class TDeflateStreamTest extends PHPUnit\Framework\TestCase
{
	private string $plain = 'Compress me. Compress me. Compress me. Compress me. Compress me.';

	/** Compresses $this->plain through a write-side TDeflateStream and returns the compressed bytes. */
	private function deflate(int $encoding, int $chunk = 0): string
	{
		$path = tempnam(sys_get_temp_dir(), 'pradodeflate');
		$gz = new TDeflateStream(TStream::fromFile($path, 'wb'), $encoding);
		foreach ($chunk > 0 ? str_split($this->plain, $chunk) : [$this->plain] as $part) {
			$gz->write($part);
		}
		$gz->close();   // flushes the final block and closes the file
		$compressed = (string) file_get_contents($path);
		@unlink($path);
		return $compressed;
	}

	public function testGzipOutputIsNativelyDecodable()
	{
		self::assertSame($this->plain, gzdecode($this->deflate(ZLIB_ENCODING_GZIP)));
	}

	public function testRoundTripThroughInflate()
	{
		$deflated = $this->deflate(ZLIB_ENCODING_DEFLATE);
		self::assertLessThan(strlen($this->plain), strlen($deflated), 'Repetitive input compresses.');
		$back = (new TInflateStream(TStream::fromString($deflated)))->getContents();
		self::assertSame($this->plain, $back);
	}

	public function testRawRoundTrip()
	{
		$deflated = $this->deflate(ZLIB_ENCODING_RAW);
		$back = (new TInflateStream(TStream::fromString($deflated), ZLIB_ENCODING_RAW))->getContents();
		self::assertSame($this->plain, $back);
	}

	public function testIncrementalWritesRoundTrip()
	{
		self::assertSame($this->plain, gzdecode($this->deflate(ZLIB_ENCODING_GZIP, 7)));
	}

	public function testTruncatedWithoutCloseDoesNotDecode()
	{
		$buffer = new TBufferStream();
		$gz = new TDeflateStream($buffer, ZLIB_ENCODING_GZIP);
		$gz->write($this->plain);                 // no close(): the final block is never flushed
		self::assertFalse(@gzdecode((string) $buffer), 'Output is incomplete until close() flushes the final block.');
	}

	public function testWriteOnlyForwardStream()
	{
		$s = new TDeflateStream(new TBufferStream());
		self::assertTrue($s->isWritable());
		self::assertFalse($s->isReadable());
		self::assertFalse($s->isSeekable());
		self::assertNull($s->getSize());
		$this->expectException(\RuntimeException::class);
		$s->read(4);
	}

	public function testRecycleReusesTheStreamForANewOutput()
	{
		$path1 = tempnam(sys_get_temp_dir(), 'pradodeflate');
		$s = new TDeflateStream(TStream::fromFile($path1, 'wb'), ZLIB_ENCODING_GZIP);
		self::assertInstanceOf(IStreamDecoratorPooling::class, $s);
		$s->write('alpha alpha alpha');
		$s->close();
		self::assertSame('alpha alpha alpha', gzdecode((string) file_get_contents($path1)));

		$path2 = tempnam(sys_get_temp_dir(), 'pradodeflate');
		$s->recycle(TStream::fromFile($path2, 'wb'));   // recycle the same object for a fresh output
		$s->write('beta beta beta beta');
		$s->close();
		self::assertSame('beta beta beta beta', gzdecode((string) file_get_contents($path2)));

		@unlink($path1);
		@unlink($path2);
	}

	/** Compresses a list of chunks, optionally in SyncFlush (streaming) mode, and returns the compressed bytes. */
	private function deflateChunks(array $chunks, bool $syncFlush): string
	{
		$path = tempnam(sys_get_temp_dir(), 'pradodeflate');
		$s = new TDeflateStream(TStream::fromFile($path, 'wb'), ZLIB_ENCODING_GZIP);
		$s->setSyncFlush($syncFlush);
		foreach ($chunks as $chunk) {
			$s->write($chunk);
		}
		$s->close();
		$out = (string) file_get_contents($path);
		@unlink($path);
		return $out;
	}

	public function testBufferedWritesCompressSmallerThanSyncFlushedWrites()
	{
		$chunks = array_fill(0, 200, 'repeated payload ');   // many small, repetitive writes
		$buffered = $this->deflateChunks($chunks, false);    // ZLIB_NO_FLUSH (default)
		$flushed = $this->deflateChunks($chunks, true);      // ZLIB_SYNC_FLUSH per write

		self::assertLessThan(strlen($flushed), strlen($buffered), 'Buffering across writes beats a sync flush per write.');
		$plain = implode('', $chunks);
		self::assertSame($plain, gzdecode($buffered));
		self::assertSame($plain, gzdecode($flushed));
	}

	public function testSyncFlushEmitsDecodableOutputMidStream()
	{
		$buffer = new TBufferStream();
		$s = new TDeflateStream($buffer, ZLIB_ENCODING_RAW);
		$s->setSyncFlush(true);
		$s->write('hello world');   // streaming mode: emitted and decodable without close()

		$ctx = inflate_init(ZLIB_ENCODING_RAW);
		self::assertSame('hello world', inflate_add($ctx, (string) $buffer, ZLIB_SYNC_FLUSH));
	}

	public function testReleaseUnbindsTheInnerStream()
	{
		$inner = new TBufferStream();
		$s = new TDeflateStream($inner);
		self::assertSame($inner, $s->release(), 'release() returns the inner stream.');

		$path = tempnam(sys_get_temp_dir(), 'pradodeflate');
		$s->recycle(TStream::fromFile($path, 'wb'));   // unbound, then reused
		$s->write('after release');
		$s->close();
		$back = (new TInflateStream(TStream::fromFile($path, 'rb')))->getContents();
		@unlink($path);
		self::assertSame('after release', $back);
	}

	public function testWriteEmptyStringAcceptsNothing()
	{
		$buffer = new TBufferStream();
		$s = new TDeflateStream($buffer);
		self::assertSame(0, $s->write(''));
		self::assertSame('', (string) $buffer, 'An empty write produces no output.');
	}

	public function testCloseWithoutWriteEmitsAValidEmptyStream()
	{
		$path = tempnam(sys_get_temp_dir(), 'pradodeflate');
		$s = new TDeflateStream(TStream::fromFile($path, 'wb'), ZLIB_ENCODING_GZIP);
		$s->close();   // never written: still emits a valid, decodable empty stream
		$out = (string) file_get_contents($path);
		@unlink($path);
		self::assertNotSame('', $out, 'Closing emits the empty gzip member.');
		self::assertSame('', gzdecode($out));
	}

	public function testWriteAfterCloseThrows()
	{
		$s = new TDeflateStream(new TBufferStream());
		$s->write('x');
		$s->close();
		$this->expectException(\RuntimeException::class);
		$s->write('y');
	}

	public function testDoubleCloseIsIdempotent()
	{
		$path = tempnam(sys_get_temp_dir(), 'pradodeflate');
		$s = new TDeflateStream(TStream::fromFile($path, 'wb'), ZLIB_ENCODING_GZIP);
		$s->write('once');
		$s->close();
		$s->close();   // no double-finish, no error
		$out = (string) file_get_contents($path);
		@unlink($path);
		self::assertSame('once', gzdecode($out));
	}

	public function testDetachFinishesAndReturnsTheResource()
	{
		$path = tempnam(sys_get_temp_dir(), 'pradodeflate');
		$s = new TDeflateStream(TStream::fromFile($path, 'wb'), ZLIB_ENCODING_GZIP);
		$s->write('detached');
		$resource = $s->detach();   // flushes the final block, then hands back the inner resource
		self::assertIsResource($resource);
		fclose($resource);
		$out = (string) file_get_contents($path);
		@unlink($path);
		self::assertSame('detached', gzdecode($out));
	}

	public function testToStringIsEmpty()
	{
		$s = new TDeflateStream(new TBufferStream());
		self::assertSame('', (string) $s);
	}

	public function testRecycleKeepsTheSyncFlushMode()
	{
		$s = new TDeflateStream(new TBufferStream());
		$s->setSyncFlush(true);
		$s->recycle(new TBufferStream());
		self::assertTrue($s->getSyncFlush(), 'recycle() keeps the configured SyncFlush mode.');
	}
}
