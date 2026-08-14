<?php

use Prado\Exceptions\TIOException;
use Prado\IO\Compression\ICompressor;
use Prado\IO\Compression\TCliCompressor;

/** An identity codec (the `cat` command) that exercises the TCliCompressorTrait pipe pump deterministically. */
class CatCliCompressor extends TCliCompressor
{
	public const NAME = 'cat';

	protected static function commands(): array
	{
		return ['cat'];
	}

	protected static function compressArgs(int $level): array
	{
		return [];
	}

	protected static function decompressArgs(): array
	{
		return [];
	}
}

/** A codec whose command does not exist, for the unavailable path. */
class MissingCliCompressor extends TCliCompressor
{
	public const NAME = 'nope';

	protected static function commands(): array
	{
		return ['prado-no-such-command-xyz'];
	}

	protected static function compressArgs(int $level): array
	{
		return [];
	}

	protected static function decompressArgs(): array
	{
		return [];
	}
}

class TCliCompressorTest extends PHPUnit\Framework\TestCase
{
	private function skipWithoutCat(): void
	{
		if (!CatCliCompressor::isAvailable()) {
			self::markTestSkipped('The cat command is not on the PATH.');
		}
	}

	public function testIsACompressor()
	{
		self::assertContains(ICompressor::class, class_implements(CatCliCompressor::class));
	}

	public function testAvailabilityTracksTheCommand()
	{
		self::assertFalse(MissingCliCompressor::isAvailable(), 'A codec whose command is absent is unavailable.');
	}

	public function testMissingCommandThrows()
	{
		$this->expectException(TIOException::class);
		MissingCliCompressor::compress('data');
	}

	public function testPumpRoundTripsThroughAnIdentityCommand()
	{
		$this->skipWithoutCat();
		foreach (['', 'A', 'hello world', random_bytes(4096)] as $data) {
			self::assertSame($data, CatCliCompressor::compress($data), 'cat passes bytes through unchanged.');
			self::assertSame($data, CatCliCompressor::decompress($data), 'The decompress path uses the same pump.');
		}
	}

	public function testPumpDoesNotDeadlockOnDataLargerThanAPipeBuffer()
	{
		$this->skipWithoutCat();
		// 4 MB dwarfs the ~64 KB OS pipe buffer; a write-all-then-read pump would deadlock here.
		$data = random_bytes(4 * 1024 * 1024);
		self::assertSame($data, CatCliCompressor::compress($data), 'The non-blocking pump streams past the pipe buffer.');
	}
}
