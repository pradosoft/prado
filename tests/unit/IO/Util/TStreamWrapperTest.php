<?php

use Prado\Exceptions\TIOException;
use Prado\IO\Util\TStreamWrapper;

class TStreamWrapperTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
		TFixedTestWrapper::registerOnce();
	}

	protected function tearDown(): void
	{
		// Stream wrappers are process-global; unregister so they do not leak across the suite.
		foreach (['prado-test-fixed', 'prado-test-url'] as $protocol) {
			if (in_array($protocol, stream_get_wrappers(), true)) {
				stream_wrapper_unregister($protocol);
			}
		}
	}

	public function testRegisteredAndReadable()
	{
		self::assertContains('prado-test-fixed', TStreamWrapper::getRegisteredWrappers());
		self::assertSame('fixed-content', file_get_contents('prado-test-fixed://anything'));
	}

	public function testFopenReadSeek()
	{
		$h = fopen('prado-test-fixed://x', 'r');
		self::assertSame('fixed', fread($h, 5));
		self::assertSame(5, ftell($h));
		self::assertSame(0, fseek($h, 0));
		self::assertSame('fixed-content', stream_get_contents($h));
		fclose($h);
	}

	public function testRegisterDuplicateThrows()
	{
		self::expectException(TIOException::class);
		TFixedTestWrapper::register();   // already registered by setUp
	}

	public function testUnregisterAndRestore()
	{
		self::assertTrue(TFixedTestWrapper::unregister());
		self::assertNotContains('prado-test-fixed', TStreamWrapper::getRegisteredWrappers());
		TFixedTestWrapper::registerOnce();   // restore for other tests
		self::assertContains('prado-test-fixed', TStreamWrapper::getRegisteredWrappers());
	}

	public function testEmptyProtocolRegisterThrows()
	{
		self::expectException(TIOException::class);
		TStreamWrapper::register('');
	}

	public function testRegisterOnceIsIdempotent()
	{
		TFixedTestWrapper::registerOnce();   // setUp already registered; this is a no-op
		TFixedTestWrapper::registerOnce();
		self::assertContains('prado-test-fixed', TStreamWrapper::getRegisteredWrappers());
	}

	public function testWriteAndUrlFlags()
	{
		// Registers with STREAM_IS_URL and exercises stream_write.
		TUrlTestWrapper::registerOnce();
		self::assertContains('prado-test-url', TStreamWrapper::getRegisteredWrappers());
		$h = fopen('prado-test-url://x', 'w');
		self::assertSame(4, fwrite($h, 'data'), 'stream_write is invoked.');
		fclose($h);
		self::assertTrue(TUrlTestWrapper::unregister());
	}
}

/**
 * Minimal read-only wrapper serving a fixed string, for exercising the base.
 */
class TFixedTestWrapper extends TStreamWrapper
{
	private string $_data = 'fixed-content';
	private int $_pos = 0;

	public static function getDefaultProtocol(): string
	{
		return 'prado-test-fixed';
	}

	public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
	{
		$this->_pos = 0;
		return true;
	}

	public function stream_read(int $count): string|false
	{
		$chunk = substr($this->_data, $this->_pos, $count);
		$this->_pos += strlen($chunk);
		return $chunk;
	}

	public function stream_write(string $data): int
	{
		return 0;
	}

	public function stream_tell(): int
	{
		return $this->_pos;
	}

	public function stream_eof(): bool
	{
		return $this->_pos >= strlen($this->_data);
	}

	public function stream_seek(int $offset, int $whence = SEEK_SET): bool
	{
		$this->_pos = $offset;
		return true;
	}

	public function stream_stat(): array|false
	{
		return [];
	}
}

/**
 * Write-accepting wrapper registered with STREAM_IS_URL, for exercising flags and writes.
 */
class TUrlTestWrapper extends TStreamWrapper
{
	public static function getDefaultProtocol(): string
	{
		return 'prado-test-url';
	}

	public static function getDefaultFlags(): int
	{
		return STREAM_IS_URL;
	}

	public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
	{
		return true;
	}

	public function stream_read(int $count): string|false
	{
		return '';
	}

	public function stream_write(string $data): int
	{
		return strlen($data);
	}

	public function stream_tell(): int
	{
		return 0;
	}

	public function stream_eof(): bool
	{
		return true;
	}

	public function stream_seek(int $offset, int $whence = SEEK_SET): bool
	{
		return true;
	}

	public function stream_stat(): array|false
	{
		return [];
	}
}
