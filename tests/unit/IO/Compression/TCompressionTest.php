<?php

use Prado\Exceptions\TIOException;
use Prado\IO\Compression\TCompression;
use Prado\IO\Compression\TGzipCompressor;
use Prado\IO\Compression\TZlibCompressor;

class TCompressionTest extends PHPUnit\Framework\TestCase
{
	public function testKnownMethodsInPreferenceOrder()
	{
		self::assertSame(['zstd', 'br', 'gzip', 'deflate'], TCompression::getMethods());
	}

	public function testCodecLookupIsCaseInsensitive()
	{
		self::assertSame(TGzipCompressor::class, TCompression::getCodec('gzip'));
		self::assertSame(TGzipCompressor::class, TCompression::getCodec('GZIP'));
		self::assertSame(TZlibCompressor::class, TCompression::getCodec('deflate'), 'HTTP deflate is the zlib format.');
		self::assertNull(TCompression::getCodec('bzip2'), 'bzip2 is not an HTTP content coding.');
		self::assertNull(TCompression::getCodec('nonsense'));
	}

	public function testAvailabilityTracksTheCodec()
	{
		self::assertSame(extension_loaded('zlib'), TCompression::isAvailable('gzip'));
		self::assertSame(extension_loaded('zstd'), TCompression::isAvailable('zstd'));
		self::assertFalse(TCompression::isAvailable('nonsense'));
	}

	public function testAvailableMethodsAreASubsetInPreferenceOrder()
	{
		$available = TCompression::getAvailableMethods();
		self::assertSame($available, array_values(array_intersect(TCompression::getMethods(), $available)), 'Preference order is preserved.');
		if (extension_loaded('zlib')) {
			self::assertContains('gzip', $available);
			self::assertContains('deflate', $available);
		}
	}

	public function testBestMethodIsTheTopAvailable()
	{
		$best = TCompression::getBestMethod();
		self::assertSame(TCompression::getAvailableMethods()[0] ?? null, $best);
		if (!extension_loaded('zstd') && !extension_loaded('brotli') && extension_loaded('zlib')) {
			self::assertSame('gzip', $best, 'Without zstd/brotli, gzip is the best available.');
		}
	}

	public function testCompressRoundTripsThroughTheFacade()
	{
		if (!extension_loaded('zlib')) {
			self::markTestSkipped('zlib is required for the gzip/deflate codings.');
		}
		$data = str_repeat('facade payload ', 500);
		foreach (['gzip', 'deflate'] as $method) {
			$packed = TCompression::compress($data, $method);
			self::assertSame($data, TCompression::decompress($packed, $method), "{$method} round-trips through the facade.");
		}
	}

	public function testCompressWithNullMethodUsesTheBest()
	{
		if (TCompression::getBestMethod() === null) {
			self::markTestSkipped('No compression method is available.');
		}
		$data = str_repeat('auto ', 500);
		$best = TCompression::getBestMethod();
		$packed = TCompression::compress($data);
		self::assertSame($data, TCompression::decompress($packed, $best), 'A null method compresses with the best available codec.');
	}

	public function testUnknownMethodThrows()
	{
		$this->expectException(TIOException::class);
		TCompression::compress('data', 'nonsense');
	}

	// ---- Accept-Encoding negotiation -------------------------------------------

	public function testNegotiatePicksTheServerPreferredCoding()
	{
		// The client accepts both; the server prefers gzip over deflate, so gzip wins.
		self::assertSame('gzip', TCompression::negotiate('deflate, gzip', ['gzip', 'deflate']));
	}

	public function testNegotiateHonorsAClientQValueRejection()
	{
		// q=0 rejects gzip, leaving deflate the only acceptable coding.
		self::assertSame('deflate', TCompression::negotiate('gzip;q=0, deflate', ['gzip', 'deflate']));
	}

	public function testNegotiateHonorsTheWildcard()
	{
		self::assertSame('gzip', TCompression::negotiate('*', ['gzip', 'deflate']));
		self::assertNull(TCompression::negotiate('*;q=0', ['gzip', 'deflate']), 'A zero wildcard accepts nothing.');
	}

	public function testNegotiateReturnsNullWhenNothingIsShared()
	{
		self::assertNull(TCompression::negotiate('br', ['gzip', 'deflate']), 'The client accepts only a coding the server does not offer.');
	}

	public function testNegotiateOnAnAbsentHeaderServesIdentity()
	{
		self::assertNull(TCompression::negotiate(null, ['gzip']));
		self::assertNull(TCompression::negotiate('', ['gzip']));
	}

	public function testNegotiateDefaultsToAvailableMethods()
	{
		if (!extension_loaded('zlib')) {
			self::markTestSkipped('zlib is required.');
		}
		// With no explicit offer, the server offers what it can run; gzip is accepted here.
		self::assertSame('gzip', TCompression::negotiate('gzip'));
	}

	public function testNegotiatedCodingRoundTrips()
	{
		if (!extension_loaded('zlib')) {
			self::markTestSkipped('zlib is required.');
		}
		$method = TCompression::negotiate('br;q=0.9, gzip;q=0.8', ['gzip', 'deflate']);
		self::assertSame('gzip', $method, 'br is unavailable/unoffered, so gzip is chosen.');
		$data = str_repeat('negotiated ', 300);
		self::assertSame($data, TCompression::decompress(TCompression::compress($data, $method), $method));
	}
}
