<?php

/**
 * TApplicationFinalParameterTest class file.
 *
 * Covers final application parameters (issue #296) through
 * {@see \Prado\TApplication::applyConfiguration()}: a parameter marked
 * `final="true"` in the application configuration keeps its value against
 * folder `config.xml` files, later configurations, and code.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit;

use Prado\Collections\TMap;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\TApplication;
use Prado\TApplicationConfiguration;
use Prado\TApplicationParameters;
use Prado\Web\Services\TPageConfiguration;
use Prado\Xml\TXmlDocument;

class TApplicationFinalParameterTest extends \PHPUnit\Framework\TestCase
{
	/** Builds a bare TApplication with the minimum state applyConfiguration() reads. */
	private function bareApp(?TMap $parameters = null): TApplication
	{
		$app = (new \ReflectionClass(TApplication::class))->newInstanceWithoutConstructor();
		PradoUnit::setProp($app, '_configType', TApplication::CONFIG_TYPE_XML);
		PradoUnit::setProp($app, '_basePath', sys_get_temp_dir());
		PradoUnit::setProp($app, '_pageServiceID', 'page');
		PradoUnit::setProp($app, '_services', []);
		PradoUnit::setProp($app, '_parameters', $parameters ?? new TApplicationParameters());
		PradoUnit::setProp($app, '_modules', []);
		PradoUnit::setProp($app, '_lazyModules', []);
		return $app;
	}

	private function xml(string $string): TXmlDocument
	{
		$dom = new TXmlDocument();
		$dom->loadFromString($string);
		return $dom;
	}

	private function xmlConfig(string $parameters): TApplicationConfiguration
	{
		$config = new TApplicationConfiguration();
		$config->loadFromXml($this->xml('<application><parameters>' . $parameters . '</parameters></application>'), sys_get_temp_dir());
		return $config;
	}

	/** Parses a folder config.xml the way TPageService does. */
	private function folderConfigs(string $parameters): array
	{
		$pageConfig = new TPageConfiguration('Admin.Home');
		$pageConfig->loadFromXml($this->xml('<configuration><parameters>' . $parameters . '</parameters></configuration>'), sys_get_temp_dir(), 'Admin');
		return $pageConfig->getApplicationConfigurations();
	}

	public function testNewParameters_isTApplicationParameters(): void
	{
		$this->assertInstanceOf(TApplicationParameters::class, Prado::getApplication()->getParameters());
	}

	public function testApply_finalizesMarkedParameters(): void
	{
		$app = $this->bareApp();
		$app->applyConfiguration($this->xmlConfig(
			'<parameter id="Locked" value="App" final="true"/><parameter id="Open" value="App"/>'
		));

		$params = $app->getParameters();
		$this->assertSame('App', $params['Locked']);
		$this->assertSame('App', $params['Open']);
		$this->assertSame(['Locked'], $params->getFinalKeys());
	}

	public function testFolderConfig_cannotOverrideFinalParameter(): void
	{
		$app = $this->bareApp();
		$app->applyConfiguration($this->xmlConfig('<parameter id="TestParam" value="--App Magic--" final="true"/>'));

		try {
			foreach ($this->folderConfigs('<parameter id="TestParam" value="--Folder Magic--"/>') as $config) {
				$app->applyConfiguration($config);
			}
			$this->fail('Expected TInvalidOperationException');
		} catch (TInvalidOperationException $e) {
			$this->assertSame('appparameters_final', $e->getErrorCode());
			$this->assertStringContainsString('TestParam', $e->getMessage());
		}
		$this->assertSame('--App Magic--', $app->getParameters()['TestParam']);
	}

	public function testFolderConfig_overridesNonFinalParameter(): void
	{
		$app = $this->bareApp();
		$app->applyConfiguration($this->xmlConfig('<parameter id="TestParam" value="--App Magic--" final="false"/>'));
		foreach ($this->folderConfigs('<parameter id="TestParam" value="--Folder Magic--"/>') as $config) {
			$app->applyConfiguration($config);
		}
		$this->assertSame('--Folder Magic--', $app->getParameters()['TestParam']);
	}

	public function testFolderConfig_canFinalizeItsOwnParameter(): void
	{
		$app = $this->bareApp();
		$app->applyConfiguration($this->xmlConfig('<parameter id="TestParam" value="--App Magic--"/>'));
		foreach ($this->folderConfigs('<parameter id="TestParam" value="--Folder Magic--" final="true"/>') as $config) {
			$app->applyConfiguration($config);
		}
		$params = $app->getParameters();
		$this->assertSame('--Folder Magic--', $params['TestParam']);
		$this->assertTrue($params->getIsFinal('TestParam'));
	}

	public function testCode_cannotChangeFinalParameter(): void
	{
		$app = $this->bareApp();
		$app->applyConfiguration($this->xmlConfig('<parameter id="DeveloperMode" value="0" final="true"/>'));
		$params = $app->getParameters();

		$this->expectException(TInvalidOperationException::class);
		$params['DeveloperMode'] = '1';
	}

	public function testPhpConfig_finalParameter(): void
	{
		$config = new TApplicationConfiguration();
		$config->loadFromPhp(['parameters' => ['DeveloperMode' => ['value' => '0', 'final' => true]]], sys_get_temp_dir());
		$app = $this->bareApp();
		$app->applyConfiguration($config);

		$params = $app->getParameters();
		$this->assertSame('0', $params['DeveloperMode']);
		$this->assertTrue($params->getIsFinal('DeveloperMode'));
	}

	public function testFinalComponentParameter(): void
	{
		$app = $this->bareApp();
		$app->applyConfiguration($this->xmlConfig(
			'<parameter id="Map" class="Prado\Collections\TMap" ReadOnly="false" final="true"/>'
		));
		$params = $app->getParameters();
		$this->assertInstanceOf(TMap::class, $params['Map']);
		$this->assertTrue($params->getIsFinal('Map'));
	}

	public function testPlainTMap_finalParameterThrows(): void
	{
		$app = $this->bareApp(new TMap());
		try {
			$app->applyConfiguration($this->xmlConfig('<parameter id="Locked" value="1" final="true"/>'));
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('appconfig_parameters_final_unsupported', $e->getErrorCode());
			$this->assertStringContainsString('Locked', $e->getMessage());
		}
	}

	public function testPlainTMap_nonFinalParametersApply(): void
	{
		$app = $this->bareApp(new TMap());
		$app->applyConfiguration($this->xmlConfig('<parameter id="Open" value="1"/>'));
		$this->assertSame('1', $app->getParameters()['Open']);
	}
}
