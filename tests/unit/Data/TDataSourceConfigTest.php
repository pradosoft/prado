<?php

/**
 * TDataSourceConfigTest
 *
 * Unit tests for {@see \Prado\Data\TDataSourceConfig}.
 *
 * Covers the order the module applies the `<database>` properties to its
 * connection in, from both an XML and a PHP application configuration.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

namespace Prado\Test\Unit\Data;

use Prado\Data\TDataSourceConfig;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TApplication;
use Prado\TModule;
use Prado\Test\Unit\Harness\Traits\TNestedPathTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Xml\TXmlDocument;

/**
 * A connection carrying a nested 'Cfg' property, to observe the order the
 * module applies the `<database>` attributes in.
 */
class TNestedPathDbConnection extends TDbConnection
{
	use TNestedPathTrait;
}

/**
 * A module that is neither a TDbConnection nor a TDataSourceConfig, so
 * findConnectionByID() refuses it.
 */
class TNotAConnectionModule extends TModule
{
}

class TDataSourceConfigTest extends \PHPUnit\Framework\TestCase
{
	private $_configurationType;

	/** @var string[] module IDs this test registered on the application. */
	private array $_registered = [];

	protected function setUp(): void
	{
		$this->_configurationType = Prado::getApplication()->getConfigurationType();
	}

	protected function tearDown(): void
	{
		Prado::getApplication()->setConfigurationType($this->_configurationType);
		if ($this->_registered) {
			// setModule() keeps a nulled key to prevent ID reuse, so drop the keys outright
			$modules = PradoUnit::getProp(Prado::getApplication(), '_modules');
			foreach ($this->_registered as $id) {
				unset($modules[$id]);
			}
			PradoUnit::setProp(Prado::getApplication(), '_modules', $modules);
			$this->_registered = [];
		}
	}

	/**
	 * Registers a module for the duration of the test, writing the module map
	 * directly. A TDbConnection is not an IModule, so setModule() refuses it,
	 * while findConnectionByID() still accepts one found under an ID.
	 * @param string $id the module ID.
	 * @param object $module the module to register.
	 */
	private function registerModule(string $id, $module)
	{
		$app = Prado::getApplication();
		$modules = PradoUnit::getProp($app, '_modules');
		$modules[$id] = $module;
		PradoUnit::setProp($app, '_modules', $modules);
		$this->_registered[] = $id;
	}

	/**
	 * Builds the module's connection from a PHP configuration.
	 * @param array $database the `database` properties to apply.
	 */
	private function buildFromPhp(array $database): TNestedPathDbConnection
	{
		Prado::getApplication()->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		$module = new TDataSourceConfig();
		$module->setConnectionClass(TNestedPathDbConnection::class);
		$module->init(['database' => $database]);
		return $module->getDbConnection();
	}

	/**
	 * Builds the module's connection from an XML configuration.
	 * @param string $attributes the `<database>` attributes as written.
	 */
	private function buildFromXml(string $attributes): TNestedPathDbConnection
	{
		Prado::getApplication()->setConfigurationType(TApplication::CONFIG_TYPE_XML);
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString('<module><database ' . $attributes . '/></module>');
		$module = new TDataSourceConfig();
		$module->setConnectionClass(TNestedPathDbConnection::class);
		$module->init($config);
		return $module->getDbConnection();
	}

	/**
	 * A PHP configuration applies a parent path before any path nested beneath
	 * it, so a nested value survives whatever order it declares them in.
	 */
	public function testPhpConfigurationAppliesParentPathBeforeNestedPath()
	{
		foreach ([
			['Cfg.Size' => 'child', 'Cfg' => 'value'],
			['Cfg' => 'value', 'Cfg.Size' => 'child'],
		] as $database) {
			$connection = $this->buildFromPhp($database);
			$this->assertEquals('child', $connection->getCfg()->getSize(), 'declared: ' . implode(', ', array_keys($database)));
		}
	}

	/**
	 * An XML configuration supplies a TAttributeCollection rather than an array,
	 * and orders it the same way.
	 */
	public function testXmlConfigurationAppliesParentPathBeforeNestedPath()
	{
		foreach ([
			'Cfg.Size="child" Cfg="value"',
			'Cfg="value" Cfg.Size="child"',
		] as $attributes) {
			$connection = $this->buildFromXml($attributes);
			$this->assertEquals('child', $connection->getCfg()->getSize(), "attributes: {$attributes}");
		}
	}

	// -------------------------------------------------------------------------
	// init() — configuration branches
	// -------------------------------------------------------------------------

	/**
	 * A PHP configuration without a 'database' key leaves the connection alone.
	 */
	public function testPhpConfigurationWithoutADatabaseKeyIsAccepted()
	{
		Prado::getApplication()->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		$module = new TDataSourceConfig();
		$module->setConnectionClass(TNestedPathDbConnection::class);
		$module->init(['other' => []]);
		$this->assertEquals('unset', $module->getDbConnection()->getCfg()->getSize());
	}

	/**
	 * A 'database' key that is not an array is ignored rather than applied.
	 */
	public function testPhpConfigurationWithANonArrayDatabaseKeyIsIgnored()
	{
		Prado::getApplication()->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		$module = new TDataSourceConfig();
		$module->setConnectionClass(TNestedPathDbConnection::class);
		$module->init(['database' => 'not-an-array']);
		$this->assertEquals('unset', $module->getDbConnection()->getCfg()->getSize());
	}

	/**
	 * An XML configuration without a <database> element leaves the connection alone.
	 */
	public function testXmlConfigurationWithoutADatabaseElementIsAccepted()
	{
		Prado::getApplication()->setConfigurationType(TApplication::CONFIG_TYPE_XML);
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString('<module/>');
		$module = new TDataSourceConfig();
		$module->setConnectionClass(TNestedPathDbConnection::class);
		$module->init($config);
		$this->assertEquals('unset', $module->getDbConnection()->getCfg()->getSize());
	}

	/**
	 * A null configuration is accepted; the XML branch is skipped entirely.
	 */
	public function testANullConfigurationIsAccepted()
	{
		Prado::getApplication()->setConfigurationType(TApplication::CONFIG_TYPE_XML);
		$module = new TDataSourceConfig();
		$module->setConnectionClass(TNestedPathDbConnection::class);
		$module->init(null);
		$this->assertEquals('unset', $module->getDbConnection()->getCfg()->getSize());
	}

	// -------------------------------------------------------------------------
	// ConnectionClass
	// -------------------------------------------------------------------------

	/**
	 * The connection class defaults to TDbConnection.
	 */
	public function testConnectionClassDefaultsToTDbConnection()
	{
		$module = new TDataSourceConfig();
		$this->assertEquals(TDbConnection::class, $module->getConnectionClass());
	}

	/**
	 * getDbConnection() builds a connection of the configured class.
	 */
	public function testGetDbConnectionBuildsTheConfiguredClass()
	{
		$module = new TDataSourceConfig();
		$module->setConnectionClass(TNestedPathDbConnection::class);
		$this->assertInstanceOf(TNestedPathDbConnection::class, $module->getDbConnection());
	}

	/**
	 * getDbConnection() builds the connection once and returns the same instance.
	 */
	public function testGetDbConnectionIsBuiltOnce()
	{
		$module = new TDataSourceConfig();
		$this->assertSame($module->getDbConnection(), $module->getDbConnection());
	}

	/**
	 * The connection class cannot change once the connection is established.
	 */
	public function testSetConnectionClassAfterTheConnectionExistsIsRefused()
	{
		$module = new TDataSourceConfig();
		$module->getDbConnection();
		$this->expectException(TConfigurationException::class);
		$module->setConnectionClass(TNestedPathDbConnection::class);
	}

	// -------------------------------------------------------------------------
	// Database alias
	// -------------------------------------------------------------------------

	/**
	 * getDatabase() is an alias for getDbConnection().
	 */
	public function testGetDatabaseIsAnAliasForGetDbConnection()
	{
		$module = new TDataSourceConfig();
		$this->assertSame($module->getDbConnection(), $module->getDatabase());
	}

	// -------------------------------------------------------------------------
	// findConnectionByID() — ConnectionID resolution
	// -------------------------------------------------------------------------

	/**
	 * A ConnectionID naming a TDbConnection module resolves to that connection.
	 */
	public function testConnectionIDResolvesATDbConnectionModule()
	{
		$this->registerModule('nestedconn', $connection = new TNestedPathDbConnection());
		$module = new TDataSourceConfig();
		$module->setConnectionID('nestedconn');
		$this->assertSame($connection, $module->getDbConnection());
	}

	/**
	 * A ConnectionID naming another TDataSourceConfig resolves to that module's
	 * connection.
	 */
	public function testConnectionIDResolvesATDataSourceConfigModule()
	{
		$source = new TDataSourceConfig();
		$source->setConnectionClass(TNestedPathDbConnection::class);
		$this->registerModule('nestedsource', $source);

		$module = new TDataSourceConfig();
		$module->setConnectionID('nestedsource');
		$this->assertSame($source->getDbConnection(), $module->getDbConnection());
	}

	/**
	 * A ConnectionID naming a module of any other type is refused.
	 */
	public function testConnectionIDNamingAnUnrelatedModuleIsRefused()
	{
		$this->registerModule('notaconn', new TNotAConnectionModule());
		$module = new TDataSourceConfig();
		$module->setConnectionID('notaconn');
		$this->expectException(TConfigurationException::class);
		$module->getDbConnection();
	}

	/**
	 * A ConnectionID naming no registered module is refused the same way.
	 */
	public function testConnectionIDNamingAnUnknownModuleIsRefused()
	{
		$module = new TDataSourceConfig();
		$module->setConnectionID('nosuchmodule');
		$this->expectException(TConfigurationException::class);
		$module->getDbConnection();
	}
}
