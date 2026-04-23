<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

/**
 * OracleTableExistsTest — driver-specific tests for {@see TTableGateway::getTableExists()} on Oracle.
 *
 * Skipped automatically when pdo_oci is unavailable or the Oracle service cannot be reached.
 *
 * Oracle's TOracleTableInfo::getTableFullName() produces unquoted, schema-prefixed names,
 * e.g. PRADO_UNITEST.UPSERT_TEST. Oracle stores unquoted identifiers as uppercase and
 * DDL statements implicitly commit any open transaction.
 *
 * Oracle does not support DROP TABLE IF EXISTS; cleanup uses a PHP-level try/catch instead.
 *
 * Requires: Oracle FREEPDB1 (or ORACLE_SERVICE_NAME env) with prado_unitest user/schema
 *   and the upsert_test table (tests/initdb_oracle.sql).
 */

use Prado\Data\Common\TDbMetaData;
use Prado\Data\DataGateway\TTableGateway;
use Prado\Data\TDbConnection;

class OracleTableExistsTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected static ?TDbConnection $conn = null;

	// Oracle upcases unquoted identifiers; use uppercase consistently.
	private const TEMP_TABLE        = 'tbl_exists_tmp';
	private const TEMP_TABLE_SCHEMA = 'PRADO_UNITEST.tbl_exists_tmp';

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupOciConnection';
	}

	protected function getDatabaseName(): ?string
	{
		return null; // service name comes from the DSN / ORACLE_SERVICE_NAME env
	}

	protected function getTestTables(): array
	{
		return ['upsert_test'];
	}

	protected function setUp(): void
	{
		if (static::$conn === null) {
			$conn = $this->setUpConnection();
			if ($conn instanceof TDbConnection) {
				static::$conn = $conn;
			}
		}
		$this->dropTempTableIfExists();
	}

	protected function tearDown(): void
	{
		$this->dropTempTableIfExists();
	}

	public static function tearDownAfterClass(): void
	{
		if (static::$conn !== null) {
			static::$conn->Active = false;
			static::$conn = null;
		}
	}

	private function dropTempTableIfExists(): void
	{
		if (static::$conn === null) {
			return;
		}
		try {
			// Oracle DDL auto-commits; no explicit COMMIT needed.
			static::$conn->createCommand('DROP TABLE ' . self::TEMP_TABLE)->execute();
		} catch (\Exception $e) {
			// ORA-00942: table or view does not exist — ignore.
		}
	}

	// -----------------------------------------------------------------------

	public function test_getTableExists_returns_true_for_existing_table(): void
	{
		// Oracle tests use schema-prefixed names, consistent with OracleInsertOrIgnoreTest.
		$gateway = new TTableGateway('PRADO_UNITEST.upsert_test', static::$conn);
		$this->assertTrue($gateway->getTableExists());
	}

	public function test_getTableExists_returns_true_for_newly_created_table(): void
	{
		static::$conn->createCommand(
			'CREATE TABLE ' . self::TEMP_TABLE . ' (id NUMBER NOT NULL PRIMARY KEY)'
		)->execute();

		$gateway = new TTableGateway(self::TEMP_TABLE_SCHEMA, static::$conn);
		$this->assertTrue($gateway->getTableExists());
	}

	public function test_getTableExists_returns_false_after_table_is_dropped(): void
	{
		static::$conn->createCommand(
			'CREATE TABLE ' . self::TEMP_TABLE . ' (id NUMBER NOT NULL PRIMARY KEY)'
		)->execute();

		// Construct while the table exists so the metadata lookup succeeds.
		$info = TDbMetaData::getInstance(static::$conn)->getTableInfo(self::TEMP_TABLE_SCHEMA);
		$gateway = new TTableGateway($info, static::$conn);

		$this->assertTrue($gateway->getTableExists(), 'pre-condition: table must exist before drop');

		// Oracle DDL auto-commits.
		static::$conn->createCommand('DROP TABLE ' . self::TEMP_TABLE)->execute();

		$this->assertFalse($gateway->getTableExists());
	}
}
