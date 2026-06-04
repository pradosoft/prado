<?php

use Prado\Exceptions\TIOException;
use Prado\IO\TFileStream;
use Prado\IO\TInputStream;
use Prado\IO\TMemoryStream;
use Prado\IO\TOutputStream;
use Prado\IO\TStdErrStream;
use Prado\IO\TStdInStream;
use Prado\IO\TStdOutStream;
use Prado\IO\TStream;
use Prado\IO\TTempStream;

class TStreamPresetsTest extends PHPUnit\Framework\TestCase
{
	protected function tearDown(): void
	{
		TTestIOHelper::removeTempFiles();
	}

	public function testFileStream()
	{
		$path = TTestIOHelper::tempFile('file-data', 'prado-tfs');
		$s = new TFileStream($path, 'r+b');
		self::assertInstanceOf(TStream::class, $s);
		self::assertTrue($s->getOwnsResource());
		self::assertTrue($s->isReadable());
		self::assertTrue($s->isWritable());
		self::assertSame('file-data', $s->getContents());
		self::assertSame($path, $s->getURI());
		$s->close();
	}

	public function testFileStreamMissingThrows()
	{
		self::expectException(TIOException::class);
		new TFileStream('/no/such/dir/missing.bin', 'rb');
	}

	public function testMemoryStream()
	{
		$s = new TMemoryStream();
		self::assertTrue($s->isReadable());
		self::assertTrue($s->isWritable());
		self::assertTrue($s->getOwnsResource());
		$s->write('mem');
		$s->seek(0);
		self::assertSame('mem', $s->getContents());
		$s->close();
	}

	public function testTempStream()
	{
		$s = new TTempStream(1024);
		self::assertTrue($s->isReadable());
		self::assertTrue($s->isWritable());
		$s->write('temp-data');
		$s->seek(0);
		self::assertSame('temp-data', $s->read(9));
		$s->close();
	}

	public function testStdInStream()
	{
		$s = new TStdInStream();
		self::assertFalse($s->getOwnsResource(), 'STDIN must be non-owning.');
		self::assertTrue($s->isReadable());
		self::assertFalse($s->isWritable());
		self::assertSame(TStdInStream::URI, $s->getURI());
		// Do not read (would block); just verify closing leaves the fd intact.
		$resource = $s->getResource();
		$s->close();
		self::assertTrue(is_resource($resource), 'Non-owning close must leave STDIN open.');
	}

	public function testStdOutStreamIsNonOwningWritableFlushable()
	{
		$s = new TStdOutStream();
		self::assertFalse($s->getOwnsResource(), 'STDOUT must be non-owning.');
		self::assertTrue($s->isWritable());
		self::assertFalse($s->isReadable());
		self::assertTrue(method_exists($s, 'flush'));
		$resource = $s->getResource();
		$s->close();
		self::assertTrue(is_resource($resource), 'Non-owning close must leave STDOUT open.');
	}

	public function testStdErrStreamIsNonOwningWriteOnly()
	{
		$s = new TStdErrStream();
		self::assertFalse($s->getOwnsResource(), 'STDERR must be non-owning.');
		self::assertTrue($s->isWritable());
		self::assertFalse($s->isReadable());
		self::assertSame(TStdErrStream::URI, $s->getURI());
		$resource = $s->getResource();
		$s->close();
		self::assertTrue(is_resource($resource), 'Non-owning close must leave STDERR open.');
	}

	public function testInputStreamReadOnlyOwned()
	{
		$s = new TInputStream();
		self::assertTrue($s->getOwnsResource());
		self::assertTrue($s->isReadable());
		self::assertFalse($s->isWritable());
		self::assertSame(TInputStream::URI, $s->getURI());
		$s->close();
	}

	public function testOutputStreamWritesThroughOutputBuffer()
	{
		$s = new TOutputStream();
		self::assertTrue($s->getOwnsResource());
		self::assertTrue($s->isWritable());
		self::assertFalse($s->isReadable());
		self::assertTrue(method_exists($s, 'flush'));

		ob_start();
		$s->write('echoed');
		$captured = ob_get_clean();
		self::assertSame('echoed', $captured, 'php://output writes go through the output buffer.');
		$s->close();
	}

	public function testOutputStreamFlushReturnsBool()
	{
		// Flush with nothing buffered so no output leaks out of the test.
		ob_start();
		$s = new TOutputStream();
		$result = $s->flush();
		ob_end_clean();
		self::assertIsBool($result);
		$s->close();
	}

	public function testStdOutStreamFlushReturnsBool()
	{
		$s = new TStdOutStream();          // non-owning; flush() must not error
		self::assertIsBool($s->flush());
		$s->close();
	}

	public function testStdErrStreamIsNotFlushable()
	{
		// By design only stdout/output carry TFlushableStreamTrait; stderr is unbuffered.
		self::assertFalse(method_exists(new TStdErrStream(), 'flush'));
	}
}
