<?php

namespace Prado\Test\Unit\IO\Compression;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TIOException;
use Prado\IO\Compression\TCompressionConfig;

/**
 * Redeclares every default so a test proves the constants reach the getters through
 * late static binding.
 */
class TSubclassCompressionConfig extends TCompressionConfig
{
	public const DEFAULT_ENABLED = true;

	public const DEFAULT_METHOD = 'gzip';

	public const DEFAULT_LEVEL = 9;

	public const DEFAULT_THRESHOLD = 64;
}

class TCompressionConfigTest extends \PHPUnit\Framework\TestCase
{
	protected ?TCompressionConfig $config = null;

	protected function setUp(): void
	{
		$this->config = new TCompressionConfig();
	}

	protected function tearDown(): void
	{
		$this->config = null;
	}

	public function testDefaults()
	{
		self::assertFalse($this->config->getEnabled());
		self::assertEquals(TCompressionConfig::DEFAULT_METHOD, $this->config->getMethod());
		self::assertEquals('deflate', $this->config->getMethod());
		self::assertEquals(-1, $this->config->getLevel());
		self::assertEquals(TCompressionConfig::DEFAULT_THRESHOLD, $this->config->getThreshold());
	}

	public function testSubclassDefaultsOverrideTheConstants()
	{
		$config = new TSubclassCompressionConfig();
		self::assertTrue($config->getEnabled());
		self::assertEquals('gzip', $config->getMethod());
		self::assertEquals(9, $config->getLevel());
		self::assertEquals(64, $config->getThreshold());
	}

	public function testSubclassDefaultsYieldToAnAssignedValue()
	{
		$config = new TSubclassCompressionConfig();
		$config->setEnabled(false);
		$config->setMethod('deflate');
		$config->setLevel(1);
		$config->setThreshold(2048);
		self::assertFalse($config->getEnabled());
		self::assertEquals('deflate', $config->getMethod());
		self::assertEquals(1, $config->getLevel());
		self::assertEquals(2048, $config->getThreshold());
	}

	public function testSetEnabled()
	{
		$this->config->setEnabled('true');
		self::assertTrue($this->config->getEnabled());
		$this->config->setEnabled(false);
		self::assertFalse($this->config->getEnabled());
	}

	public function testSetMethod()
	{
		$this->config->setMethod('gzip');
		self::assertEquals('gzip', $this->config->getMethod());
		$this->config->setMethod(' GZIP ');
		self::assertEquals('gzip', $this->config->getMethod());
	}

	public function testSetMethodRejectsUnknownCoding()
	{
		try {
			$this->config->setMethod('lzma');
			self::fail('Expected TConfigurationException not thrown');
		} catch (TConfigurationException $e) {
		}
		self::assertEquals(TCompressionConfig::DEFAULT_METHOD, $this->config->getMethod());
	}

	public function testSetLevel()
	{
		$this->config->setLevel('9');
		self::assertEquals(9, $this->config->getLevel());
	}

	public function testSetThreshold()
	{
		$this->config->setThreshold('2048');
		self::assertEquals(2048, $this->config->getThreshold());
		$this->config->setThreshold(-5);
		self::assertEquals(0, $this->config->getThreshold());
	}

	public function testGetIsAvailable()
	{
		$this->config->setMethod('gzip');
		self::assertEquals(extension_loaded('zlib'), $this->config->getIsAvailable());
	}

	public function testGetShouldCompress()
	{
		$this->config->setMethod('gzip');
		self::assertFalse($this->config->getShouldCompress());
		$this->config->setEnabled(true);
		self::assertEquals(extension_loaded('zlib'), $this->config->getShouldCompress());
	}

	public function testShouldCompressLength()
	{
		$this->config->setMethod('gzip');
		$this->config->setEnabled(true);
		$this->config->setThreshold(100);
		self::assertFalse($this->config->shouldCompressLength(99));
		self::assertTrue($this->config->shouldCompressLength(100));
		self::assertTrue($this->config->shouldCompressLength(101));

		$this->config->setEnabled(false);
		self::assertFalse($this->config->shouldCompressLength(101));
	}

	public function testCompressRoundTrip()
	{
		$data = str_repeat('prado compresses this. ', 100);
		foreach (['gzip', 'deflate'] as $method) {
			$this->config->setMethod($method);
			$encoded = $this->config->compress($data);
			self::assertNotEquals($data, $encoded);
			self::assertLessThan(strlen($data), strlen($encoded));
			self::assertEquals($data, $this->config->decompress($encoded));
		}
	}

	public function testCompressAppliesTheLevel()
	{
		$data = str_repeat('prado compresses this, with more and less effort. ', 200);
		$this->config->setMethod('gzip');
		$this->config->setLevel(1);
		$fast = $this->config->compress($data);
		$this->config->setLevel(9);
		$small = $this->config->compress($data);
		self::assertLessThanOrEqual(strlen($fast), strlen($small));
		self::assertEquals($data, $this->config->decompress($small));
	}

	public function testDecompressCorruptData()
	{
		$this->config->setMethod('gzip');
		try {
			$this->config->decompress('not compressed at all');
			self::fail('Expected TIOException not thrown');
		} catch (TIOException $e) {
			self::assertInstanceOf(TIOException::class, $e);
		}
	}
}
