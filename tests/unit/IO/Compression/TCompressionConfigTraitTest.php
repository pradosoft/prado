<?php

namespace Prado\Test\Unit\IO\Compression;

use Prado\IO\Compression\ICompressionConfigurable;
use Prado\IO\Compression\TCompressionConfig;
use Prado\IO\Compression\TCompressionConfigTrait;
use Prado\TApplication;
use Prado\TModule;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Xml\TXmlDocument;

/**
 * A module holding compression settings, reading its nested `<compression>` element in `init()`.
 */
class TCompressingModule extends TModule implements ICompressionConfigurable
{
	use TCompressionConfigTrait;

	public function init($config)
	{
		$this->applyCompressionConfig($config);
		parent::init($config);
	}
}

/**
 * A module whose compression starts from its own defaults through {@see newCompression()}.
 */
class TGzipCompressingModule extends TCompressingModule
{
	protected function newCompression(): TCompressionConfig
	{
		$compression = new TCompressionConfig();
		$compression->setMethod('gzip');
		return $compression;
	}
}

class TCompressionConfigTraitTest extends \PHPUnit\Framework\TestCase
{
	protected ?TTestApplication $app = null;

	protected function setUp(): void
	{
		$this->app = new TTestApplication(__DIR__ . '/../../Web/app');
	}

	protected function tearDown(): void
	{
		if ($this->app !== null) {
			$this->app->restoreApplication();
			$this->app = null;
		}
	}

	public function testGetCompressionIsCreatedOnce()
	{
		$module = new TCompressingModule();
		$compression = $module->getCompression();
		self::assertInstanceOf(TCompressionConfig::class, $compression);
		self::assertSame($compression, $module->getCompression());
	}

	public function testSetCompression()
	{
		$module = new TCompressingModule();
		$compression = new TCompressionConfig();
		$module->setCompression($compression);
		self::assertSame($compression, $module->getCompression());
	}

	public function testNewCompressionSuppliesTheModuleDefaults()
	{
		self::assertEquals('gzip', (new TGzipCompressingModule())->getCompression()->getMethod());
		self::assertEquals(TCompressionConfig::DEFAULT_METHOD, (new TCompressingModule())->getCompression()->getMethod());
	}

	public function testCompressionIsReachedAsASubProperty()
	{
		$module = new TCompressingModule();
		$module->setSubProperty('Compression.Method', 'gzip');
		self::assertEquals('gzip', $module->getCompression()->getMethod());
	}

	public function testCompressionElementOfAnXmlConfiguration()
	{
		$this->app->setConfigurationType(TApplication::CONFIG_TYPE_XML);
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString('<module><compression Enabled="true" Method="gzip" Level="9" Threshold="512" /></module>');

		$module = new TCompressingModule();
		$module->init($config);

		$compression = $module->getCompression();
		self::assertTrue($compression->getEnabled());
		self::assertEquals('gzip', $compression->getMethod());
		self::assertEquals(9, $compression->getLevel());
		self::assertEquals(512, $compression->getThreshold());
	}

	public function testXmlConfigurationWithoutTheElementLeavesTheDefaults()
	{
		$this->app->setConfigurationType(TApplication::CONFIG_TYPE_XML);
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString('<module />');

		$module = new TGzipCompressingModule();
		$module->init($config);

		self::assertFalse($module->getCompression()->getEnabled());
		self::assertEquals('gzip', $module->getCompression()->getMethod());
	}

	public function testCompressionArrayOfAPhpConfiguration()
	{
		$this->app->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		$module = new TCompressingModule();
		$module->init(['compression' => ['Enabled' => 'true', 'Method' => 'gzip', 'Level' => '6']]);

		$compression = $module->getCompression();
		self::assertTrue($compression->getEnabled());
		self::assertEquals('gzip', $compression->getMethod());
		self::assertEquals(6, $compression->getLevel());
	}

	public function testPhpConfigurationWithoutTheArrayLeavesTheDefaults()
	{
		$this->app->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		$module = new TCompressingModule();
		$module->init(['properties' => []]);
		self::assertFalse($module->getCompression()->getEnabled());
	}

	public function testNullConfigurationLeavesTheDefaults()
	{
		$module = new TCompressingModule();
		$module->init(null);
		self::assertFalse($module->getCompression()->getEnabled());
		self::assertEquals(TCompressionConfig::DEFAULT_METHOD, $module->getCompression()->getMethod());
	}
}
