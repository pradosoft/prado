<?php

/**
 * TApplicationConfigurationTest class file.
 *
 * Full-coverage tests for {@see \Prado\TApplicationConfiguration}, the parser that
 * turns an XML or PHP application configuration into the structured tuples that
 * {@see \Prado\TApplication::applyConfiguration()} consumes. The suite drives both
 * formats through every section (application properties, paths, modules, services,
 * parameters, includes, error messages), exercises the configuration-type fallback
 * chain of {@see \Prado\TApplicationConfiguration::loadFromFile()}, and covers the
 * Composer-extension resolution surface ({@see \Prado\TApplicationConfiguration::getComposerExtensionClass()}
 * and the captured `extra.prado` error-message and class-map files).
 *
 * Composer state is injected by seeding {@see \Prado\Util\TComposerReflection}'s static
 * `$_packages` cache; it and the Prado path aliases are restored in tearDown so no
 * test leaks process state.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Prado;
use Prado\TApplication;
use Prado\TApplicationConfiguration;
use Prado\Exceptions\TConfigurationException;
use Prado\Util\TComposerReflection;
use Prado\Xml\TXmlDocument;

class TApplicationConfigurationTest extends PHPUnit\Framework\TestCase
{
	private string $tmpDir;
	private array $createdFiles = [];
	private mixed $aliasSnapshot;
	private mixed $usingSnapshot;
	private mixed $packagesSnapshot;

	protected function setUp(): void
	{
		$this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tac_' . uniqid('', true);
		mkdir($this->tmpDir, 0o777, true);
		$this->aliasSnapshot = PradoUnit::getStaticProp(Prado::class, '_aliases');
		$this->usingSnapshot = PradoUnit::getStaticProp(Prado::class, '_usings');
		$this->packagesSnapshot = PradoUnit::getStaticProp(TComposerReflection::class, '_packages');
	}

	protected function tearDown(): void
	{
		PradoUnit::setStaticProp(Prado::class, '_aliases', $this->aliasSnapshot);
		PradoUnit::setStaticProp(Prado::class, '_usings', $this->usingSnapshot);
		PradoUnit::setStaticProp(TComposerReflection::class, '_packages', $this->packagesSnapshot);
		foreach ($this->createdFiles as $f) {
			if (is_file($f)) {
				unlink($f);
			}
		}
		$this->removeTree($this->tmpDir);
	}

	// =======================================================================
	// Helpers
	// =======================================================================

	private function config(): TApplicationConfiguration
	{
		return new TApplicationConfiguration();
	}

	private function xml(string $string): TXmlDocument
	{
		$dom = new TXmlDocument();
		$dom->loadFromString($string);
		return $dom;
	}

	private function writeFile(string $name, string $contents): string
	{
		$path = $this->tmpDir . DIRECTORY_SEPARATOR . $name;
		file_put_contents($path, $contents);
		$this->createdFiles[] = $path;
		return $path;
	}

	private function makeDir(string $name): string
	{
		$path = $this->tmpDir . DIRECTORY_SEPARATOR . $name;
		mkdir($path, 0o777, true);
		return $path;
	}

	private function seedPackages(array $packages): void
	{
		PradoUnit::setStaticProp(TComposerReflection::class, '_packages', $packages);
	}

	private function removeTree(string $dir): void
	{
		if (!is_dir($dir)) {
			return;
		}
		foreach (scandir($dir) as $entry) {
			if ($entry === '.' || $entry === '..') {
				continue;
			}
			$path = $dir . DIRECTORY_SEPARATOR . $entry;
			is_dir($path) ? $this->removeTree($path) : @unlink($path);
		}
		@rmdir($dir);
	}

	// =======================================================================
	// Initial state and ConfigurationType accessors
	// =======================================================================

	public function testIsEmpty_trueByDefault(): void
	{
		$this->assertTrue($this->config()->getIsEmpty());
	}

	public function testConfigurationType_defaultsToNull(): void
	{
		$this->assertNull($this->config()->getConfigurationType());
	}

	public function testConfigurationType_setAndGet(): void
	{
		$config = $this->config();
		$config->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		$this->assertSame(TApplication::CONFIG_TYPE_PHP, $config->getConfigurationType());
		$config->setConfigurationType(null);
		$this->assertNull($config->getConfigurationType());
	}

	public function testEmptyGetters_returnEmptyArrays(): void
	{
		$config = $this->config();
		$this->assertSame([], $config->getProperties());
		$this->assertSame([], $config->getAliases());
		$this->assertSame([], $config->getUsings());
		$this->assertSame([], $config->getModules());
		$this->assertSame([], $config->getServices());
		$this->assertSame([], $config->getParameters());
		$this->assertSame([], $config->getExternalConfigurations());
		$this->assertSame([], $config->getErrorMessages());
		$this->assertSame([], $config->getClassMap());
	}

	// =======================================================================
	// loadFromPhp — top-level dispatch
	// =======================================================================

	public function testLoadFromPhp_applicationProperties(): void
	{
		$config = $this->config();
		$config->loadFromPhp(['application' => ['Mode' => 'Performance', 'DefaultModule' => 'page']], $this->tmpDir);

		$this->assertSame(['Mode' => 'Performance', 'DefaultModule' => 'page'], $config->getProperties());
		$this->assertFalse($config->getIsEmpty());
	}

	public function testLoadFromPhp_emptyArray_staysEmpty(): void
	{
		$config = $this->config();
		$config->loadFromPhp([], $this->tmpDir);
		$this->assertTrue($config->getIsEmpty());
	}

	public function testLoadFromPhp_nonArraySectionsIgnored(): void
	{
		$config = $this->config();
		// Sections that are not arrays must be skipped without error.
		$config->loadFromPhp([
			'paths' => 'nope',
			'modules' => 'nope',
			'services' => 'nope',
			'parameters' => 'nope',
			'includes' => 'nope',
		], $this->tmpDir);
		$this->assertTrue($config->getIsEmpty());
	}

	// =======================================================================
	// loadFromPhp — paths
	// =======================================================================

	public function testLoadPathsPhp_relativeAndAbsoluteAliases(): void
	{
		$sub = $this->makeDir('common');
		$config = $this->config();
		$config->loadFromPhp(['paths' => ['aliases' => [
			'Rel' => 'common',
			'Abs' => $sub,
		]]], $this->tmpDir);

		$aliases = $config->getAliases();
		$this->assertSame(realpath($sub), $aliases['Rel']);
		$this->assertSame(realpath($sub), $aliases['Abs']);
	}

	public function testLoadPathsPhp_usings(): void
	{
		$config = $this->config();
		$config->loadFromPhp(['paths' => ['using' => ['App.Common.*', 'App.Pages.*']]], $this->tmpDir);
		$this->assertSame(['App.Common.*', 'App.Pages.*'], $config->getUsings());
	}

	public function testLoadPathsPhp_invalidAliasPathThrows(): void
	{
		$config = $this->config();
		try {
			$config->loadFromPhp(['paths' => ['aliases' => ['Bad' => 'does-not-exist']]], $this->tmpDir);
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('appconfig_aliaspath_invalid', $e->getErrorCode());
		}
	}

	public function testLoadPathsPhp_redefinedAliasThrows(): void
	{
		$this->makeDir('common');
		$config = $this->config();
		// First definition succeeds via XML, then the PHP definition collides.
		$config->loadFromPhp(['paths' => ['aliases' => ['Dup' => 'common']]], $this->tmpDir);
		try {
			$config->loadFromPhp(['paths' => ['aliases' => ['Dup' => 'common']]], $this->tmpDir);
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('appconfig_alias_redefined', $e->getErrorCode());
		}
	}

	// =======================================================================
	// loadFromXml — paths
	// =======================================================================

	public function testLoadPathsXml_aliasAndUsing(): void
	{
		$sub = $this->makeDir('common');
		$config = $this->config();
		$config->loadFromXml($this->xml(
			'<application><paths>'
			. '<alias id="Common" path="common"/>'
			. '<using namespace="App.Common.*"/>'
			. '</paths></application>'
		), $this->tmpDir);

		$this->assertSame(['Common' => realpath($sub)], $config->getAliases());
		$this->assertSame(['App.Common.*'], $config->getUsings());
		$this->assertFalse($config->getIsEmpty());
	}

	public function testLoadPathsXml_aliasMissingAttributesThrows(): void
	{
		$config = $this->config();
		try {
			$config->loadFromXml($this->xml('<application><paths><alias id="x"/></paths></application>'), $this->tmpDir);
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('appconfig_alias_invalid', $e->getErrorCode());
		}
	}

	public function testLoadPathsXml_usingMissingNamespaceThrows(): void
	{
		$config = $this->config();
		try {
			$config->loadFromXml($this->xml('<application><paths><using/></paths></application>'), $this->tmpDir);
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('appconfig_using_invalid', $e->getErrorCode());
		}
	}

	public function testLoadPathsXml_invalidPathThrows(): void
	{
		$config = $this->config();
		try {
			$config->loadFromXml($this->xml('<application><paths><alias id="x" path="ghost"/></paths></application>'), $this->tmpDir);
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('appconfig_aliaspath_invalid', $e->getErrorCode());
		}
	}

	public function testLoadPathsXml_redefinedAliasThrows(): void
	{
		$this->makeDir('common');
		$config = $this->config();
		try {
			$config->loadFromXml($this->xml(
				'<application><paths>'
				. '<alias id="Dup" path="common"/>'
				. '<alias id="Dup" path="common"/>'
				. '</paths></application>'
			), $this->tmpDir);
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('appconfig_alias_redefined', $e->getErrorCode());
		}
	}

	public function testLoadPathsXml_unknownTagThrows(): void
	{
		$config = $this->config();
		try {
			$config->loadFromXml($this->xml('<application><paths><bogus/></paths></application>'), $this->tmpDir);
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('appconfig_paths_invalid', $e->getErrorCode());
		}
	}

	// =======================================================================
	// Modules — PHP
	// =======================================================================

	public function testLoadModulesPhp_classPropertiesAndIdInjected(): void
	{
		$config = $this->config();
		$config->loadFromPhp(['modules' => [
			'cache' => ['class' => 'TFileCache', 'properties' => ['Directory' => 'runtime'], 'extra' => 'kept'],
		]], $this->tmpDir);

		$modules = $config->getModules();
		[$type, $properties, $element] = $modules['cache'];
		$this->assertSame('TFileCache', $type);
		$this->assertSame(['Directory' => 'runtime', 'id' => 'cache'], $properties);
		// The leftover body keeps non-class, non-properties keys.
		$this->assertSame(['extra' => 'kept'], $element);
		$this->assertFalse($config->getIsEmpty());
	}

	public function testLoadModulesPhp_missingClassThrows(): void
	{
		$config = $this->config();
		try {
			$config->loadFromPhp(['modules' => ['noclass' => ['properties' => []]]], $this->tmpDir);
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('appconfig_moduletype_required', $e->getErrorCode());
		}
	}

	public function testLoadModulesPhp_composerPackageResolvesClass(): void
	{
		$this->seedPackages([
			'acme/mod' => ['name' => 'acme/mod', 'extra' => ['prado' => ['bootstrap' => 'Acme\\Module']]],
		]);
		$config = $this->config();
		$config->loadFromPhp(['modules' => ['acme/mod' => []]], $this->tmpDir);

		$modules = $config->getModules();
		$this->assertSame('Acme\\Module', $modules['acme/mod'][0]);
	}

	public function testLoadModulesPhp_composerPackageWithClassThrows(): void
	{
		$this->seedPackages([
			'acme/mod' => ['name' => 'acme/mod', 'extra' => ['prado' => ['bootstrap' => 'Acme\\Module']]],
		]);
		$config = $this->config();
		try {
			$config->loadFromPhp(['modules' => ['acme/mod' => ['class' => 'Other']]], $this->tmpDir);
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('appconfig_moduletype_inapplicable', $e->getErrorCode());
		}
	}

	// =======================================================================
	// Modules — XML
	// =======================================================================

	public function testLoadModulesXml_keyedById(): void
	{
		$config = $this->config();
		$config->loadFromXml($this->xml(
			'<application><modules><module id="cache" class="TFileCache" Directory="runtime"/></modules></application>'
		), $this->tmpDir);

		$modules = $config->getModules();
		$this->assertArrayHasKey('cache', $modules);
		[$type, $properties] = $modules['cache'];
		$this->assertSame('TFileCache', $type);
		$this->assertSame('runtime', $properties['Directory']);
		$this->assertSame('cache', $properties['id']);
	}

	public function testLoadModulesXml_anonymousModuleGetsNumericKey(): void
	{
		$config = $this->config();
		$config->loadFromXml($this->xml(
			'<application><modules><module class="TFileCache"/></modules></application>'
		), $this->tmpDir);

		$modules = $config->getModules();
		$this->assertArrayHasKey(0, $modules);
		$this->assertSame('TFileCache', $modules[0][0]);
	}

	public function testLoadModulesXml_missingClassThrows(): void
	{
		$config = $this->config();
		try {
			$config->loadFromXml($this->xml('<application><modules><module id="x"/></modules></application>'), $this->tmpDir);
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('appconfig_moduletype_required', $e->getErrorCode());
		}
	}

	public function testLoadModulesXml_composerPackageResolvesClass(): void
	{
		$this->seedPackages([
			'acme/mod' => ['name' => 'acme/mod', 'extra' => ['prado' => ['bootstrap' => 'Acme\\Module']]],
		]);
		$config = $this->config();
		$config->loadFromXml($this->xml('<application><modules><module id="acme/mod"/></modules></application>'), $this->tmpDir);

		$modules = $config->getModules();
		$this->assertSame('Acme\\Module', $modules['acme/mod'][0]);
	}

	public function testLoadModulesXml_composerPackageWithClassThrows(): void
	{
		$this->seedPackages([
			'acme/mod' => ['name' => 'acme/mod', 'extra' => ['prado' => ['bootstrap' => 'Acme\\Module']]],
		]);
		$config = $this->config();
		try {
			$config->loadFromXml($this->xml('<application><modules><module id="acme/mod" class="Other"/></modules></application>'), $this->tmpDir);
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('appconfig_moduletype_inapplicable', $e->getErrorCode());
		}
	}

	public function testLoadModulesXml_unknownTagThrows(): void
	{
		$config = $this->config();
		try {
			$config->loadFromXml($this->xml('<application><modules><bogus/></modules></application>'), $this->tmpDir);
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('appconfig_modules_invalid', $e->getErrorCode());
		}
	}

	// =======================================================================
	// Services — PHP
	// =======================================================================

	public function testLoadServicesPhp_storedAsThreeTuple(): void
	{
		$config = $this->config();
		$config->loadFromPhp(['services' => [
			'page' => ['class' => 'TPageService', 'properties' => ['DefaultPage' => 'Home'], 'modules' => ['x']],
		]], $this->tmpDir);

		$services = $config->getServices();
		[$type, $properties, $element] = $services['page'];
		$this->assertSame('TPageService', $type);
		$this->assertSame(['DefaultPage' => 'Home', 'id' => 'page'], $properties);
		// The remaining body is held verbatim for lazy startup.
		$this->assertSame(['class' => 'TPageService', 'modules' => ['x']], $element);
		$this->assertFalse($config->getIsEmpty());
	}

	public function testLoadServicesPhp_missingClassThrows(): void
	{
		$config = $this->config();
		try {
			$config->loadFromPhp(['services' => ['page' => ['properties' => []]]], $this->tmpDir);
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('appconfig_servicetype_required', $e->getErrorCode());
		}
	}

	// =======================================================================
	// Services — XML
	// =======================================================================

	public function testLoadServicesXml_keyedById(): void
	{
		$config = $this->config();
		$config->loadFromXml($this->xml(
			'<application><services><service id="page" class="TPageService" DefaultPage="Home"/></services></application>'
		), $this->tmpDir);

		$services = $config->getServices();
		[$type, $properties] = $services['page'];
		$this->assertSame('TPageService', $type);
		$this->assertSame('Home', $properties['DefaultPage']);
		$this->assertSame('page', $properties['id']);
	}

	public function testLoadServicesXml_missingIdThrows(): void
	{
		$config = $this->config();
		try {
			$config->loadFromXml($this->xml('<application><services><service class="TPageService"/></services></application>'), $this->tmpDir);
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('appconfig_serviceid_required', $e->getErrorCode());
		}
	}

	public function testLoadServicesXml_missingClassThrows(): void
	{
		$config = $this->config();
		try {
			$config->loadFromXml($this->xml('<application><services><service id="page"/></services></application>'), $this->tmpDir);
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('appconfig_servicetype_required', $e->getErrorCode());
		}
	}

	public function testLoadServicesXml_unknownTagThrows(): void
	{
		$config = $this->config();
		try {
			$config->loadFromXml($this->xml('<application><services><bogus/></services></application>'), $this->tmpDir);
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('appconfig_services_invalid', $e->getErrorCode());
		}
	}

	// =======================================================================
	// Parameters — PHP
	// =======================================================================

	public function testLoadParametersPhp_scalar(): void
	{
		$config = $this->config();
		$config->loadFromPhp(['parameters' => ['SiteName' => 'My Site']], $this->tmpDir);
		$this->assertSame(['SiteName' => 'My Site'], $config->getParameters());
	}

	public function testLoadParametersPhp_componentTyped(): void
	{
		$config = $this->config();
		$config->loadFromPhp(['parameters' => [
			'Mailer' => ['class' => 'TMailer', 'properties' => ['Host' => 'smtp']],
		]], $this->tmpDir);

		$params = $config->getParameters();
		[$type, $properties, $element] = $params['Mailer'];
		$this->assertSame('TMailer', $type);
		$this->assertSame(['Host' => 'smtp', 'id' => 'Mailer'], $properties);
		$this->assertSame(['properties' => ['Host' => 'smtp']], $element);
	}

	public function testLoadParametersPhp_arrayWithoutClassIgnored(): void
	{
		$config = $this->config();
		// An array parameter without a class key is not a component definition; skipped.
		$config->loadFromPhp(['parameters' => ['Bad' => ['no' => 'class']]], $this->tmpDir);
		$this->assertSame([], $config->getParameters());
	}

	// =======================================================================
	// Parameters — XML
	// =======================================================================

	public function testLoadParametersXml_value(): void
	{
		$config = $this->config();
		$config->loadFromXml($this->xml(
			'<application><parameters><parameter id="SiteName" value="My Site"/></parameters></application>'
		), $this->tmpDir);
		$this->assertSame(['SiteName' => 'My Site'], $config->getParameters());
	}

	public function testLoadParametersXml_componentTyped(): void
	{
		$config = $this->config();
		$config->loadFromXml($this->xml(
			'<application><parameters><parameter id="Mailer" class="TMailer" Host="smtp"/></parameters></application>'
		), $this->tmpDir);

		$params = $config->getParameters();
		[$type, $properties] = $params['Mailer'];
		$this->assertSame('TMailer', $type);
		$this->assertSame('smtp', $properties['Host']);
	}

	public function testLoadParametersXml_noValueNoClassStoresElement(): void
	{
		$config = $this->config();
		$config->loadFromXml($this->xml(
			'<application><parameters><parameter id="Raw">body</parameter></parameters></application>'
		), $this->tmpDir);

		$params = $config->getParameters();
		$this->assertInstanceOf(\Prado\Xml\TXmlElement::class, $params['Raw']);
	}

	public function testLoadParametersXml_missingIdThrows(): void
	{
		$config = $this->config();
		try {
			$config->loadFromXml($this->xml('<application><parameters><parameter value="x"/></parameters></application>'), $this->tmpDir);
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('appconfig_parameterid_required', $e->getErrorCode());
		}
	}

	public function testLoadParametersXml_unknownTagThrows(): void
	{
		$config = $this->config();
		try {
			$config->loadFromXml($this->xml('<application><parameters><bogus/></parameters></application>'), $this->tmpDir);
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('appconfig_parameters_invalid', $e->getErrorCode());
		}
	}

	// =======================================================================
	// Includes — PHP
	// =======================================================================

	public function testLoadExternalPhp_defaultWhenIsTrue(): void
	{
		$config = $this->config();
		$config->loadFromPhp(['includes' => [['file' => 'extra.xml']]], $this->tmpDir);
		$this->assertSame(['extra.xml' => true], $config->getExternalConfigurations());
		$this->assertFalse($config->getIsEmpty());
	}

	public function testLoadExternalPhp_whenExpressionKept(): void
	{
		$config = $this->config();
		$config->loadFromPhp(['includes' => [['file' => 'extra.xml', 'when' => '$this->getMode()=="Debug"']]], $this->tmpDir);
		$this->assertSame(['extra.xml' => '$this->getMode()=="Debug"'], $config->getExternalConfigurations());
	}

	public function testLoadExternalPhp_duplicateFileMerged(): void
	{
		$config = $this->config();
		$config->loadFromPhp(['includes' => [
			['file' => 'extra.xml', 'when' => 'a'],
			['file' => 'extra.xml', 'when' => 'b'],
		]], $this->tmpDir);
		$this->assertSame(['extra.xml' => '(a) || (b)'], $config->getExternalConfigurations());
	}

	public function testLoadExternalPhp_missingFileThrows(): void
	{
		$config = $this->config();
		try {
			$config->loadFromPhp(['includes' => [['when' => 'a']]], $this->tmpDir);
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('appconfig_includefile_required', $e->getErrorCode());
		}
	}

	// =======================================================================
	// Includes — XML
	// =======================================================================

	public function testLoadExternalXml_defaultWhenIsTrue(): void
	{
		$config = $this->config();
		$config->loadFromXml($this->xml('<application><include file="extra.xml"/></application>'), $this->tmpDir);
		$this->assertSame(['extra.xml' => true], $config->getExternalConfigurations());
	}

	public function testLoadExternalXml_whenExpressionKept(): void
	{
		$config = $this->config();
		$config->loadFromXml($this->xml('<application><include file="extra.xml" when="cond"/></application>'), $this->tmpDir);
		$this->assertSame(['extra.xml' => 'cond'], $config->getExternalConfigurations());
	}

	public function testLoadExternalXml_duplicateFileMerged(): void
	{
		$config = $this->config();
		$config->loadFromXml($this->xml(
			'<application>'
			. '<include file="extra.xml" when="a"/>'
			. '<include file="extra.xml" when="b"/>'
			. '</application>'
		), $this->tmpDir);
		$this->assertSame(['extra.xml' => '(a) || (b)'], $config->getExternalConfigurations());
	}

	public function testLoadExternalXml_missingFileThrows(): void
	{
		$config = $this->config();
		try {
			$config->loadFromXml($this->xml('<application><include when="a"/></application>'), $this->tmpDir);
			$this->fail('Expected TConfigurationException');
		} catch (TConfigurationException $e) {
			$this->assertSame('appconfig_includefile_required', $e->getErrorCode());
		}
	}

	// =======================================================================
	// loadFromXml — root attributes and unknown tags
	// =======================================================================

	public function testLoadFromXml_rootAttributesBecomeProperties(): void
	{
		$config = $this->config();
		$config->loadFromXml($this->xml('<application Mode="Performance" id="app"/>'), $this->tmpDir);
		$this->assertSame(['Mode' => 'Performance', 'id' => 'app'], $config->getProperties());
		$this->assertFalse($config->getIsEmpty());
	}

	public function testLoadFromXml_unknownTopLevelTagIgnored(): void
	{
		$config = $this->config();
		// Unknown elements hit the default branch and are silently ignored.
		$config->loadFromXml($this->xml('<application><bogus/></application>'), $this->tmpDir);
		$this->assertTrue($config->getIsEmpty());
	}

	// =======================================================================
	// Error messages
	// =======================================================================

	public function testErrorMessages_xmlTagRelativePath(): void
	{
		$config = $this->config();
		$config->loadFromXml($this->xml('<application><errorMessage file="messages-app.txt"/></application>'), $this->tmpDir);
		$this->assertSame([$this->tmpDir . DIRECTORY_SEPARATOR . 'messages-app.txt'], $config->getErrorMessages());
		$this->assertFalse($config->getIsEmpty());
	}

	public function testErrorMessages_xmlTagEmptyFileIgnored(): void
	{
		$config = $this->config();
		$config->loadFromXml($this->xml('<application><errorMessage file=""/></application>'), $this->tmpDir);
		$this->assertSame([], $config->getErrorMessages());
	}

	public function testErrorMessages_phpKeyStringAndArray(): void
	{
		$config = $this->config();
		$config->loadFromPhp(['errormessages' => 'one.txt'], $this->tmpDir);
		$this->assertSame([$this->tmpDir . DIRECTORY_SEPARATOR . 'one.txt'], $config->getErrorMessages());

		$config2 = $this->config();
		$config2->loadFromPhp(['errormessages' => ['a.txt', 'b.txt']], $this->tmpDir);
		$this->assertSame([
			$this->tmpDir . DIRECTORY_SEPARATOR . 'a.txt',
			$this->tmpDir . DIRECTORY_SEPARATOR . 'b.txt',
		], $config2->getErrorMessages());
	}

	public function testErrorMessages_absolutePathKept(): void
	{
		$abs = $this->writeFile('abs.txt', 'x');
		$config = $this->config();
		$config->loadFromPhp(['errormessages' => [$abs]], $this->tmpDir);
		$this->assertSame([$abs], $config->getErrorMessages());
	}

	public function testErrorMessages_deduplicated(): void
	{
		$config = $this->config();
		$config->loadFromPhp(['errormessages' => ['same.txt', 'same.txt']], $this->tmpDir);
		$this->assertSame([$this->tmpDir . DIRECTORY_SEPARATOR . 'same.txt'], $config->getErrorMessages());
	}

	public function testErrorMessages_namespaceResolvedToExistingFile(): void
	{
		// A namespace that resolves (no extension) to a real file is used verbatim.
		$this->writeFile('msg', 'x');
		// setPathOfAlias canonicalizes the directory, so the resolved namespace
		// path is the realpath'd directory plus the file segment.
		Prado::setPathOfAlias('TacTmp', $this->tmpDir);
		$config = $this->config();
		$config->loadFromPhp(['errormessages' => ['TacTmp.msg']], $this->tmpDir);
		$this->assertSame([realpath($this->tmpDir) . DIRECTORY_SEPARATOR . 'msg'], $config->getErrorMessages());
	}

	// =======================================================================
	// Composer extension class resolution
	// =======================================================================

	public function testGetComposerExtensionClass_pradoExtra(): void
	{
		$this->seedPackages([
			'acme/pkg' => ['name' => 'acme/pkg', 'extra' => ['prado' => ['bootstrap' => 'Acme\\Boot']]],
		]);
		$this->assertSame('Acme\\Boot', $this->config()->getComposerExtensionClass('acme/pkg'));
	}

	public function testGetComposerExtensionClass_legacyFallback(): void
	{
		$this->seedPackages([
			'acme/legacy' => ['name' => 'acme/legacy', 'extra' => ['bootstrap' => 'Acme\\Legacy']],
		]);
		$this->assertSame('Acme\\Legacy', $this->config()->getComposerExtensionClass('acme/legacy'));
	}

	public function testGetComposerExtensionClass_noneReturnsNull(): void
	{
		$this->seedPackages([
			'acme/none' => ['name' => 'acme/none'],
		]);
		$this->assertNull($this->config()->getComposerExtensionClass('acme/none'));
	}

	public function testGetComposerExtensionClass_unknownPackageReturnsNull(): void
	{
		$this->seedPackages([]);
		$this->assertNull($this->config()->getComposerExtensionClass('ghost/missing'));
	}

	public function testGetComposerExtensionClassLegacy_directInvoke(): void
	{
		$this->seedPackages([
			'acme/legacy' => ['name' => 'acme/legacy', 'extra' => ['bootstrap' => 'Acme\\Legacy']],
		]);
		$this->assertSame(
			'Acme\\Legacy',
			PradoUnit::invoke($this->config(), 'getComposerExtensionClassLegacy', 'acme/legacy')
		);
	}

	// =======================================================================
	// captureComposerExtensions — system-wide extension error-messages and class-map
	// =======================================================================

	public function testCaptureComposerExtensions_collectsErrorMessagesAndInlineClassMap(): void
	{
		// getPackagePath() resolves through Composer's InstalledVersions runtime, so
		// the extension must be a real installed package; its extra.prado metadata is
		// seeded into the in-memory cache that getInstalledPackages()/getPradoExtra() read.
		$pkg = 'phpunit/phpunit';
		$base = TComposerReflection::getPackagePath($pkg);
		$this->seedPackages([
			$pkg => [
				'name' => $pkg,
				'extra' => ['prado' => [
					'error-messages' => 'messages.txt',
					'class-map' => ['TAcmeWidget' => 'Acme\\Widget\\TAcmeWidget'],
				]],
			],
		]);
		$config = $this->config();
		// No modules declared — the extension's data loads system-wide regardless.
		$config->captureComposerExtensions();

		$this->assertSame([$base . DIRECTORY_SEPARATOR . 'messages.txt'], $config->getErrorMessages());
		$this->assertSame(['TAcmeWidget' => 'Acme\\Widget\\TAcmeWidget'], $config->getClassMap());
		// Capturing extension data makes the configuration non-empty so it is applied.
		$this->assertFalse($config->getIsEmpty());
	}

	public function testCaptureComposerExtensions_loadsExtensionNotInConfigModules(): void
	{
		// The extension's module is never referenced in the configuration, yet its
		// error-messages still load — module inclusion is a separate opt-in.
		$pkg = 'phpunit/phpunit';
		$base = TComposerReflection::getPackagePath($pkg);
		$this->seedPackages([
			$pkg => ['name' => $pkg, 'extra' => ['prado' => ['error-messages' => 'm.txt']]],
		]);
		$config = $this->config();
		$config->captureComposerExtensions();
		$this->assertSame([$base . DIRECTORY_SEPARATOR . 'm.txt'], $config->getErrorMessages());
	}

	public function testCaptureComposerExtensions_skipsPackagesWithoutPradoExtra(): void
	{
		// A package with no extra.prado section contributes nothing.
		$this->seedPackages([
			'acme/plain' => ['name' => 'acme/plain', 'extra' => ['other' => 'x']],
			'acme/none' => ['name' => 'acme/none'],
		]);
		$config = $this->config();
		$config->captureComposerExtensions();
		$this->assertSame([], $config->getErrorMessages());
		$this->assertSame([], $config->getClassMap());
		$this->assertTrue($config->getIsEmpty());
	}

	public function testCaptureComposerExtensions_notInstalledExtensionIsNoOp(): void
	{
		// Declares extra.prado but is not installed, so getPackagePath() returns null.
		$this->seedPackages([
			'acme/nopath' => ['name' => 'acme/nopath', 'extra' => ['prado' => [
				'error-messages' => 'messages.txt',
			]]],
		]);
		$config = $this->config();
		$config->captureComposerExtensions();
		$this->assertSame([], $config->getErrorMessages());
		$this->assertSame([], $config->getClassMap());
	}

	public function testCaptureComposerExtensions_intKeyMissingJsonFileIsNoOp(): void
	{
		// An integer key is a JSON class-map file; a missing file contributes nothing.
		$pkg = 'phpunit/phpunit';
		$this->seedPackages([
			$pkg => ['name' => $pkg, 'extra' => ['prado' => ['class-map' => 'no-such-classes.json']]],
		]);
		$config = $this->config();
		$config->captureComposerExtensions();
		$this->assertSame([], $config->getClassMap());
	}

	/** A config whose getFileContents() seam serves canned JSON keyed by absolute path. */
	private function seamConfig(array $cannedFiles): TApplicationConfiguration
	{
		$config = new class () extends TApplicationConfiguration {
			public array $cannedFiles = [];
			protected function getFileContents(string $path): string|false
			{
				return $this->cannedFiles[$path] ?? false;
			}
		};
		$config->cannedFiles = $cannedFiles;
		return $config;
	}

	public function testCaptureComposerExtensions_intKeyJsonFileMergesViaSeam(): void
	{
		// The JSON file lives in the (real) package directory a test cannot write to;
		// the getFileContents() seam supplies its content instead.
		$pkg = 'phpunit/phpunit';
		$base = TComposerReflection::getPackagePath($pkg);
		$this->seedPackages([
			$pkg => ['name' => $pkg, 'extra' => ['prado' => ['class-map' => [
				'config/classes.json',         // numeric (list) key → JSON class-map file
				'TInline' => 'Acme\\TInline',  // class-name key → inline class name => FQN
			]]]],
		]);
		$config = $this->seamConfig([
			$base . DIRECTORY_SEPARATOR . 'config/classes.json' => json_encode(['TFromFile' => 'Acme\\TFromFile']),
		]);

		$config->captureComposerExtensions();

		// The file (numeric key) is processed before the inline (class-name) entry.
		$this->assertSame(
			['TFromFile' => 'Acme\\TFromFile', 'TInline' => 'Acme\\TInline'],
			$config->getClassMap()
		);
	}

	public function testCaptureComposerExtensions_numericStringKeyIsTreatedAsFile(): void
	{
		// A numeric-string key PHP does not fold to int ("08") still marks a file, not an
		// inline class name — is_numeric() gates the file branch where is_int() would not.
		$pkg = 'phpunit/phpunit';
		$base = TComposerReflection::getPackagePath($pkg);
		$this->seedPackages([
			$pkg => ['name' => $pkg, 'extra' => ['prado' => ['class-map' => ['08' => 'config/classes.json']]]],
		]);
		$config = $this->seamConfig([
			$base . DIRECTORY_SEPARATOR . 'config/classes.json' => json_encode(['TFromFile' => 'Acme\\TFromFile']),
		]);

		$config->captureComposerExtensions();

		$this->assertSame(['TFromFile' => 'Acme\\TFromFile'], $config->getClassMap());
	}

	public function testGetFileContents_readsRealFileAndFalseForMissing(): void
	{
		$file = $this->writeFile('seam.txt', 'hello');
		$config = $this->config();
		$this->assertSame('hello', PradoUnit::invoke($config, 'getFileContents', $file));
		$this->assertFalse(PradoUnit::invoke($config, 'getFileContents', $this->tmpDir . DIRECTORY_SEPARATOR . 'gone.txt'));
	}

	public function testMergeClassMap_keepsFirstAndFiltersNonStrings(): void
	{
		$config = $this->config();
		PradoUnit::invoke($config, 'mergeClassMap', ['TA' => 'Acme\\TA', 'TB' => 'Acme\\TB']);
		// First declaration of TA wins; non-string key/value and empty key are dropped.
		PradoUnit::invoke($config, 'mergeClassMap', ['TA' => 'Other\\TA', 5 => 'Ignored', 'TC' => 123, '' => 'x']);
		$this->assertSame(['TA' => 'Acme\\TA', 'TB' => 'Acme\\TB'], $config->getClassMap());
		$this->assertFalse($config->getIsEmpty());
	}

	public function testReadClassMapFile_decodesJsonObject(): void
	{
		$file = $this->writeFile('cm.json', json_encode(['TX' => 'Acme\\TX', 'TY' => 'Acme\\TY']));
		$config = $this->config();
		$this->assertSame(
			['TX' => 'Acme\\TX', 'TY' => 'Acme\\TY'],
			PradoUnit::invoke($config, 'readClassMapFile', $file)
		);
	}

	public function testReadClassMapFile_missingFileReturnsEmpty(): void
	{
		$config = $this->config();
		$this->assertSame([], PradoUnit::invoke($config, 'readClassMapFile', $this->tmpDir . DIRECTORY_SEPARATOR . 'nope.json'));
	}

	public function testReadClassMapFile_nonObjectJsonReturnsEmpty(): void
	{
		$file = $this->writeFile('bad.json', '"just a string"');
		$config = $this->config();
		$this->assertSame([], PradoUnit::invoke($config, 'readClassMapFile', $file));
	}

	public function testLoadModules_composerModuleDoesNotCaptureExtrasByItself(): void
	{
		// Declaring an extension's module resolves its bootstrap class but does not,
		// on its own, capture error-messages/class-map — that is captureComposerExtensions()'s job.
		$pkg = 'phpunit/phpunit';
		$this->seedPackages([
			$pkg => ['name' => $pkg, 'extra' => ['prado' => [
				'bootstrap' => 'Acme\\Boot',
				'error-messages' => 'messages.txt',
				'class-map' => ['TWidget' => 'Acme\\TWidget'],
			]]],
		]);
		$config = $this->config();
		$config->loadFromPhp(['modules' => [$pkg => []]], $this->tmpDir);

		$this->assertSame('Acme\\Boot', $config->getModules()[$pkg][0]);
		$this->assertSame([], $config->getErrorMessages());
		$this->assertSame([], $config->getClassMap());
	}

	// =======================================================================
	// loadFromFile — configuration-type fallback and shape dispatch
	// =======================================================================

	public function testLoadFromFile_explicitPhpType(): void
	{
		$file = $this->writeFile('conf.cfg', "<?php return ['application' => ['Mode' => 'Performance']];");
		$config = $this->config();
		$config->loadFromFile($file, TApplication::CONFIG_TYPE_PHP);
		$this->assertSame(['Mode' => 'Performance'], $config->getProperties());
	}

	public function testLoadFromFile_explicitXmlType(): void
	{
		$file = $this->writeFile('conf.cfg', '<application Mode="Debug"/>');
		$config = $this->config();
		$config->loadFromFile($file, TApplication::CONFIG_TYPE_XML);
		$this->assertSame(['Mode' => 'Debug'], $config->getProperties());
	}

	public function testLoadFromFile_instanceTypeOverridesExtension(): void
	{
		// .xml extension but instance type PHP forces the PHP include path.
		$file = $this->writeFile('looks.xml', "<?php return ['application' => ['Mode' => 'X']];");
		$config = $this->config();
		$config->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		$config->loadFromFile($file);
		$this->assertSame(['Mode' => 'X'], $config->getProperties());
	}

	public function testLoadFromFile_appTypeUsedWhenInstanceNull(): void
	{
		// No explicit/instance type → falls back to the application's type. Set it
		// explicitly (and restore) so the test does not depend on ambient state.
		$app = Prado::getApplication();
		$saved = $app->getConfigurationType();
		$app->setConfigurationType(TApplication::CONFIG_TYPE_XML);
		try {
			$file = $this->writeFile('conf.cfg', '<application Mode="FromApp"/>');
			$config = $this->config();
			$config->loadFromFile($file);
			$this->assertSame(['Mode' => 'FromApp'], $config->getProperties());
		} finally {
			$app->setConfigurationType($saved);
		}
	}

	public function testLoadFromFile_extensionAutoDetectWhenAppTypeNull(): void
	{
		$app = Prado::getApplication();
		$saved = $app->getConfigurationType();
		$app->setConfigurationType(null);
		try {
			$file = $this->writeFile('auto.php', "<?php return ['application' => ['Mode' => 'Auto']];");
			$config = $this->config();
			$config->loadFromFile($file);
			$this->assertSame(['Mode' => 'Auto'], $config->getProperties());
		} finally {
			$app->setConfigurationType($saved);
		}
	}

	public function testLoadFromFile_phpReturningNonArrayIsNoOp(): void
	{
		$file = $this->writeFile('noret.php', '<?php $x = 1;');
		$config = $this->config();
		$config->loadFromFile($file, TApplication::CONFIG_TYPE_PHP);
		$this->assertTrue($config->getIsEmpty());
	}

	// =======================================================================
	// Full document — both formats parse equivalent sections
	// =======================================================================

	public function testFullDocument_xml(): void
	{
		$this->makeDir('common');
		$config = $this->config();
		$config->loadFromXml($this->xml(
			'<application Mode="Performance">'
			. '<paths><alias id="Common" path="common"/><using namespace="App.*"/></paths>'
			. '<parameters><parameter id="SiteName" value="My Site"/></parameters>'
			. '<modules><module id="cache" class="TFileCache"/></modules>'
			. '<services><service id="page" class="TPageService"/></services>'
			. '<include file="extra.xml"/>'
			. '</application>'
		), $this->tmpDir);

		$this->assertSame('Performance', $config->getProperties()['Mode']);
		$this->assertArrayHasKey('Common', $config->getAliases());
		$this->assertSame(['App.*'], $config->getUsings());
		$this->assertSame(['SiteName' => 'My Site'], $config->getParameters());
		$this->assertArrayHasKey('cache', $config->getModules());
		$this->assertArrayHasKey('page', $config->getServices());
		$this->assertSame(['extra.xml' => true], $config->getExternalConfigurations());
		$this->assertFalse($config->getIsEmpty());
	}

	public function testFullDocument_php(): void
	{
		$this->makeDir('common');
		$config = $this->config();
		$config->loadFromPhp([
			'application' => ['Mode' => 'Performance'],
			'paths' => ['aliases' => ['Common' => 'common'], 'using' => ['App.*']],
			'parameters' => ['SiteName' => 'My Site'],
			'modules' => ['cache' => ['class' => 'TFileCache']],
			'services' => ['page' => ['class' => 'TPageService']],
			'includes' => [['file' => 'extra.xml']],
		], $this->tmpDir);

		$this->assertSame('Performance', $config->getProperties()['Mode']);
		$this->assertArrayHasKey('Common', $config->getAliases());
		$this->assertSame(['App.*'], $config->getUsings());
		$this->assertSame(['SiteName' => 'My Site'], $config->getParameters());
		$this->assertArrayHasKey('cache', $config->getModules());
		$this->assertArrayHasKey('page', $config->getServices());
		$this->assertSame(['extra.xml' => true], $config->getExternalConfigurations());
		$this->assertFalse($config->getIsEmpty());
	}
}
