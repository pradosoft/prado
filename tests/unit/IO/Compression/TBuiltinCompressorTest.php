<?php

use Prado\Exceptions\TIOException;
use Prado\IO\Compression\ICompressor;
use Prado\IO\Compression\TBrotliCompressor;
use Prado\IO\Compression\TBuiltinCompressor;
use Prado\IO\Compression\TBzip2Compressor;
use Prado\IO\Compression\TDeflateCompressor;
use Prado\IO\Compression\TGzipCompressor;
use Prado\IO\Compression\TZlibCompressor;
use Prado\IO\Compression\TZstdCompressor;
use Prado\IO\Stream\TInflateStream;
use Prado\IO\TStream;

class TBuiltinCompressorTest extends PHPUnit\Framework\TestCase
{
	/** @return array<string, array{0: class-string<TBuiltinCompressor>, 1: string, 2: ?int}> */
	public static function codecProvider(): array
	{
		return [
			//                            class                    NAME       ZLIB_ENCODING for cross-check
			'gzip' => [TGzipCompressor::class, 'gzip', ZLIB_ENCODING_GZIP],
			'zlib' => [TZlibCompressor::class, 'zlib', ZLIB_ENCODING_DEFLATE],
			'deflate' => [TDeflateCompressor::class, 'deflate', ZLIB_ENCODING_RAW],
			'bzip2' => [TBzip2Compressor::class, 'bzip2', null],
			'zstd' => [TZstdCompressor::class, 'zstd', null],
			'brotli' => [TBrotliCompressor::class, 'br', null],
		];
	}

	/**
	 * @dataProvider codecProvider
	 * @param class-string<TBuiltinCompressor> $codec
	 */
	public function testImplementsTheCompressorContract(string $codec): void
	{
		self::assertContains(ICompressor::class, class_implements($codec));
		self::assertInstanceOf(TBuiltinCompressor::class, (new \ReflectionClass($codec))->newInstanceWithoutConstructor());
	}

	/**
	 * @dataProvider codecProvider
	 * @param class-string<TBuiltinCompressor> $codec
	 * @param string $name
	 */
	public function testNameIsExposed(string $codec, string $name): void
	{
		self::assertSame($name, $codec::NAME);
	}

	/**
	 * @dataProvider codecProvider
	 * @param class-string<TBuiltinCompressor> $codec
	 * @param string $name
	 */
	public function testRoundTrip(string $codec, string $name): void
	{
		$this->skipIfUnavailable($codec);
		foreach (['', 'A', 'PRADO framework', str_repeat('abcABC123 ', 5000), random_bytes(4096)] as $data) {
			$packed = $codec::compress($data);
			self::assertSame($data, $codec::decompress($packed), "{$name} round-trip of " . strlen($data) . ' bytes');
		}
	}

	/**
	 * @dataProvider codecProvider
	 * @param class-string<TBuiltinCompressor> $codec
	 */
	public function testCompressesRepetitiveData(string $codec): void
	{
		$this->skipIfUnavailable($codec);
		$data = str_repeat('PRADO', 4000);
		self::assertLessThan(strlen($data), strlen($codec::compress($data)), 'A repetitive payload shrinks.');
	}

	/**
	 * @dataProvider codecProvider
	 * @param class-string<TBuiltinCompressor> $codec
	 */
	public function testLevelIsHonored(string $codec): void
	{
		$this->skipIfUnavailable($codec);
		$data = str_repeat('the quick brown fox jumps ', 2000) . random_bytes(1000);
		$low = $codec::compress($data, 1);
		$high = $codec::compress($data, 9);
		self::assertSame($data, $codec::decompress($low), 'Level 1 round-trips.');
		self::assertSame($data, $codec::decompress($high), 'Level 9 round-trips.');
		self::assertLessThanOrEqual(strlen($low), strlen($high), 'The maximum level is no larger than the minimum.');
	}

	/**
	 * An out-of-range level falls back to the codec's default rather than leaking a raw
	 * ValueError from the native call, so every codec upholds the same contract.
	 * @dataProvider codecProvider
	 * @param class-string<TBuiltinCompressor> $codec
	 */
	public function testOutOfRangeLevelFallsBackToTheDefault(string $codec): void
	{
		$this->skipIfUnavailable($codec);
		$data = str_repeat('level guard ', 500);
		foreach ([-99, 99] as $level) {
			$packed = $codec::compress($data, $level);   // must not throw a ValueError
			self::assertSame($data, $codec::decompress($packed), "An out-of-range level ({$level}) still round-trips.");
		}
	}

	/**
	 * @dataProvider codecProvider
	 * @param class-string<TBuiltinCompressor> $codec
	 * @param string $name
	 */
	public function testCorruptDataThrows(string $codec, string $name): void
	{
		$this->skipIfUnavailable($codec);
		$this->expectException(TIOException::class);
		$codec::decompress('not ' . $name . ' compressed data at all');
	}

	/**
	 * The whole-string zlib codecs must produce bytes the streaming inflater decodes, since
	 * they are two forms of the same algorithm.  bzip2 has no ZLIB encoding and is skipped.
	 * @dataProvider codecProvider
	 * @param class-string<TBuiltinCompressor> $codec
	 * @param string $name
	 * @param ?int $encoding
	 */
	public function testInteroperatesWithTheStreamingInflater(string $codec, string $name, ?int $encoding): void
	{
		if ($encoding === null) {
			self::markTestSkipped('bzip2 has no zlib streaming counterpart.');
		}
		$data = str_repeat('interop payload ', 1000);
		$packed = $codec::compress($data);
		$inflated = (new TInflateStream(TStream::fromString($packed), $encoding))->getContents();
		self::assertSame($data, $inflated, "{$name} output is readable by TInflateStream.");
	}

	public function testAvailabilityReflectsTheExtension(): void
	{
		self::assertSame(extension_loaded('zlib'), TGzipCompressor::isAvailable());
		self::assertSame(extension_loaded('zlib'), TDeflateCompressor::isAvailable());
		self::assertSame(extension_loaded('zlib'), TZlibCompressor::isAvailable());
		self::assertSame(extension_loaded('bz2'), TBzip2Compressor::isAvailable());
		self::assertSame(extension_loaded('zstd'), TZstdCompressor::isAvailable());
		self::assertSame(extension_loaded('brotli'), TBrotliCompressor::isAvailable());
	}

	/**
	 * An absent extension makes the codec throw at use rather than fatal on an undefined
	 * function, so a deployment without zstd/brotli degrades cleanly.
	 * @dataProvider codecProvider
	 * @param class-string<TBuiltinCompressor> $codec
	 */
	public function testUnavailableCodecThrowsExtensionRequired(string $codec): void
	{
		if ($codec::isAvailable()) {
			self::markTestSkipped($codec::NAME . ' is available; the unavailable path cannot be exercised here.');
		}
		$this->expectException(TIOException::class);
		$codec::compress('data');
	}

	public function testGzipOutputIsAStandardGzipStream(): void
	{
		$this->skipIfUnavailable(TGzipCompressor::class);
		$packed = TGzipCompressor::compress('gzip magic check');
		self::assertSame("\x1f\x8b", substr($packed, 0, 2), 'A gzip stream starts with the 1f 8b magic.');
		self::assertSame('gzip magic check', gzdecode($packed), 'A stock gzdecode reads it.');
	}

	/**
	 * @param class-string<TBuiltinCompressor> $codec
	 */
	private function skipIfUnavailable(string $codec): void
	{
		if (!$codec::isAvailable()) {
			self::markTestSkipped($codec::NAME . ' requires a PHP extension that is not loaded.');
		}
	}
}
