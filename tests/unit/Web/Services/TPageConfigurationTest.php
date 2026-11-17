<?php

/**
 * TPageConfigurationTest
 *
 * Unit tests for {@see \Prado\Web\Services\TPageConfiguration}.
 *
 * The file tests run against `PageConfigurationApp/` beside this file: `xml/`
 * and `php/` each hold a root `config` file and an `Admin/config` file, and
 * `empty/` holds an empty `config.xml`.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

namespace Prado\Test\Unit\Web\Services;

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\Security\TAuthorizationRuleCollection;
use Prado\TApplication;
use Prado\TApplicationConfiguration;
use Prado\TComponent;
use Prado\Web\Services\TPageConfiguration;
use Prado\Xml\TXmlDocument;

class TPageConfigurationTest extends \PHPUnit\Framework\TestCase
{
	private string $_configurationType;

	protected function setUp(): void
	{
		$this->_configurationType = Prado::getApplication()->getConfigurationType();
	}

	protected function tearDown(): void
	{
		Prado::getApplication()->setConfigurationType($this->_configurationType);
	}

	/**
	 * Returns the path of a fixture directory.
	 * @param string $dir the directory below `PageConfigurationApp`.
	 */
	private function fixture(string $dir): string
	{
		return __DIR__ . DIRECTORY_SEPARATOR . 'PageConfigurationApp' . DIRECTORY_SEPARATOR . $dir;
	}

	/**
	 * Parses an XML configuration.
	 * @param string $xml the configuration.
	 */
	private function xml(string $xml): TXmlDocument
	{
		$dom = new TXmlDocument();
		$dom->loadFromString($xml);
		return $dom;
	}

	/**
	 * Describes each rule as 'action:users:roles' in the order they were added.
	 * @param array|TAuthorizationRuleCollection $rules the rules.
	 */
	private function describe($rules): array
	{
		$list = [];
		foreach ($rules as $rule) {
			$list[] = $rule->getAction() . ':' . implode(',', $rule->getUsers()) . ':' . implode(',', $rule->getRoles());
		}
		return $list;
	}

	// -------------------------------------------------------------------------
	// Construction
	// -------------------------------------------------------------------------

	/**
	 * A new configuration holds nothing.
	 */
	public function testANewConfigurationIsEmpty()
	{
		$config = new TPageConfiguration('Home');
		$this->assertSame([], $config->getExternalConfigurations());
		$this->assertSame([], $config->getProperties());
		$this->assertSame([], $config->getRules());
		$this->assertSame([], $config->getApplicationConfigurations());
	}

	// -------------------------------------------------------------------------
	// Files
	// -------------------------------------------------------------------------

	/**
	 * loadFromFiles() merges the configuration file of each directory on the
	 * page path, the deeper rules first.
	 * @dataProvider configurationTypeProvider
	 * @param string $type the application configuration type.
	 */
	public function testLoadFromFilesMergesEachDirectoryOnThePath(string $type)
	{
		Prado::getApplication()->setConfigurationType($type);
		$config = new TPageConfiguration('Admin.Users');
		$config->loadFromFiles($this->fixture($type));

		$this->assertSame(['Theme' => 'Basic', 'Title' => 'Admin users'], $config->getProperties());
		$this->assertInstanceOf(TAuthorizationRuleCollection::class, $config->getRules());
		$this->assertSame(['deny::*', 'allow::admin'], $this->describe($config->getRules()));
		$this->assertCount(2, $config->getApplicationConfigurations());
		$this->assertContainsOnlyInstancesOf(TApplicationConfiguration::class, $config->getApplicationConfigurations());
		$this->assertSame('root', $config->getApplicationConfigurations()[0]->getParameters()['RootParameter']);
	}

	/**
	 * loadFromFiles() for a page at the base reads the base file only.
	 * @dataProvider configurationTypeProvider
	 * @param string $type the application configuration type.
	 */
	public function testLoadFromFilesForAPageAtTheBase(string $type)
	{
		Prado::getApplication()->setConfigurationType($type);
		$config = new TPageConfiguration('Home');
		$config->loadFromFiles($this->fixture($type));

		$this->assertSame(['Theme' => 'Basic', 'Title' => 'Root home'], $config->getProperties());
		$this->assertSame([], $this->describe($config->getRules()));
		$this->assertCount(1, $config->getApplicationConfigurations());
	}

	/**
	 * loadFromFiles() joins the directories of a deeper path with dots, so a
	 * rule of the base file names the full page path.
	 */
	public function testLoadFromFilesJoinsADeeperPath()
	{
		Prado::getApplication()->setConfigurationType(TApplication::CONFIG_TYPE_XML);
		$config = new TPageConfiguration('Admin.Reports.Monthly');
		$config->loadFromFiles($this->fixture('xml'));

		$this->assertSame(['Theme' => 'Basic'], $config->getProperties());
		$this->assertSame(['deny::*', 'allow::admin'], $this->describe($config->getRules()));
	}

	public static function configurationTypeProvider(): array
	{
		return [
			'xml' => [TApplication::CONFIG_TYPE_XML],
			'php' => [TApplication::CONFIG_TYPE_PHP],
		];
	}

	/**
	 * loadFromFile() ignores an empty file name and a missing file.
	 */
	public function testLoadFromFileIgnoresAMissingFile()
	{
		$config = new TPageConfiguration('Home');
		$config->loadFromFile('', '');
		$config->loadFromFile($this->fixture('missing') . DIRECTORY_SEPARATOR . 'config.xml', '');
		$this->assertSame([], $config->getApplicationConfigurations());
	}

	/**
	 * loadFromFile() refuses an XML file that holds no document.
	 */
	public function testLoadFromFileRefusesAnEmptyXmlFile()
	{
		Prado::getApplication()->setConfigurationType(TApplication::CONFIG_TYPE_XML);
		$config = new TPageConfiguration('Home');
		$this->expectException(TConfigurationException::class);
		$config->loadFromFile($this->fixture('empty') . DIRECTORY_SEPARATOR . 'config.xml', '');
	}

	// -------------------------------------------------------------------------
	// Authorization
	// -------------------------------------------------------------------------

	/**
	 * Which page patterns a rule applies to, from a configuration at the base or
	 * in the 'Admin' directory, for the page 'Admin.Users'.
	 */
	public static function patternProvider(): array
	{
		return [
			'no pattern' => [null, '', true],
			'empty pattern' => ['', '', true],
			'every page' => [' * ', '', true],
			'exact' => ['Admin.Users', '', true],
			'exact in any case' => ['admin.users', '', true],
			'other page' => ['Admin.Roles', '', false],
			'wildcard' => ['Admin.*', '', true],
			'other wildcard' => ['Guest.*', '', false],
			'list' => ['Home, ,Admin.Users', '', true],
			'list without a match' => ['Home,Guest', '', false],
			'relative' => ['Users', 'Admin', true],
			'relative wildcard' => ['U*', 'Admin', true],
			'relative other page' => ['Roles', 'Admin', false],
		];
	}

	/**
	 * A PHP rule applies to the pages its pattern names.
	 * @dataProvider patternProvider
	 * @param ?string $pages the rule's page pattern.
	 * @param string $configPagePath the page path of the configuration.
	 * @param bool $applies whether the rule applies to 'Admin.Users'.
	 */
	public function testAPhpRuleAppliesToItsPages(?string $pages, string $configPagePath, bool $applies)
	{
		$rule = ['action' => 'deny', 'users' => '*'];
		if ($pages !== null) {
			$rule['pages'] = $pages;
		}
		$config = new TPageConfiguration('Admin.Users');
		$config->loadPageConfigurationFromPhp(['authorization' => [$rule]], __DIR__, $configPagePath);
		$this->assertSame($applies ? ['deny::*'] : [], $this->describe($config->getRules()));
	}

	/**
	 * An XML rule applies to the pages its pattern names.
	 * @dataProvider patternProvider
	 * @param ?string $pages the rule's page pattern.
	 * @param string $configPagePath the page path of the configuration.
	 * @param bool $applies whether the rule applies to 'Admin.Users'.
	 */
	public function testAnXmlRuleAppliesToItsPages(?string $pages, string $configPagePath, bool $applies)
	{
		$attribute = ($pages === null) ? '' : ' pages="' . $pages . '"';
		$dom = $this->xml('<configuration><authorization><deny users="*"' . $attribute . ' /></authorization></configuration>');
		$config = new TPageConfiguration('Admin.Users');
		$config->loadPageConfigurationFromXml($dom, __DIR__, $configPagePath);
		$this->assertSame($applies ? ['deny::*'] : [], $this->describe($config->getRules()));
	}

	/**
	 * A PHP rule reads each of its settings, and a later configuration's rules
	 * come before the earlier ones.
	 */
	public function testPhpRulesReadTheirSettingsAndComeBeforeEarlierRules()
	{
		$config = new TPageConfiguration('Home');
		$config->loadPageConfigurationFromPhp(['authorization' => [['action' => 'allow', 'roles' => 'admin']]], __DIR__, '');
		$config->loadPageConfigurationFromPhp(['authorization' => [
			['action' => 'deny', 'users' => 'guest', 'verb' => 'post', 'ips' => '10.0.0.1', 'priority' => 5],
			['action' => 'allow'],
		]], __DIR__, '');

		$rules = $config->getRules();
		$this->assertSame(['deny:guest:*', 'allow::*', 'allow::admin'], $this->describe($rules));
		$this->assertSame('post', $rules[0]->getVerb());
		$this->assertSame(['10.0.0.1'], $rules[0]->getIPRules());
		$this->assertEquals(5, $rules[0]->getPriority());
	}

	/**
	 * An XML rule reads each of its settings, and a later configuration's rules
	 * come before the earlier ones.
	 */
	public function testXmlRulesReadTheirSettingsAndComeBeforeEarlierRules()
	{
		$config = new TPageConfiguration('Home');
		$config->loadPageConfigurationFromXml($this->xml('<c><authorization><allow roles="admin" /></authorization></c>'), __DIR__, '');
		$config->loadPageConfigurationFromXml($this->xml('<c><authorization><deny users="guest" verb="post" ips="10.0.0.1" priority="5" /></authorization></c>'), __DIR__, '');

		$rules = $config->getRules();
		$this->assertSame(['deny:guest:*', 'allow::admin'], $this->describe($rules));
		$this->assertSame('post', $rules[0]->getVerb());
		$this->assertSame(['10.0.0.1'], $rules[0]->getIPRules());
		$this->assertEquals(5, $rules[0]->getPriority());
	}

	/**
	 * A PHP 'authorization' that is not a list of rules is ignored.
	 */
	public function testAPhpAuthorizationThatIsNotAListIsIgnored()
	{
		$config = new TPageConfiguration('Home');
		$config->loadPageConfigurationFromPhp(['authorization' => 'deny'], __DIR__, '');
		$this->assertSame([], $config->getRules());
	}

	/**
	 * An XML 'authorization' without rules adds none.
	 */
	public function testAnEmptyXmlAuthorizationAddsNoRules()
	{
		$config = new TPageConfiguration('Home');
		$config->loadPageConfigurationFromXml($this->xml('<c><authorization /></c>'), __DIR__, '');
		$this->assertSame([], $config->getRules());
	}

	// -------------------------------------------------------------------------
	// Pages
	// -------------------------------------------------------------------------

	/**
	 * Which page ids a page's properties apply to, from a configuration at the
	 * base or in the 'Admin' directory, for the page 'Admin.Users'.
	 */
	public static function pageIdProvider(): array
	{
		return [
			'exact' => ['Admin.Users', '', true],
			'exact in any case' => ['ADMIN.users', '', true],
			'other page' => ['Admin.Roles', '', false],
			'wildcard' => ['Admin.*', '', true],
			'other wildcard' => ['Guest.*', '', false],
			'relative' => ['Users', 'Admin', true],
			'relative wildcard' => ['*', 'Admin', true],
			'relative other page' => ['Roles', 'Admin', false],
		];
	}

	/**
	 * A PHP page's properties apply to the page its id names, after the
	 * properties of every page.
	 * @dataProvider pageIdProvider
	 * @param string $id the page id.
	 * @param string $configPagePath the page path of the configuration.
	 * @param bool $applies whether the properties apply to 'Admin.Users'.
	 */
	public function testAPhpPageAppliesToTheIdItNames(string $id, string $configPagePath, bool $applies)
	{
		$config = new TPageConfiguration('Admin.Users');
		$config->loadPageConfigurationFromPhp(['pages' => [
			'properties' => ['Theme' => 'Basic'],
			$id => ['properties' => ['Title' => 'Users']],
		]], __DIR__, $configPagePath);
		$this->assertSame(['Theme' => 'Basic'] + ($applies ? ['Title' => 'Users'] : []), $config->getProperties());
	}

	/**
	 * An XML page's properties apply to the page its id names, after the
	 * properties of every page.
	 * @dataProvider pageIdProvider
	 * @param string $id the page id.
	 * @param string $configPagePath the page path of the configuration.
	 * @param bool $applies whether the properties apply to 'Admin.Users'.
	 */
	public function testAnXmlPageAppliesToTheIdItNames(string $id, string $configPagePath, bool $applies)
	{
		$dom = $this->xml('<c><pages Theme="Basic"><page id="' . $id . '" Title="Users" /></pages></c>');
		$config = new TPageConfiguration('Admin.Users');
		$config->loadPageConfigurationFromXml($dom, __DIR__, $configPagePath);
		$this->assertSame(['Theme' => 'Basic'] + ($applies ? ['Title' => 'Users'] : []), $config->getProperties());
	}

	/**
	 * A PHP page without properties, and 'pages' without shared properties, add none.
	 */
	public function testAPhpPageWithoutPropertiesAddsNone()
	{
		$config = new TPageConfiguration('Home');
		$config->loadPageConfigurationFromPhp(['pages' => ['Home' => []]], __DIR__, '');
		$config->loadPageConfigurationFromPhp(['pages' => 'Home'], __DIR__, '');
		$this->assertSame([], $config->getProperties());
	}

	/**
	 * An XML page without an id is refused.
	 */
	public function testAnXmlPageWithoutAnIdIsRefused()
	{
		$config = new TPageConfiguration('Home');
		$this->expectException(TConfigurationException::class);
		$config->loadPageConfigurationFromXml($this->xml('<c><pages><page Title="None" /></pages></c>'), __DIR__, '');
	}

	// -------------------------------------------------------------------------
	// XML includes
	// -------------------------------------------------------------------------

	/**
	 * An XML include without a condition always applies, and one with a
	 * condition keeps it for the page service to evaluate.
	 */
	public function testAnXmlIncludeKeepsItsCondition()
	{
		$config = new TPageConfiguration('Home');
		$config->loadPageConfigurationFromXml($this->xml('<c><include file="Application.a" /><include file="Application.b" when="false" /></c>'), __DIR__, 'Admin');
		$this->assertSame([
			'Application.a' => ['Admin', true],
			'Application.b' => ['Admin', 'false'],
		], $config->getExternalConfigurations());
	}

	/**
	 * A file included twice in XML applies when either condition holds.
	 */
	public function testAnXmlIncludeRepeatedJoinsTheConditions()
	{
		$config = new TPageConfiguration('Home');
		$config->loadPageConfigurationFromXml($this->xml('<c><include file="Application.a" /><include file="Application.a" when="false" /></c>'), __DIR__, '');
		$this->assertSame(['Application.a' => ['', '(1) || (false)']], $config->getExternalConfigurations());
	}

	/**
	 * An XML include without a file is refused.
	 */
	public function testAnXmlIncludeWithoutAFileIsRefused()
	{
		$config = new TPageConfiguration('Home');
		$this->expectException(TConfigurationException::class);
		$config->loadPageConfigurationFromXml($this->xml('<c><include when="true" /></c>'), __DIR__, '');
	}

	/**
	 * A PHP include without a file is refused.
	 */
	public function testAPhpIncludeWithoutAFileIsRefused()
	{
		$config = new TPageConfiguration('Home');
		$this->expectException(TConfigurationException::class);
		$config->loadPageConfigurationFromPhp(['includes' => [['when' => 'true']]], __DIR__, '');
	}

	// -------------------------------------------------------------------------
	// PHP includes
	// -------------------------------------------------------------------------

	/**
	 * A PHP include without a 'when' condition always applies.
	 */
	public function testAPhpIncludeWithoutAConditionAlwaysApplies()
	{
		$config = new TPageConfiguration('Home');
		$config->loadPageConfigurationFromPhp(['includes' => [['file' => 'Application.extra']]], __DIR__, '');
		$this->assertSame(['Application.extra' => ['', true]], $config->getExternalConfigurations());
	}

	/**
	 * A PHP include keeps its 'when' condition for the page service to evaluate.
	 */
	public function testAPhpIncludeKeepsItsCondition()
	{
		$config = new TPageConfiguration('Home');
		$config->loadPageConfigurationFromPhp(['includes' => [['file' => 'Application.extra', 'when' => 'false']]], __DIR__, 'Admin');
		$this->assertSame(['Application.extra' => ['Admin', 'false']], $config->getExternalConfigurations());
	}

	/**
	 * A file included twice applies when either condition holds.
	 */
	public function testAPhpIncludeRepeatedJoinsTheConditions()
	{
		$config = new TPageConfiguration('Home');
		$config->loadPageConfigurationFromPhp(['includes' => [
			['file' => 'Application.extra'],
			['file' => 'Application.extra', 'when' => 'false'],
		]], __DIR__, '');
		[$pagePath, $condition] = $config->getExternalConfigurations()['Application.extra'];
		$this->assertSame('(1) || (false)', $condition);
		$this->assertTrue((new TComponent())->evaluateExpression($condition));
	}

	// -------------------------------------------------------------------------
	// PHP page ids
	// -------------------------------------------------------------------------

	/**
	 * A numeric PHP page key, which PHP stores as an integer, names its page.
	 */
	public function testANumericPhpPageKeyNamesItsPage()
	{
		$pages = ['pages' => [
			404 => ['properties' => ['Title' => 'Missing']],
			'Home' => ['properties' => ['Title' => 'Welcome']],
		]];
		foreach (['404' => 'Missing', 'Home' => 'Welcome'] as $pagePath => $title) {
			$config = new TPageConfiguration((string) $pagePath);
			$config->loadPageConfigurationFromPhp($pages, __DIR__, '');
			$this->assertSame(['Title' => $title], $config->getProperties());
		}
	}

	/**
	 * An empty PHP page key is refused, as an XML page without an id is.
	 */
	public function testAnEmptyPhpPageKeyIsRefused()
	{
		$config = new TPageConfiguration('Home');
		$this->expectException(TConfigurationException::class);
		$config->loadPageConfigurationFromPhp(['pages' => ['' => ['properties' => ['Title' => 'None']]]], __DIR__, '');
	}
}
