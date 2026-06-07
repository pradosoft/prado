<?php

use Prado\IO\TFileStream;
use Prado\IO\TInputStream;
use Prado\IO\TMemoryStream;
use Prado\IO\TOutputStream;
use Prado\IO\TStdErrStream;
use Prado\IO\TStdInStream;
use Prado\IO\TStdOutStream;
use Prado\IO\TStream;
use Prado\IO\TTempStream;

/**
 * Smoke tests for the concrete {@see \Prado\IO\TStream} subclasses: each fixes a URI,
 * a capability set, and a resource-ownership policy.
 */
class TStreamSubclassesTest extends PHPUnit\Framework\TestCase
{
	public function testMemoryStreamRoundTrips()
	{
		$s = new TMemoryStream();
		self::assertInstanceOf(TStream::class, $s);
		self::assertSame('php://memory', TMemoryStream::URI);
		self::assertTrue($s->isReadable() && $s->isWritable() && $s->isSeekable());
		self::assertTrue($s->getOwnsResource(), 'A memory stream owns its handle.');
		$s->write('round');
		$s->rewind();
		self::assertSame('round', $s->getContents());
		$s->close();
	}

	public function testTempStreamRoundTrips()
	{
		$s = new TTempStream();
		self::assertSame('php://temp', TTempStream::URI);
		self::assertTrue($s->isReadable() && $s->isWritable() && $s->isSeekable());
		self::assertTrue($s->getOwnsResource());
		$s->write('trip');
		$s->rewind();
		self::assertSame('trip', $s->getContents());
		$s->close();
	}

	public function testFileStreamRoundTrips()
	{
		$path = tempnam(sys_get_temp_dir(), 'fs');
		try {
			$w = new TFileStream($path, 'wb');
			self::assertTrue($w->getOwnsResource(), 'A file stream owns its handle.');
			self::assertSame(5, $w->write('bytes'));
			$w->close();

			$r = new TFileStream($path, 'rb');
			self::assertTrue($r->isReadable());
			self::assertFalse($r->isWritable(), 'Opened "rb", the file stream is read-only.');
			self::assertSame('bytes', $r->getContents());
			$r->close();
		} finally {
			@unlink($path);
		}
	}

	public function testInputStreamIsReadOnly()
	{
		$s = new TInputStream();
		self::assertSame('php://input', TInputStream::URI);
		self::assertTrue($s->isReadable());
		self::assertFalse($s->isWritable(), 'php://input is read-only.');
		$s->close();
	}

	public function testOutputStreamIsWriteOnlyAndFlushable()
	{
		$s = new TOutputStream();
		self::assertSame('php://output', TOutputStream::URI);
		self::assertFalse($s->isReadable());
		self::assertTrue($s->isWritable(), 'php://output is write-only.');
		self::assertTrue(method_exists($s, 'flush'), 'TOutputStream is flushable.');
		$s->close();
	}

	public function testStdStreamsCapabilitiesAndNonOwning()
	{
		$cases = [
			[new TStdInStream(), TStdInStream::URI, 'php://stdin', true, false],
			[new TStdOutStream(), TStdOutStream::URI, 'php://stdout', false, true],
			[new TStdErrStream(), TStdErrStream::URI, 'php://stderr', false, true],
		];
		foreach ($cases as [$s, $uri, $expectedUri, $readable, $writable]) {
			self::assertSame($expectedUri, $uri);
			self::assertSame($readable, $s->isReadable(), "{$uri} readable");
			self::assertSame($writable, $s->isWritable(), "{$uri} writable");
			self::assertFalse($s->getOwnsResource(), "{$uri} is non-owning (never closes the process handle).");
		}
	}
}
