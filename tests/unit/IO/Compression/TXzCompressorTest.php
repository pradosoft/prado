<?php

use Prado\Exceptions\TNotSupportedException;
use Prado\IO\Compression\ICompressor;
use Prado\IO\Compression\TBuiltinCompressor;
use Prado\IO\Compression\TXzCompressor;

class TXzCompressorTest extends PHPUnit\Framework\TestCase
{
	public function testIsABuiltinCompressorStub()
	{
		self::assertContains(ICompressor::class, class_implements(TXzCompressor::class));
		self::assertInstanceOf(TBuiltinCompressor::class, (new \ReflectionClass(TXzCompressor::class))->newInstanceWithoutConstructor());
		self::assertSame('xz', TXzCompressor::NAME);
	}

	public function testIsNeverAvailable()
	{
		self::assertFalse(TXzCompressor::isAvailable(), 'No PHP xz extension exists, so the core stub is inert.');
	}

	public function testCompressThrowsNotSupported()
	{
		$this->expectException(TNotSupportedException::class);
		TXzCompressor::compress('data');
	}

	public function testDecompressThrowsNotSupported()
	{
		$this->expectException(TNotSupportedException::class);
		TXzCompressor::decompress('data');
	}
}
