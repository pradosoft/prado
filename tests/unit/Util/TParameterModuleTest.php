<?php

namespace Prado\Test\Unit\Util;

use Prado\Caching\ICache;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\TApplication;
use Prado\TComponent;
use Prado\Test\Unit\Harness\Traits\TTempDirectoryTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\TParameterModule;
use Prado\Xml\TXmlDocument;
use Prado\Xml\TXmlElement;

class TParameterModuleTestComponent extends TComponent
{
	private $_color = '';
	public $initConfig;

	public function getColor()
	{
		return $this->_color;
	}

	public function setColor($value)
	{
		$this->_color = $value;
	}

	public function dyInit($config, $chain = null)
	{
		$this->initConfig = $config;
	}
}

class TParameterModuleTestCache implements ICache
{
	public array $store = [];
	public int $sets = 0;

	public static function getIsAvailable(): bool
	{
		return true;
	}

	public function get($id)
	{
		return $this->store[$id] ?? false;
	}

	public function set($id, $value, $expire = 0, $dependency = null)
	{
		$this->sets++;
		$this->store[$id] = $value;
		return true;
	}

	public function add($id, $value, $expire = 0, $dependency = null)
	{
		return $this->set($id, $value, $expire, $dependency);
	}

	public function delete($id)
	{
		unset($this->store[$id]);
		return true;
	}

	public function flush()
	{
		$this->store = [];
		return true;
	}
}

class TParameterModuleTest extends \PHPUnit\Framework\TestCase
{
	use TTempDirectoryTrait;

	private const IDS = ['pmScalar', 'pmArray', 'pmComponent', 'pmNode', 'pmValue', 'pmFile', 'pmPhpFile'];
	private const ALIAS = 'TParameterModuleTestDir';

	private array $_appSnapshot;

	protected function setUp(): void
	{
		$this->_appSnapshot = PradoUnit::snapshot(Prado::getApplication(), ['_cache', '_configType', '_configFileExt']);
	}

	protected function tearDown(): void
	{
		$app = Prado::getApplication();
		PradoUnit::restore($app, $this->_appSnapshot);
		foreach (self::IDS as $id) {
			$app->getParameters()->remove($id);
		}
		$aliases = PradoUnit::getStaticProp(Prado::class, '_aliases');
		unset($aliases[self::ALIAS]);
		PradoUnit::setStaticProp(Prado::class, '_aliases', $aliases);
		$this->removeTempDirectories();
	}

	private function setConfigType(string $type): void
	{
		$app = Prado::getApplication();
		$app->setConfigurationType($type);
		PradoUnit::setProp($app, '_configFileExt', null);
	}

	private function makeParameterFile(string $name, string $content): string
	{
		$dir = $this->makeTempDirectory();
		file_put_contents($dir . DIRECTORY_SEPARATOR . $name, $content);
		Prado::setPathOfAlias(self::ALIAS, $dir);
		return self::ALIAS . '.' . pathinfo($name, PATHINFO_FILENAME);
	}

	private function assertErrorCode(string $code, callable $callable): void
	{
		try {
			$callable();
			self::fail("No exception with the error code '$code' was thrown.");
		} catch (TConfigurationException $e) {
			self::assertSame($code, $e->getErrorCode());
		}
	}

	private function xmlConfig(string $parameters): TXmlElement
	{
		$doc = new TXmlDocument();
		$doc->loadFromString('<module>' . $parameters . '</module>');
		return $doc;
	}

	public function testInitArrayConfig()
	{
		$module = new TParameterModule();
		$module->init([
			'pmScalar' => 'scalar',
			'pmArray' => ['value' => ['a' => 1]],
			'pmNode' => ['a' => 1],
			'pmComponent' => ['class' => TParameterModuleTestComponent::class, 'properties' => ['Color' => 'red']],
		]);
		$params = Prado::getApplication()->getParameters();

		self::assertSame('scalar', $params['pmScalar']);
		self::assertSame(['a' => 1], $params['pmArray']);
		self::assertFalse($params->contains('pmNode'), 'An array without "class" or "value" is skipped, as in the application configuration.');
		self::assertInstanceOf(TParameterModuleTestComponent::class, $params['pmComponent']);
		self::assertSame('red', $params['pmComponent']->getColor());
		self::assertSame(['class' => TParameterModuleTestComponent::class, 'properties' => ['Color' => 'red']], $params['pmComponent']->initConfig);
		self::assertTrue($module->getIsInitialized());
	}

	public function testInitArrayComponentWithoutProperties()
	{
		$module = new TParameterModule();
		$module->init(['pmComponent' => ['class' => TParameterModuleTestComponent::class]]);

		self::assertSame('', Prado::getApplication()->getParameters()['pmComponent']->getColor());
	}

	public function testInitXmlConfig()
	{
		$config = $this->xmlConfig(
			'<parameter id="pmValue" value="v1" />'
			. '<parameter id="pmNode"><inner>text</inner></parameter>'
			. '<parameter id="pmComponent" class="' . TParameterModuleTestComponent::class . '" Color="blue" />'
		);
		$module = new TParameterModule();
		$module->init($config);
		$params = Prado::getApplication()->getParameters();

		self::assertSame('v1', $params['pmValue']);
		self::assertInstanceOf(TXmlElement::class, $params['pmNode']);
		self::assertSame('text', $params['pmNode']->getElementByTagName('inner')->getValue());
		self::assertSame('blue', $params['pmComponent']->getColor());
		self::assertInstanceOf(TXmlElement::class, $params['pmComponent']->initConfig);
	}

	public function testInitXmlParameterWithoutIdThrows()
	{
		$module = new TParameterModule();
		$this->assertErrorCode('parametermodule_parameterid_required', fn () => $module->init($this->xmlConfig('<parameter value="v1" />')));
	}

	public function testInitOtherConfigLoadsNothing()
	{
		$count = Prado::getApplication()->getParameters()->getCount();
		$module = new TParameterModule();
		$module->init(null);

		self::assertSame($count, Prado::getApplication()->getParameters()->getCount());
		self::assertTrue($module->getIsInitialized());
	}

	public function testParameterFile()
	{
		$module = new TParameterModule();
		self::assertNull($module->getParameterFile());

		$this->setConfigType(TApplication::CONFIG_TYPE_XML);
		$namespace = $this->makeParameterFile('params.xml', '<parameters />');
		$module->setParameterFile($namespace);
		self::assertSame(Prado::getPathOfAlias(self::ALIAS) . DIRECTORY_SEPARATOR . 'params.xml', $module->getParameterFile());
	}

	public function testSetParameterFileInvalidNamespaceThrows()
	{
		$module = new TParameterModule();
		$this->assertErrorCode('parametermodule_parameterfile_invalid', fn () => $module->setParameterFile('NoSuchAlias.params'));
	}

	public function testSetParameterFileMissingFileThrows()
	{
		$this->setConfigType(TApplication::CONFIG_TYPE_XML);
		$this->makeParameterFile('other.xml', '<parameters />');
		$module = new TParameterModule();
		$this->assertErrorCode('parametermodule_parameterfile_invalid', fn () => $module->setParameterFile(self::ALIAS . '.params'));
	}

	public function testSetParameterFileAfterInitThrows()
	{
		$module = new TParameterModule();
		$module->init(null);
		$this->expectException(TInvalidOperationException::class);
		$module->setParameterFile('NoSuchAlias.params');
	}

	public function testInitXmlParameterFileWithoutCache()
	{
		$this->setConfigType(TApplication::CONFIG_TYPE_XML);
		PradoUnit::setProp(Prado::getApplication(), '_cache', null);
		$namespace = $this->makeParameterFile('params.xml', '<parameters><parameter id="pmFile" value="from-file" /></parameters>');
		$module = new TParameterModule();
		$module->setParameterFile($namespace);
		$module->init(['pmScalar' => 'from-config']);
		$params = Prado::getApplication()->getParameters();

		self::assertSame('from-config', $params['pmScalar']);
		self::assertSame('from-file', $params['pmFile']);
	}

	public function testInitXmlParameterFileWithCache()
	{
		$this->setConfigType(TApplication::CONFIG_TYPE_XML);
		$cache = new TParameterModuleTestCache();
		Prado::getApplication()->setCache($cache);
		$namespace = $this->makeParameterFile('params.xml', '<parameters><parameter id="pmFile" value="from-file" /></parameters>');

		$module = new TParameterModule();
		$module->setParameterFile($namespace);
		$module->init(null);
		self::assertSame(1, $cache->sets);
		$key = 'TParameterModule:' . $module->getParameterFile();
		self::assertInstanceOf(TXmlDocument::class, $cache->store[$key]);
		self::assertSame('from-file', Prado::getApplication()->getParameters()['pmFile']);

		Prado::getApplication()->getParameters()->remove('pmFile');
		$cache->store[$key] = $this->xmlConfig('<parameter id="pmFile" value="from-cache" />');
		$module = new TParameterModule();
		$module->setParameterFile($namespace);
		$module->init(null);
		self::assertSame(1, $cache->sets);
		self::assertSame('from-cache', Prado::getApplication()->getParameters()['pmFile']);
	}

	public function testInitPhpParameterFile()
	{
		$this->setConfigType(TApplication::CONFIG_TYPE_PHP);
		Prado::getApplication()->setCache(new TParameterModuleTestCache());
		$namespace = $this->makeParameterFile('params.php', "<?php\nreturn ['pmPhpFile' => 'from-php'];\n");
		$module = new TParameterModule();
		$module->setParameterFile($namespace);
		$module->init(null);

		self::assertSame('from-php', Prado::getApplication()->getParameters()['pmPhpFile']);
	}

	public function testInitParameterFileOfAnotherConfigurationTypeLoadsXml()
	{
		$this->setConfigType(TApplication::CONFIG_TYPE_XML);
		$namespace = $this->makeParameterFile('params.xml', '<parameters><parameter id="pmFile" value="from-xml" /></parameters>');
		$module = new TParameterModule();
		$module->setParameterFile($namespace);
		Prado::getApplication()->setConfigurationType('custom');
		$module->init(null);

		self::assertSame('from-xml', Prado::getApplication()->getParameters()['pmFile']);
	}
}
