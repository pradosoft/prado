<?php

require_once(__DIR__ . '/../PradoUnit.php');

use Prado\Data\TDbConnection;
use Prado\Data\TDbPropertiesTrait;
use Prado\Data\DataGateway\TTableGateway;
use Prado\Exceptions\TConfigurationException;
use Prado\TApplication;
use Prado\TComponent;

/**
 * Minimal concrete class using TDbPropertiesTrait, activation type = false (default).
 */
class TraitTestBaseClass extends TComponent
{
	use TDbPropertiesTrait;
}

/**
 * Class that uses activation type null (never auto-activate).
 */
class TraitTestNullActivationClass extends TComponent
{
	use TDbPropertiesTrait;

	protected function getDbConnectionActivationType(): ?bool
	{
		return null;
	}
}

/**
 * Class that uses activation type true (always activate on get).
 */
class TraitTestAlwaysActivateClass extends TComponent
{
	use TDbPropertiesTrait;

	protected function getDbConnectionActivationType(): ?bool
	{
		return true;
	}
}

/**
 * Class that provides a SQLite database name.
 */
class TraitTestSqliteClass extends TComponent
{
	use TDbPropertiesTrait;

	protected function getSqliteDatabaseName(): ?string
	{
		return 'trait_test.db';
	}
}

/**
 * Class that provides a custom DB connection.
 */
class TraitTestCustomConnectionClass extends TComponent
{
	use TDbPropertiesTrait;

	private TDbConnection $_customConn;

	public function setCustomConn(TDbConnection $conn): void
	{
		$this->_customConn = $conn;
	}

	protected function getCustomDbConnection(): ?TDbConnection
	{
		return $this->_customConn ?? null;
	}
}

class TDbPropertiesTraitTest extends PHPUnit\Framework\TestCase
{
	private static string $dbFile;
	private static ?TDbConnection $sharedConn = null;

	public static function setUpBeforeClass(): void
	{
		static::$dbFile = __DIR__ . '/db/trait_test.db';
		// Ensure test application is running
		if (\Prado\Prado::getApplication() === null) {
			new TApplication(__DIR__, false, TApplication::CONFIG_TYPE_PHP);
		}
		// Create a shared SQLite connection with tables for gateway tests
		static::$sharedConn = new TDbConnection('sqlite:' . static::$dbFile);
		static::$sharedConn->setActive(true);
		foreach (['trait_tbl_a', 'trait_tbl_b', 'trait_tbl_x', 'trait_tbl_y', 'trait_test_table'] as $tbl) {
			static::$sharedConn->createCommand(
				"CREATE TABLE IF NOT EXISTS $tbl (id INTEGER PRIMARY KEY, val TEXT)"
			)->execute();
		}
	}

	public static function tearDownAfterClass(): void
	{
		if (static::$sharedConn !== null) {
			static::$sharedConn->setActive(false);
			static::$sharedConn = null;
		}
	}

	private function makeSqliteConnection(): TDbConnection
	{
		// Return a fresh inactive connection to the shared SQLite file
		return new TDbConnection('sqlite:' . static::$dbFile);
	}

	private function makeActiveSharedConnection(): TDbConnection
	{
		// Return the already-active shared connection
		return static::$sharedConn;
	}

	// -------  ConnectionID  -------

	public function test_connection_id_defaults_to_empty_string()
	{
		$obj = new TraitTestBaseClass();
		$this->assertSame('', $obj->getConnectionID());
	}

	public function test_set_connection_id()
	{
		$obj = new TraitTestBaseClass();
		$obj->setConnectionID('myDB');
		$this->assertSame('myDB', $obj->getConnectionID());
	}

	// -------  HasDbConnection  -------

	public function test_has_db_connection_initially_false()
	{
		$obj = new TraitTestBaseClass();
		$this->assertFalse($obj->getHasDbConnection());
	}

	// -------  Custom connection  -------

	public function test_custom_connection_used_when_connection_id_empty()
	{
		$conn = $this->makeSqliteConnection();
		$obj = new TraitTestCustomConnectionClass();
		$obj->setCustomConn($conn);

		$retrieved = $obj->getDbConnection();
		$this->assertSame($conn, $retrieved);
	}

	public function test_has_db_connection_true_after_get()
	{
		$conn = $this->makeSqliteConnection();
		$obj = new TraitTestCustomConnectionClass();
		$obj->setCustomConn($conn);
		$obj->getDbConnection();
		$this->assertTrue($obj->getHasDbConnection());
	}

	// -------  deactivateDbConnection  -------

	public function test_deactivate_without_connection_is_no_op()
	{
		$obj = new TraitTestBaseClass();
		$result = $obj->deactivateDbConnection();
		$this->assertInstanceOf(TraitTestBaseClass::class, $result);
	}

	public function test_deactivate_clears_connection_when_flag_true()
	{
		$conn = $this->makeSqliteConnection();
		$obj = new TraitTestCustomConnectionClass();
		$obj->setCustomConn($conn);
		$obj->getDbConnection();

		$this->assertTrue($obj->getHasDbConnection());
		$obj->deactivateDbConnection(true);
		$this->assertFalse($obj->getHasDbConnection());
	}

	public function test_deactivate_returns_static()
	{
		$obj = new TraitTestCustomConnectionClass();
		$result = $obj->deactivateDbConnection();
		$this->assertSame($obj, $result);
	}

	// -------  Activation types  -------

	public function test_activation_type_false_activates_on_first_get()
	{
		$conn = $this->makeSqliteConnection();
		$obj = new TraitTestCustomConnectionClass();
		$obj->setCustomConn($conn);

		$retrieved = $obj->getDbConnection();
		$this->assertTrue($retrieved->getActive());
	}

	public function test_activation_type_null_does_not_auto_activate()
	{
		$obj = new class extends TraitTestCustomConnectionClass {
			protected function getDbConnectionActivationType(): ?bool
			{
				return null;
			}
		};

		$conn = $this->makeSqliteConnection();
		$obj->setCustomConn($conn);

		$retrieved = $obj->getDbConnection();
		// null activation: setActive(true) is never called automatically
		$this->assertFalse($retrieved->getActive());
	}

	// -------  getTableGateway  -------

	public function test_get_table_gateway_returns_table_gateway()
	{
		$obj = new TraitTestCustomConnectionClass();
		$obj->setCustomConn($this->makeActiveSharedConnection());

		$gateway = $obj->getTableGateway('trait_test_table');
		$this->assertInstanceOf(TTableGateway::class, $gateway);
	}

	public function test_get_table_gateway_caches_by_table_name()
	{
		$obj = new TraitTestCustomConnectionClass();
		$obj->setCustomConn($this->makeActiveSharedConnection());

		$gateway1 = $obj->getTableGateway('trait_tbl_a');
		$gateway2 = $obj->getTableGateway('trait_tbl_a');
		$this->assertSame($gateway1, $gateway2);
	}

	public function test_get_table_gateway_no_cache_creates_new_instance()
	{
		$obj = new TraitTestCustomConnectionClass();
		$obj->setCustomConn($this->makeActiveSharedConnection());

		$gateway1 = $obj->getTableGateway('trait_tbl_b', false);
		$gateway2 = $obj->getTableGateway('trait_tbl_b', false);
		$this->assertNotSame($gateway1, $gateway2);
	}

	public function test_get_table_gateway_different_tables_different_instances()
	{
		$obj = new TraitTestCustomConnectionClass();
		$obj->setCustomConn($this->makeActiveSharedConnection());

		$gatewayA = $obj->getTableGateway('trait_tbl_x');
		$gatewayB = $obj->getTableGateway('trait_tbl_y');
		$this->assertNotSame($gatewayA, $gatewayB);
	}

	// -------  Exception when no connection configured  -------

	public function test_no_connection_throws_when_no_sqlite_and_no_custom()
	{
		$obj = new TraitTestBaseClass();
		$this->expectException(TConfigurationException::class);
		$obj->getDbConnection();
	}

	// -------  SQLite fallback  -------

	public function test_sqlite_connection_created_from_runtime_path()
	{
		$obj = new TraitTestSqliteClass();
		$conn = $obj->getDbConnection();
		$this->assertInstanceOf(TDbConnection::class, $conn);
		$this->assertTrue($conn->getActive());
	}
}
