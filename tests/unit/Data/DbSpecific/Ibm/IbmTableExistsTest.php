<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

/**
 * IbmTableExistsTest — driver-specific tests for {@see TTableGateway::getTableExists()} on IBM DB2.
 *
 * Skipped automatically when pdo_ibm is unavailable or the pradount DB cannot be reached.
 *
 * DB2's TIbmTableInfo::getTableFullName() produces double-quoted, schema-qualified names,
 * e.g. "DB2INST1"."UPSERT_TEST". DB2 stores unquoted identifiers as uppercase.
 *
 * DB2 does not support DROP TABLE IF EXISTS; cleanup uses a PHP-level try/catch instead.
 *
 * Requires: IBM DB2 pradount database with the upsert_test table (tests/initdb_ibm.sql).
 */

use Prado\Data\Common\TDbMetaData;
use Prado\Data\DataGateway\TTableGateway;
use Prado\Data\TDbConnection;

class IbmTableExistsTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected static ?TDbConnection $conn = null;

	private const TEMP_TABLE = 'tbl_exists_tmp';

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupIbmConnection';
	}

	protected function getDatabaseName(): ?string
	{
		return null; // DB2 database name is passed via the DSN
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
			static::$conn->createCommand('DROP TABLE ' . self::TEMP_TABLE)->execute();
		} catch (\Exception $e) {
			// SQLSTATE 42704 — object not found; table did not exist, ignore.
		}
	}

	// -----------------------------------------------------------------------

	public function test_getTableExists_returns_true_for_existing_table(): void
	{
		$gateway = new TTableGateway('upsert_test', static::$conn);
		$this->assertTrue($gateway->getTableExists());
	}

	public function test_getTableExists_returns_true_for_newly_created_table(): void
	{
		static::$conn->createCommand(
			'CREATE TABLE ' . self::TEMP_TABLE . ' (id INTEGER NOT NULL GENERATED ALWAYS AS IDENTITY PRIMARY KEY)'
		)->execute();

		$gateway = new TTableGateway(self::TEMP_TABLE, static::$conn);
		$this->assertTrue($gateway->getTableExists());
	}

	public function test_getTableExists_returns_false_after_table_is_dropped(): void
	{
		static::$conn->createCommand(
			'CREATE TABLE ' . self::TEMP_TABLE . ' (id INTEGER NOT NULL GENERATED ALWAYS AS IDENTITY PRIMARY KEY)'
		)->execute();

		// Construct while the table exists so the metadata lookup succeeds.
		$info = TDbMetaData::getInstance(static::$conn)->getTableInfo(self::TEMP_TABLE);
		$gateway = new TTableGateway($info, static::$conn);

		$this->assertTrue($gateway->getTableExists(), 'pre-condition: table must exist before drop');

		static::$conn->createCommand('DROP TABLE ' . self::TEMP_TABLE)->execute();

		$this->assertFalse($gateway->getTableExists());
	}
}
