<?php

namespace Prado\Test\Unit\IO\Compression;

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

/** An identity codec whose staging hook creates files in a test directory and fails on a chosen call. */
class FailingStageCliCompressor extends CatCliCompressor
{
	public static string $directory = '';

	public static int $failOnCall = 0;

	public static int $calls = 0;

	protected static function cliStageFile(string $directory): false|string
	{
		if (++self::$calls === self::$failOnCall) {
			return false;
		}
		return tempnam(self::$directory, 'prado_cli_');
	}
}

class TCliCompressorTest extends \PHPUnit\Framework\TestCase
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

	public function testHandlesDataLargerThanAPipeBuffer()
	{
		$this->skipWithoutCat();
		// 4 MB dwarfs the ~64 KB OS pipe buffer; staging through temporary files transfers it
		// without a deadlock and without stream_select, which is unreliable on Windows.
		$data = random_bytes(4 * 1024 * 1024);
		self::assertSame($data, CatCliCompressor::compress($data), 'A large transfer streams through the file-backed run.');
	}

	/**
	 * @dataProvider provideStagingFailures
	 * @param int $failOnCall
	 */
	public function testFailedStagingRemovesTheFilesAlreadyCreated(int $failOnCall)
	{
		$this->skipWithoutCat();
		$directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado_cli_stage_' . bin2hex(random_bytes(6));
		mkdir($directory);
		FailingStageCliCompressor::$directory = $directory;
		FailingStageCliCompressor::$failOnCall = $failOnCall;
		FailingStageCliCompressor::$calls = 0;
		try {
			FailingStageCliCompressor::compress('data');
			self::fail('A staging failure throws.');
		} catch (TIOException $e) {
			self::assertSame([], array_values(array_diff(scandir($directory), ['.', '..'])), 'The staging files created before the failure are removed.');
		} finally {
			array_map('unlink', glob($directory . DIRECTORY_SEPARATOR . '*') ?: []);
			rmdir($directory);
		}
	}

	public static function provideStagingFailures(): array
	{
		return ['first' => [1], 'second' => [2], 'third' => [3]];
	}

	public function testSuccessfulRunRemovesTheStagingFiles()
	{
		$this->skipWithoutCat();
		$directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado_cli_stage_' . bin2hex(random_bytes(6));
		mkdir($directory);
		FailingStageCliCompressor::$directory = $directory;
		FailingStageCliCompressor::$failOnCall = 0;
		FailingStageCliCompressor::$calls = 0;
		try {
			self::assertSame('data', FailingStageCliCompressor::compress('data'));
			self::assertSame(3, FailingStageCliCompressor::$calls, 'The run stages its three standard streams through the hook.');
			self::assertSame([], array_values(array_diff(scandir($directory), ['.', '..'])), 'The staging files are removed after the run.');
		} finally {
			array_map('unlink', glob($directory . DIRECTORY_SEPARATOR . '*') ?: []);
			rmdir($directory);
		}
	}
}
