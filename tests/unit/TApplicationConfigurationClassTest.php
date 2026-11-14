<?php

namespace Prado\Test\Unit;

use Prado\TApplication;

/**
 * A TApplicationConfiguration subclass that counts every instantiation and
 * no-ops all file-loading methods so no real filesystem access occurs.
 */
class AppConfigurationSpy extends \Prado\TApplicationConfiguration
{
	public static int $instanceCount = 0;

	public static function resetCount(): void
	{
		self::$instanceCount = 0;
	}

	public function __construct()
	{
		parent::__construct();
		self::$instanceCount++;
	}

	public function loadFromFile($fname, ?string $type = null): void {}

	public function loadFromXml($dom, $configPath): void {}

	public function loadFromPhp($config, $configPath): void {}
}

/**
 * A minimal concrete TService for testing startService() without real
 * service infrastructure.
 */
class AppConfigTestService extends \Prado\TService
{
	public function run(): void {}
}

/**
 * A THttpRequest stub whose resolveRequest() always returns null so that
 * initApplication() falls through to the page service ID without touching
 * $_SERVER or URL parsing.
 */
class AppStubRequest extends \Prado\Web\THttpRequest
{
	public function init($config): void {}

	public function resolveRequest($serviceIDs): ?string
	{
		return null;
	}
}

/**
 * TApplication subclass that returns AppConfigurationSpy from
 * getApplicationConfigurationClass(). Used as a base for all tests in
 * TApplicationConfigurationClassTest.
 */
class ConfigClassTestApp extends TApplication
{
	protected function getApplicationConfigurationClass(): string
	{
		return AppConfigurationSpy::class;
	}
}

/**
 * Extends ConfigClassTestApp and stubs applyConfiguration() so that
 * startService() can be called without triggering full configuration
 * application side-effects.
 */
class StartServiceConfigClassApp extends ConfigClassTestApp
{
	public function applyConfiguration($config, $withinService = false): void {}
}

/**
 * Extends ConfigClassTestApp and stubs everything that runs after the
 * configuration-file loading block so that initApplication() can be called
 * in isolation, verifying only the config-class instantiation path.
 */
class InitConfigClassTestApp extends ConfigClassTestApp
{
	public function applyConfiguration($config, $withinService = false): void {}

	public function onConfiguration(): void {}

	public function onInitComplete(): void {}

	public function startService($serviceID): void {}

	public function getRequest(): \Prado\Web\THttpRequest
	{
		return new AppStubRequest();
	}
}

/**
 * Verifies that TApplication::getApplicationConfigurationClass() is honoured
 * at every instantiation site:
 *
 *   1. initApplication()     — fresh config-file parse (cache absent / stale)
 *   2. startService()        — service sub-configuration element
 *   3. applyConfiguration()  — external include files
 *
 * Each test uses a subclass whose getApplicationConfigurationClass() returns
 * AppConfigurationSpy. After the relevant method is called the spy's instance
 * counter confirms (or denies) that the override was respected.
 *
 * @package System
 */
class TApplicationConfigurationClassTest extends \PHPUnit\Framework\TestCase
{
	// -----------------------------------------------------------------------
	// Shared helpers
	// -----------------------------------------------------------------------

	/** Create an instance of $class via newInstanceWithoutConstructor(). */
	private function newApp(string $class): TApplication
	{
		$ref = new \ReflectionClass($class);
		return $ref->newInstanceWithoutConstructor();
	}

	/** Write `$_services` on an app instance via PradoUnit. */
	private function setServices(TApplication $app, array $services): void
	{
		PradoUnit::setProp($app, '_services', $services);
	}

	/** Set a single named private/protected property via PradoUnit. */
	private function setProp(TApplication $app, string $name, mixed $value): void
	{
		PradoUnit::setProp($app, $name, $value);
	}

	// -----------------------------------------------------------------------
	// startService() — config class used when service has a config element
	// -----------------------------------------------------------------------

	public function testStartService_usesConfigClass_whenServiceHasConfigElement(): void
	{
		AppConfigurationSpy::resetCount();

		$app = $this->newApp(StartServiceConfigClassApp::class);
		$this->setProp($app, '_configType', TApplication::CONFIG_TYPE_XML);
		$this->setProp($app, '_basePath', sys_get_temp_dir());
		$this->setProp($app, '_pageServiceID', 'svc');
		$this->setServices($app, [
			'svc' => [AppConfigTestService::class, [], new \Prado\Xml\TXmlElement('service')],
		]);

		$app->startService('svc');

		$this->assertSame(1, AppConfigurationSpy::$instanceCount,
			'startService() must instantiate the class returned by getApplicationConfigurationClass() when the service has a config element');
	}

	public function testStartService_doesNotUseConfigClass_whenServiceConfigElementIsNull(): void
	{
		AppConfigurationSpy::resetCount();

		$app = $this->newApp(StartServiceConfigClassApp::class);
		$this->setProp($app, '_configType', TApplication::CONFIG_TYPE_XML);
		$this->setProp($app, '_basePath', sys_get_temp_dir());
		$this->setProp($app, '_pageServiceID', 'svc');
		$this->setServices($app, [
			'svc' => [AppConfigTestService::class, [], null],  // null configElement
		]);

		$app->startService('svc');

		$this->assertSame(0, AppConfigurationSpy::$instanceCount,
			'startService() must not instantiate the config class when configElement is null');
	}

	// -----------------------------------------------------------------------
	// initApplication() — config class used for fresh parse (no cache)
	// -----------------------------------------------------------------------

	public function testInitApplication_usesConfigClass_forFreshParse(): void
	{
		AppConfigurationSpy::resetCount();

		$tempFile = tempnam(sys_get_temp_dir(), 'prado_cfg_');
		$configFile = $tempFile . '.xml';
		file_put_contents(
			$configFile,
			'<?xml version="1.0" encoding="utf-8"?><application></application>'
		);

		try {
			$app = $this->newApp(InitConfigClassTestApp::class);
			$this->setProp($app, '_configType', TApplication::CONFIG_TYPE_XML);
			$this->setProp($app, '_configFile', $configFile);
			$this->setProp($app, '_cacheFile', null);  // always fresh parse
			$this->setProp($app, '_pageServiceID', 'page');
			$this->setServices($app, [
				'page' => [\Prado\Web\Services\TPageService::class, [], null],
			]);

			PradoUnit::invoke($app, 'initApplication');

			$this->assertSame(1, AppConfigurationSpy::$instanceCount,
				'initApplication() must instantiate the class returned by getApplicationConfigurationClass() when parsing the config file fresh');
		} finally {
			@unlink($configFile);
			@unlink($tempFile);
		}
	}

	public function testInitApplication_doesNotUseConfigClass_whenNoConfigFile(): void
	{
		AppConfigurationSpy::resetCount();

		$app = $this->newApp(InitConfigClassTestApp::class);
		$this->setProp($app, '_configFile', null);  // no config file
		$this->setProp($app, '_pageServiceID', 'page');
		$this->setServices($app, [
			'page' => [\Prado\Web\Services\TPageService::class, [], null],
		]);

		PradoUnit::invoke($app, 'initApplication');

		$this->assertSame(0, AppConfigurationSpy::$instanceCount,
			'initApplication() must not instantiate the config class when there is no config file');
	}

	// -----------------------------------------------------------------------
	// applyConfiguration() — config class used for external includes
	// -----------------------------------------------------------------------

	public function testApplyConfiguration_usesConfigClass_forExternalIncludes(): void
	{
		AppConfigurationSpy::resetCount();

		// Create a temp directory with a minimal XML config file.
		$tmpDir  = sys_get_temp_dir();
		$tmpFile = $tmpDir . DIRECTORY_SEPARATOR . 'prado_ext_include_test.xml';
		file_put_contents(
			$tmpFile,
			'<?xml version="1.0" encoding="utf-8"?><configuration/>'
		);

		// Save and patch Prado's static alias table so the external path resolves.
		$savedAliases = PradoUnit::getProp(\Prado\Prado::class, '_aliases');
		$patchedAliases = $savedAliases;
		$patchedAliases['PradoExtTest'] = $tmpDir;
		PradoUnit::setProp(\Prado\Prado::class, '_aliases', $patchedAliases);

		try {
			$app = $this->newApp(ConfigClassTestApp::class);
			$this->setProp($app, '_configType', TApplication::CONFIG_TYPE_XML);
			$this->setProp($app, '_configFileExt', TApplication::CONFIG_FILE_EXT_XML);
			$this->setProp($app, '_basePath', $tmpDir);
			$this->setProp($app, '_pageServiceID', 'page');
			$this->setServices($app, []);
			$this->setProp($app, '_parameters', new \Prado\Collections\TMap());
			$this->setProp($app, '_modules', []);
			$this->setProp($app, '_lazyModules', []);

			// A PHPUnit mock that looks like a TApplicationConfiguration instance
			// with a single external include that resolves to the temp file.
			$outerConfig = $this->createMock(\Prado\TApplicationConfiguration::class);
			$outerConfig->method('getIsEmpty')->willReturn(false);
			$outerConfig->method('getAliases')->willReturn([]);
			$outerConfig->method('getUsings')->willReturn([]);
			$outerConfig->method('getClassMap')->willReturn([]);
			$outerConfig->method('getErrorMessages')->willReturn([]);
			$outerConfig->method('getProperties')->willReturn([]);
			$outerConfig->method('getServices')->willReturn([]);
			$outerConfig->method('getParameters')->willReturn([]);
			$outerConfig->method('getModules')->willReturn([]);
			// 'PradoExtTest.prado_ext_include_test' resolves to $tmpFile via the alias.
			$outerConfig->method('getExternalConfigurations')
				->willReturn(['PradoExtTest.prado_ext_include_test' => true]);

			$app->applyConfiguration($outerConfig, false);

			$this->assertSame(1, AppConfigurationSpy::$instanceCount,
				'applyConfiguration() must instantiate the class returned by getApplicationConfigurationClass() for external include files');
		} finally {
			PradoUnit::setProp(\Prado\Prado::class, '_aliases', $savedAliases);
			@unlink($tmpFile);
		}
	}

	public function testApplyConfiguration_doesNotUseConfigClass_whenNoExternalIncludes(): void
	{
		AppConfigurationSpy::resetCount();

		$app = $this->newApp(ConfigClassTestApp::class);
		$this->setProp($app, '_configType', TApplication::CONFIG_TYPE_XML);
		$this->setProp($app, '_pageServiceID', 'page');
		$this->setServices($app, []);
		$this->setProp($app, '_parameters', new \Prado\Collections\TMap());
		$this->setProp($app, '_modules', []);
		$this->setProp($app, '_lazyModules', []);

		$outerConfig = $this->createMock(\Prado\TApplicationConfiguration::class);
		$outerConfig->method('getIsEmpty')->willReturn(false);
		$outerConfig->method('getAliases')->willReturn([]);
		$outerConfig->method('getUsings')->willReturn([]);
		$outerConfig->method('getClassMap')->willReturn([]);
		$outerConfig->method('getErrorMessages')->willReturn([]);
		$outerConfig->method('getProperties')->willReturn([]);
		$outerConfig->method('getServices')->willReturn([]);
		$outerConfig->method('getParameters')->willReturn([]);
		$outerConfig->method('getModules')->willReturn([]);
		$outerConfig->method('getExternalConfigurations')->willReturn([]);  // none

		$app->applyConfiguration($outerConfig, false);

		$this->assertSame(0, AppConfigurationSpy::$instanceCount,
			'applyConfiguration() must not instantiate the config class when there are no external includes');
	}

	// -----------------------------------------------------------------------
	// applyConfiguration() — withinService flag
	// -----------------------------------------------------------------------

	/** Returns a minimal no-op config mock with the given properties map. */
	private function minimalConfigMock(array $properties = [], array $externalConfigs = [], array $classMapFiles = [], array $errorMessages = []): \Prado\TApplicationConfiguration
	{
		$config = $this->createMock(\Prado\TApplicationConfiguration::class);
		$config->method('getIsEmpty')->willReturn(false);
		$config->method('getAliases')->willReturn([]);
		$config->method('getUsings')->willReturn([]);
		$config->method('getClassMap')->willReturn($classMapFiles);
		$config->method('getErrorMessages')->willReturn($errorMessages);
		$config->method('getProperties')->willReturn($properties);
		$config->method('getServices')->willReturn([]);
		$config->method('getParameters')->willReturn([]);
		$config->method('getModules')->willReturn([]);
		$config->method('getExternalConfigurations')->willReturn($externalConfigs);
		return $config;
	}

	private function bareApp(): TApplication
	{
		$app = $this->newApp(ConfigClassTestApp::class);
		$this->setProp($app, '_configType', TApplication::CONFIG_TYPE_XML);
		$this->setProp($app, '_configFileExt', TApplication::CONFIG_FILE_EXT_XML);
		$this->setProp($app, '_basePath', sys_get_temp_dir());
		$this->setProp($app, '_pageServiceID', 'page');
		$this->setServices($app, []);
		$this->setProp($app, '_parameters', new \Prado\Collections\TMap());
		$this->setProp($app, '_modules', []);
		$this->setProp($app, '_lazyModules', []);
		return $app;
	}

	public function testApplyConfiguration_withinService_skipsPropertyApplication(): void
	{
		$app = $this->bareApp();
		$this->setProp($app, '_mode', \Prado\TApplicationMode::Debug);

		// Config says Mode=Performance — must be ignored when withinService=true.
		$config = $this->minimalConfigMock(['Mode' => \Prado\TApplicationMode::Performance]);

		$app->applyConfiguration($config, true);

		$this->assertSame(\Prado\TApplicationMode::Debug, $app->getMode());
	}

	public function testApplyConfiguration_notWithinService_appliesProperties(): void
	{
		$app = $this->bareApp();
		$this->setProp($app, '_mode', \Prado\TApplicationMode::Debug);

		// Config says Mode=Performance — must be applied when withinService=false.
		$config = $this->minimalConfigMock(['Mode' => \Prado\TApplicationMode::Performance]);

		$app->applyConfiguration($config, false);

		$this->assertSame(\Prado\TApplicationMode::Performance, $app->getMode());
	}

	// -----------------------------------------------------------------------
	// applyConfiguration() — external include condition/path edge cases
	// -----------------------------------------------------------------------

	public function testApplyConfiguration_skipsExternalIncludeWhenConditionFalse(): void
	{
		AppConfigurationSpy::resetCount();

		$app = $this->bareApp();
		// condition is boolean false — include must be skipped without evaluating the path.
		$config = $this->minimalConfigMock([], ['SomeAlias.config' => false]);

		$app->applyConfiguration($config, false);

		$this->assertSame(0, AppConfigurationSpy::$instanceCount,
			'applyConfiguration() must not load an external include when condition is false');
	}

	public function testApplyConfiguration_throwsWhenExternalIncludeFileInvalid(): void
	{
		$app = $this->bareApp();
		// An alias that cannot be resolved → Prado::getPathOfNamespace returns null.
		$config = $this->minimalConfigMock([], ['NonExistentAlias999.config' => true]);

		$this->expectException(\Prado\Exceptions\TConfigurationException::class);
		$app->applyConfiguration($config, false);
	}
}
