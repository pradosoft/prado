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

	// -------------------------------------------------------------------------
	// Files
	// -------------------------------------------------------------------------

	public static function configurationTypeProvider(): array
	{
		return [
			'xml' => [TApplication::CONFIG_TYPE_XML],
			'php' => [TApplication::CONFIG_TYPE_PHP],
		];
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

	// -------------------------------------------------------------------------
	// XML includes
	// -------------------------------------------------------------------------

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
