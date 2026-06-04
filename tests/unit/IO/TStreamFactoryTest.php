<?php

use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\TStream;
use Prado\IO\TStreamFactory;
use Prado\IO\TStreamResourceWrapper;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class TStreamFactoryTest extends PHPUnit\Framework\TestCase
{
	private TStreamFactory $factory;

	protected function setUp(): void
	{
		$this->factory = new TStreamFactory();
	}

	protected function tearDown(): void
	{
		TTestIOHelper::removeTempFiles();
	}

	public function testImplementsPsr17()
	{
		self::assertInstanceOf(StreamFactoryInterface::class, $this->factory);
	}

	public function testCreateStream()
	{
		$s = $this->factory->createStream('content');
		self::assertInstanceOf(StreamInterface::class, $s);
		self::assertSame('content', (string) $s);
		$s->close();
	}

	public function testCreateStreamFromFile()
	{
		$path = TTestIOHelper::tempFile('on-disk', 'prado-tsf');
		$s = $this->factory->createStreamFromFile($path, 'r');
		self::assertSame('on-disk', $s->getContents());
		$s->close();
	}

	public function testCreateStreamFromResourceOwns()
	{
		$res = TTestIOHelper::dataResource('res-data', 'r+b');
		$s = $this->factory->createStreamFromResource($res);
		self::assertTrue($s->getOwnsResource(), 'Factory-created stream owns the resource.');
		$s->seek(0);
		self::assertSame('res-data', $s->getContents());
		$s->close();
		self::assertFalse(is_resource($res), 'Owned resource closed with the stream.');
	}

	public function testAsResourceReadable()
	{
		$s = TStream::fromString('bridge-me');
		$res = TStream::asResource($s);
		self::assertTrue(is_resource($res));
		self::assertSame('bridge-me', stream_get_contents($res));
		fclose($res);
		$s->close();
	}

	public function testAsResourceWritableRoundTrip()
	{
		$s = TStream::fromMemory('r+b');
		$res = TStream::asResource($s);
		fwrite($res, 'written-through');
		$s->seek(0);
		self::assertSame('written-through', $s->getContents());
		fclose($res);
		$s->close();
	}

	public function testAsResourceUsableWithNativeFgets()
	{
		$s = TStream::fromString("line1\nline2\n");
		$res = TStream::asResource($s);
		self::assertSame("line1\n", fgets($res));
		self::assertSame("line2\n", fgets($res));
		fclose($res);
		$s->close();
	}

	public function testAsResourceMatchesDocblockExample()
	{
		// Mirrors the TStreamResourceWrapper docblock example.
		$stream = TStream::fromString("name,score\nAda,99\n");
		$resource = TStream::asResource($stream);
		self::assertSame(['name', 'score'], fgetcsv($resource));
		rewind($resource);
		self::assertSame("name,score\nAda,99\n", stream_get_contents($resource));
		fclose($resource);
		self::assertTrue($stream->isReadable(), 'Underlying stream stays open after fclose of the resource view.');
		$stream->close();
	}

	public function testWrapperProtocolRegistered()
	{
		TStreamResourceWrapper::register();
		self::assertContains(TStreamResourceWrapper::PROTOCOL, stream_get_wrappers());
		self::assertTrue(TStreamResourceWrapper::isRegistered());
		self::assertContains(TStreamResourceWrapper::PROTOCOL, TStreamResourceWrapper::getRegisteredWrappers());
	}

	public function testAsResourceUnusableStreamThrows()
	{
		// A detached stream is neither readable nor writable.
		$s = TStream::fromString('x');
		$s->detach();
		self::expectException(TInvalidDataValueException::class);
		TStream::asResource($s);
	}

	public function testCreateStreamEmpty()
	{
		$s = $this->factory->createStream();
		self::assertSame(0, $s->getSize());
		self::assertTrue($s->isReadable() && $s->isWritable());
		$s->close();
	}

	public function testCreateStreamFromFileDefaultModeIsReadOnly()
	{
		$path = TTestIOHelper::tempFile('fixed', 'prado-tsf');
		$s = $this->factory->createStreamFromFile($path);   // default mode 'r'
		self::assertTrue($s->isReadable());
		self::assertFalse($s->isWritable());
		self::assertSame('fixed', $s->getContents());
		$s->close();
	}

	public function testAsResourceReadOnlyMode()
	{
		$path = TTestIOHelper::tempFile('read-only-data', 'prado-tsf');
		$stream = TStream::fromFile($path, 'rb');            // read-only → wrapper mode 'r'
		$resource = TStream::asResource($stream);
		self::assertSame('read-only-data', stream_get_contents($resource));
		fclose($resource);
		$stream->close();
	}
}
