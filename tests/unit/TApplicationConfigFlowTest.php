<?php

/**
 * TApplicationConfigFlowTest class file.
 *
 * Covers the configuration flow of {@see \Prado\TApplication}: path and runtime
 * resolution, {@see \Prado\TApplication::applyConfiguration()} from a parsed XML
 * configuration, {@see \Prado\TApplication::initApplication()} with and without a
 * configuration cache, and service start-up with a service configuration element.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit;

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TApplication;
use Prado\TApplicationConfiguration;
use Prado\TComponent;
use Prado\TModule;
use Prado\TService;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Util\Clock\TNativeClock;
use Prado\Web\THttpRequest;
use Prado\Xml\TXmlDocument;

/**
 * Module that records the configuration passed to init().
 */
class TApplicationConfigFlowTest_Module extends TModule
{
	public bool $initialized = false;

	public function init($config)
	{
		$this->initialized = true;
		parent::init($config);
	}
}

/**
 * Component used as a class-typed application parameter.
 */
class TApplicationConfigFlowTest_Parameter extends TComponent
{
	private string $_label = '';

	public function getLabel(): string
	{
		return $this->_label;
	}

	public function setLabel(string $value): void
	{
		$this->_label = $value;
	}
}

/**
 * Service that records the configuration element passed to init().
 */
class TApplicationConfigFlowTest_Service extends TService
{
	public mixed $initConfig = null;

	public function init($config)
	{
		$this->initConfig = $config;
	}
}

/**
 * Request whose service resolution returns a fixed ID.
 */
class TApplicationConfigFlowTest_Request extends THttpRequest
{
	public ?string $resolved = null;
	public array $serviceIDs = [];

	public function resolveRequest($serviceIDs)
	{
		$this->serviceIDs = $serviceIDs;
		return $this->resolved;
	}
}

/**
 * Test application exposing the configuration lifecycle.
 */
class TApplicationConfigFlowTest_App extends TTestApplication
{
	public bool $skipService = true;

	public function pubInitApplication(): void
	{
		$this->initApplication();
	}

	public function pubInitService(): void
	{
		parent::initService();
	}

	public function pubSetCacheFile(?string $file): void
	{
		$this->setCacheFile($file);
	}

	public function pubGetClockClass(): string
	{
		return $this->getClockClass();
	}

	protected function initService(): void
	{
		if (!$this->skipService) {
			parent::initService();
		}
	}
}

class TApplicationConfigFlowTest extends \PHPUnit\Framework\TestCase
{
	private string $_tmpDir;
	private ?TApplicationConfigFlowTest_App $_app = null;

	protected function setUp(): void
	{
		$this->_tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tacf_' . uniqid('', true);
		mkdir($this->_tmpDir, 0o777, true);
	}

	protected function tearDown(): void
	{
		$this->_app?->restoreApplication();
		$this->_app = null;
		$this->removeTree($this->_tmpDir);
	}

	private function removeTree(string $dir): void
	{
		if (!is_dir($dir)) {
			return;
		}
		foreach (array_diff(scandir($dir), ['.', '..']) as $entry) {
			$path = $dir . DIRECTORY_SEPARATOR . $entry;
			if (is_dir($path) && !is_link($path)) {
				$this->removeTree($path);
			} else {
				unlink($path);
			}
		}
		rmdir($dir);
	}

	private function app(): TApplicationConfigFlowTest_App
	{
		return $this->_app ??= new TApplicationConfigFlowTest_App($this->_tmpDir);
	}

	/** A TApplication with the property defaults only; the constructor does not run. */
	private function bareApp(): TApplication
	{
		$app = (new \ReflectionClass(TApplication::class))->newInstanceWithoutConstructor();
		PradoUnit::setProp($app, '_configType', TApplication::CONFIG_TYPE_XML);
		return $app;
	}

	private function writeFile(string $name, string $contents): string
	{
		$path = $this->_tmpDir . DIRECTORY_SEPARATOR . $name;
		file_put_contents($path, $contents);
		return $path;
	}

	private function xmlConfig(string $xml): TApplicationConfiguration
	{
		$dom = new TXmlDocument();
		$dom->loadFromString($xml);
		$config = new TApplicationConfiguration();
		$config->loadFromXml($dom, $this->_tmpDir);
		return $config;
	}

	// =======================================================================
	// resolvePaths() / resolveRuntimePath()
	// =======================================================================

	public function testResolvePaths_invalidBasePath_throws(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->expectExceptionMessageMatches('/application_basepath_invalid|base path/i');
		PradoUnit::invoke($this->bareApp(), 'resolvePaths', $this->_tmpDir . DIRECTORY_SEPARATOR . 'missing');
	}

	public function testResolvePaths_unwritableRuntime_throws(): void
	{
		try {
			PradoUnit::invoke($this->bareApp(), 'resolvePaths', $this->_tmpDir);
			$this->fail('resolvePaths() did not throw without a runtime directory.');
		} catch (TConfigurationException $e) {
			$this->assertSame('application_runtimepath_invalid', $e->getErrorCode());
		}
	}

	public function testResolvePaths_directoryWithoutConfig_usesFlatRuntime(): void
	{
		mkdir($runtime = $this->_tmpDir . DIRECTORY_SEPARATOR . TApplication::RUNTIME_PATH);
		$app = $this->bareApp();

		PradoUnit::invoke($app, 'resolvePaths', $this->_tmpDir);

		$this->assertSame(realpath($this->_tmpDir), $app->getBasePath());
		$this->assertSame(realpath($runtime), realpath($app->getRuntimePath()));
		$this->assertNull($app->getConfigurationFile());
	}

	public function testResolvePaths_directoryWithConfig_createsVersionedRuntime(): void
	{
		mkdir($this->_tmpDir . DIRECTORY_SEPARATOR . TApplication::RUNTIME_PATH);
		$configFile = $this->writeFile(TApplication::CONFIG_FILE_XML, '<application/>');
		$app = $this->bareApp();

		PradoUnit::invoke($app, 'resolvePaths', $this->_tmpDir);

		$base = realpath($this->_tmpDir);
		$expectedRuntime = $base . DIRECTORY_SEPARATOR . TApplication::RUNTIME_PATH . DIRECTORY_SEPARATOR
			. TApplication::CONFIG_FILE_XML . '-' . Prado::getVersion();
		$this->assertSame(realpath($configFile), $app->getConfigurationFile());
		$this->assertSame($base, $app->getBasePath());
		$this->assertSame($expectedRuntime, $app->getRuntimePath());
		$this->assertDirectoryExists($expectedRuntime);
	}

	public function testResolvePaths_configFilePath_usesItsDirectoryAsBase(): void
	{
		mkdir($this->_tmpDir . DIRECTORY_SEPARATOR . TApplication::RUNTIME_PATH);
		$configFile = $this->writeFile('custom.xml', '<application/>');
		$app = $this->bareApp();

		PradoUnit::invoke($app, 'resolvePaths', $configFile);

		$this->assertSame(realpath($configFile), $app->getConfigurationFile());
		$this->assertSame(realpath($this->_tmpDir), $app->getBasePath());
		$this->assertStringEndsWith('custom.xml-' . Prado::getVersion(), $app->getRuntimePath());
	}

	public function testResolveRuntimePath_versionedDirectoryBlocked_throws(): void
	{
		mkdir($runtime = $this->_tmpDir . DIRECTORY_SEPARATOR . TApplication::RUNTIME_PATH);
		$configFile = $this->writeFile(TApplication::CONFIG_FILE_XML, '<application/>');
		file_put_contents($runtime . DIRECTORY_SEPARATOR . TApplication::CONFIG_FILE_XML . '-' . Prado::getVersion(), 'not a directory');

		try {
			PradoUnit::invoke($this->bareApp(), 'resolveRuntimePath', $this->_tmpDir, $configFile);
			$this->fail('resolveRuntimePath() did not throw when the versioned directory cannot be created.');
		} catch (TConfigurationException $e) {
			$this->assertSame('application_runtimepath_failed', $e->getErrorCode());
		}
	}

	// =======================================================================
	// setClockClass()
	// =======================================================================

	public function testSetClockClass_overridesDefault(): void
	{
		$app = $this->app();
		$this->assertSame(TApplication::DEFAULT_CLOCK_CLASS, $app->pubGetClockClass());
		$app->setClockClass(TNativeClock::class . 'Custom');
		$this->assertSame(TNativeClock::class . 'Custom', $app->pubGetClockClass());
	}

	// =======================================================================
	// applyConfiguration()
	// =======================================================================

	public function testApplyConfiguration_appliesPathsServicesParametersAndUnnamedModules(): void
	{
		$app = $this->app();
		mkdir($aliasDir = $this->_tmpDir . DIRECTORY_SEPARATOR . 'flowalias');
		$moduleClass = TApplicationConfigFlowTest_Module::class;
		$paramClass = TApplicationConfigFlowTest_Parameter::class;
		$serviceClass = TApplicationConfigFlowTest_Service::class;
		$usings = PradoUnit::getStaticProp(Prado::class, '_usings');
		$aliases = PradoUnit::getStaticProp(Prado::class, '_aliases');
		$lazyCount = PradoUnit::invoke($app, 'getLazyModuleCount');

		try {
			$app->applyConfiguration($this->xmlConfig(<<<XML
				<application>
					<paths>
						<alias id="FlowAlias" path="flowalias"/>
						<using namespace="FlowAlias.*"/>
					</paths>
					<services>
						<service id="flow" class="{$serviceClass}"/>
					</services>
					<parameters>
						<parameter id="plain" value="text"/>
						<parameter id="typed" class="{$paramClass}" Label="labelled"/>
					</parameters>
					<modules>
						<module class="{$moduleClass}"/>
					</modules>
				</application>
				XML), false);

			$this->assertSame(realpath($aliasDir), Prado::getPathOfAlias('FlowAlias'));
			$this->assertArrayHasKey('FlowAlias', PradoUnit::getStaticProp(Prado::class, '_usings'));
		} finally {
			PradoUnit::setStaticProp(Prado::class, '_usings', $usings);
			PradoUnit::setStaticProp(Prado::class, '_aliases', $aliases);
		}

		$this->assertTrue($app->hasRegisteredService('flow'));
		$this->assertSame('text', $app->getParameters()['plain']);
		$typed = $app->getParameters()['typed'];
		$this->assertInstanceOf(TApplicationConfigFlowTest_Parameter::class, $typed);
		$this->assertSame('labelled', $typed->getLabel());

		$module = $app->getModule('_module' . $lazyCount);
		$this->assertInstanceOf(TApplicationConfigFlowTest_Module::class, $module);
		$this->assertTrue($module->initialized);
	}

	public function testApplyConfiguration_includesAppliedWhenConditionHolds(): void
	{
		$app = $this->app();
		$this->writeFile('included.xml', '<application><parameters><parameter id="fromInclude" value="yes"/></parameters></application>');
		$this->writeFile('skipped.xml', '<application><parameters><parameter id="fromSkipped" value="yes"/></parameters></application>');
		$aliases = PradoUnit::getStaticProp(Prado::class, '_aliases');
		Prado::setPathOfAlias('FlowIncludes', $this->_tmpDir);

		try {
			$app->applyConfiguration($this->xmlConfig(<<<'XML'
				<application>
					<include file="FlowIncludes.included" when="$this->getID() !== 'never'"/>
					<include file="FlowIncludes.skipped" when="false"/>
				</application>
				XML), false);
		} finally {
			PradoUnit::setStaticProp(Prado::class, '_aliases', $aliases);
		}

		$this->assertSame('yes', $app->getParameters()['fromInclude']);
		$this->assertNull($app->getParameters()['fromSkipped']);
	}

	public function testApplyConfiguration_missingIncludeFile_throws(): void
	{
		try {
			$this->app()->applyConfiguration($this->xmlConfig('<application><include file="NoSuchAlias.missing"/></application>'), false);
			$this->fail('applyConfiguration() did not throw for a missing include file.');
		} catch (TConfigurationException $e) {
			$this->assertSame('application_includefile_invalid', $e->getErrorCode());
		}
	}

	// =======================================================================
	// initApplication()
	// =======================================================================

	private function writeAppConfig(string $value): string
	{
		return $this->writeFile(TApplication::CONFIG_FILE_XML, '<application><parameters><parameter id="source" value="' . $value . '"/></parameters></application>');
	}

	public function testInitApplication_configFileWithoutCache_appliesConfig(): void
	{
		$app = $this->app();
		$app->setConfigurationFile($this->writeAppConfig('file'));

		$app->pubInitApplication();

		$this->assertSame('file', $app->getParameters()['source']);
	}

	public function testInitApplication_staleCache_appliesConfigAndWritesCache(): void
	{
		$app = $this->app();
		$app->setConfigurationFile($this->writeAppConfig('file'));
		$app->pubSetCacheFile($cacheFile = $this->_tmpDir . DIRECTORY_SEPARATOR . 'config.cache');

		$app->pubInitApplication();

		$this->assertSame('file', $app->getParameters()['source']);
		$this->assertFileExists($cacheFile);
		$this->assertInstanceOf(TApplicationConfiguration::class, unserialize(file_get_contents($cacheFile)));
	}

	public function testInitApplication_freshCache_appliesCachedConfig(): void
	{
		$app = $this->app();
		$app->setConfigurationFile($this->writeAppConfig('file'));
		$cached = $this->xmlConfig('<application><parameters><parameter id="source" value="cache"/></parameters></application>');
		$cacheFile = $this->writeFile('config.cache', serialize($cached));
		touch($cacheFile, time() + 3600);
		$app->pubSetCacheFile($cacheFile);

		$app->pubInitApplication();

		$this->assertSame('cache', $app->getParameters()['source']);
	}

	// =======================================================================
	// initService() / startService() with a configuration element
	// =======================================================================

	public function testInitService_resolvedService_isStarted(): void
	{
		$app = $this->app();
		$app->registerService('flow', TApplicationConfigFlowTest_Service::class);
		$app->setRequest($request = new TApplicationConfigFlowTest_Request());
		$request->resolved = 'flow';

		$app->pubInitService();

		$this->assertContains('flow', $request->serviceIDs);
		$this->assertInstanceOf(TApplicationConfigFlowTest_Service::class, $app->getService());
		$this->assertSame('flow', $app->getService()->getID());
	}

	public function testInitService_unresolvedRequest_startsPageService(): void
	{
		$app = $this->app();
		$app->registerService($app->getPageServiceID(), TApplicationConfigFlowTest_Service::class);
		$app->setRequest(new TApplicationConfigFlowTest_Request());

		$app->pubInitService();

		$this->assertSame($app->getPageServiceID(), $app->getService()->getID());
	}

	public function testStartService_xmlConfigElement_appliesServiceConfiguration(): void
	{
		$app = $this->app();
		$dom = new TXmlDocument();
		$dom->loadFromString('<service><parameters><parameter id="serviceParam" value="xml"/></parameters></service>');
		$app->registerService('flow', TApplicationConfigFlowTest_Service::class, [], $dom);

		$app->startService('flow');

		$this->assertSame('xml', $app->getParameters()['serviceParam']);
		$this->assertSame($dom, $app->getService()->initConfig);
	}

	public function testStartService_phpConfigElement_appliesServiceConfiguration(): void
	{
		$this->_app = new TApplicationConfigFlowTest_App($this->_tmpDir, false, TApplication::CONFIG_TYPE_PHP);
		$element = ['parameters' => ['serviceParam' => 'php']];
		$this->_app->registerService('flow', TApplicationConfigFlowTest_Service::class, [], $element);

		$this->_app->startService('flow');

		$this->assertSame('php', $this->_app->getParameters()['serviceParam']);
		$this->assertSame($element, $this->_app->getService()->initConfig);
	}
}
