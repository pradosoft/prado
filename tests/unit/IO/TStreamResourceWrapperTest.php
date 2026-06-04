<?php

use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\TStream;
use Prado\IO\TStreamResourceWrapper;

/**
 * Unit tests for {@see \Prado\IO\TStreamResourceWrapper}, which exposes a PSR-7
 * {@see \Psr\Http\Message\StreamInterface} as a native PHP stream resource.
 *
 * The dependency-free {@see TTestPsrStream} double drives the contract against an
 * arbitrary StreamInterface, while {@see TTestIOHelper} supplies real {@see TStream}s
 * for the resource-API interop cases.
 */
class TStreamResourceWrapperTest extends PHPUnit\Framework\TestCase
{
	protected function tearDown(): void
	{
		TTestIOHelper::removeTempFiles();
	}

	public function testReturnsNativeResource()
	{
		$res = TStreamResourceWrapper::getResource(new TTestPsrStream('hello'));
		self::assertTrue(is_resource($res));
		fclose($res);
	}

	public function testReadThroughResourceApi()
	{
		$res = TStreamResourceWrapper::getResource(new TTestPsrStream("line1\nline2\n"));
		self::assertSame("line1\n", fgets($res));
		self::assertSame("line2\n", stream_get_contents($res));
		self::assertTrue(feof($res));
		fclose($res);
	}

	public function testReadWithResourceOnlyApi()
	{
		// fgetcsv only accepts a real resource; this is the wrapper's reason to exist.
		$res = TStreamResourceWrapper::getResource(new TTestPsrStream("name,score\nAda,99\n"));
		self::assertSame(['name', 'score'], fgetcsv($res));
		self::assertSame(['Ada', '99'], fgetcsv($res));
		fclose($res);
	}

	public function testWriteRoundTripReachesUnderlyingStream()
	{
		$stream = new TTestPsrStream('', true, true, true);
		$res = TStreamResourceWrapper::getResource($stream);
		self::assertSame(12, fwrite($res, 'written-data'));
		fclose($res);
		self::assertSame('written-data', (string) $stream, 'Writes land in the underlying stream.');
	}

	public function testSeekOnSeekableStream()
	{
		$res = TStreamResourceWrapper::getResource(new TTestPsrStream('0123456789'));
		self::assertSame(0, fseek($res, 5));
		self::assertSame('5', fread($res, 1));
		rewind($res);
		self::assertSame('0', fread($res, 1));
		fclose($res);
	}

	public function testSeekOnNonSeekableStreamFails()
	{
		$res = TStreamResourceWrapper::getResource(new TTestPsrStream('abc', true, true, false));
		self::assertSame(-1, @fseek($res, 1), 'fseek reports failure for a non-seekable stream.');
		fclose($res);
	}

	public function testReadOnlyStreamOpensReadMode()
	{
		$res = TStreamResourceWrapper::getResource(new TTestPsrStream('readme', true, false, true));
		self::assertSame('readme', stream_get_contents($res));
		self::assertSame('r', stream_get_meta_data($res)['mode'] ?? null);
		fclose($res);
	}

	public function testWriteOnlyStreamOpensWriteMode()
	{
		$stream = new TTestPsrStream('', false, true, true);
		$res = TStreamResourceWrapper::getResource($stream);
		self::assertSame('w', stream_get_meta_data($res)['mode'] ?? null);
		fwrite($res, 'out');
		fclose($res);
		self::assertSame('out', (string) $stream);
	}

	public function testReadWriteStreamOpensReadPlusMode()
	{
		$res = TStreamResourceWrapper::getResource(new TTestPsrStream('rw', true, true, true));
		self::assertSame('r+', stream_get_meta_data($res)['mode'] ?? null);
		fclose($res);
	}

	public function testUnusableStreamThrows()
	{
		$this->expectException(TInvalidDataValueException::class);
		TStreamResourceWrapper::getResource(new TTestPsrStream('x', false, false, false));
	}

	public function testFstatReportsSize()
	{
		$res = TStreamResourceWrapper::getResource(new TTestPsrStream('twelve bytes'));
		$stat = fstat($res);
		self::assertSame(12, $stat['size']);
		fclose($res);
	}

	public function testRegistration()
	{
		TStreamResourceWrapper::register();
		self::assertTrue(TStreamResourceWrapper::isRegistered());
		self::assertContains(TStreamResourceWrapper::PROTOCOL, TStreamResourceWrapper::getRegisteredWrappers());
		TStreamResourceWrapper::register();   // idempotent: a second call must not warn or fail
		self::assertTrue(TStreamResourceWrapper::isRegistered());
	}

	public function testClosingResourceLeavesUnderlyingStreamOpen()
	{
		$stream = TStream::fromString('persist');
		$res = TStreamResourceWrapper::getResource($stream);
		self::assertSame('persist', stream_get_contents($res));
		fclose($res);
		self::assertTrue($stream->isReadable(), 'Underlying stream stays open after the resource view closes.');
		self::assertSame('persist', (string) $stream);
		$stream->close();
	}

	public function testBridgesRealTStream()
	{
		// The wrapper works against a concrete TStream, not only the PSR double.
		$stream = TStream::fromMemory('r+b');
		$stream->write('round');
		$res = TStreamResourceWrapper::getResource($stream);
		rewind($res);
		self::assertSame('round', stream_get_contents($res));
		fclose($res);
		$stream->close();
	}
}
