<?php

use Prado\IO\Compression\ICompressor;

/** A trivial codec (byte-reversal) that only exists to exercise the ICompressor contract. */
class ReverseCompressor implements ICompressor
{
	public static function compress(string $data): string
	{
		return strrev($data);
	}

	public static function decompress(string $data): string
	{
		return strrev($data);
	}
}

class ICompressorTest extends PHPUnit\Framework\TestCase
{
	public function testImplementationSatisfiesTheContract()
	{
		self::assertContains(ICompressor::class, class_implements(ReverseCompressor::class));
	}

	public function testCompressAndDecompressRoundTrip()
	{
		$data = 'PRADO framework';
		$encoded = ReverseCompressor::compress($data);
		self::assertNotSame($data, $encoded);
		self::assertSame($data, ReverseCompressor::decompress($encoded));
	}

	public function testEmptyStringRoundTrips()
	{
		self::assertSame('', ReverseCompressor::decompress(ReverseCompressor::compress('')));
	}

	public function testMethodsAreStatic()
	{
		$ref = new \ReflectionClass(ICompressor::class);
		self::assertTrue($ref->getMethod('compress')->isStatic());
		self::assertTrue($ref->getMethod('decompress')->isStatic());
	}
}
