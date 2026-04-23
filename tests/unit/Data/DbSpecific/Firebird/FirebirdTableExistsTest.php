<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

/**
 * FirebirdTableExistsTest — driver-specific tests for {@see TTableGateway::getTableExists()} on Firebird.
 *
 * Skipped automatically when pdo_firebird is unavailable or the Firebird server cannot be reached.
 *
 * Firebird's TFirebirdTableInfo::getTableFullName() produces double-quoted, uppercased names,
 * e.g. "UPSERT_TEST". Firebird stores unquoted identifiers as uppercase.
 *
 * Firebird does not support DROP TABLE IF EXISTS; cleanup uses a PHP-level try/catch instead.
 * DDL in Firebird is transactional but PDO operates in auto-commit mode by default, so
 * CREATE TABLE / DROP TABLE do not require an explicit commit.
 *
 * Requires: prado_unitest.fdb on a Firebird 4 server with the upsert_test table
 *   (tests/initdb_firebird.sql). Override the path via the FIREBIRD_DB_PATH environment variable.
 */

use Prado\Data\Common\TDbMetaData;
use Prado\Data\DataGateway\TTableGateway;
use Prado\Data\TDbConnection;

class FirebirdTableExistsTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected static ?TDbConnection $conn = null;

	private const TEMP_TABLE = 'tbl_exists_tmp';

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupFirebirdConnection';
	}

	protected function getDatabaseName(): ?string
	{
		return null; // path comes from the DSN / FIREBIRD_DB_PATH env
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
			// Table did not exist — ignore.
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
		// Firebird: DEFAULT must precede NOT NULL.
		static::$conn->createCommand(
			'CREATE TABLE ' . self::TEMP_TABLE . ' (id INTEGER DEFAULT 0 NOT NULL PRIMARY KEY)'
		)->execute();

		$gateway = new TTableGateway(self::TEMP_TABLE, static::$conn);
		$this->assertTrue($gateway->getTableExists());
	}

	public function test_getTableExists_returns_false_after_table_is_dropped(): void
	{
		static::$conn->createCommand(
			'CREATE TABLE ' . self::TEMP_TABLE . ' (id INTEGER DEFAULT 0 NOT NULL PRIMARY KEY)'
		)->execute();

		// Construct while the table exists so the metadata lookup succeeds.
		$info = TDbMetaData::getInstance(static::$conn)->getTableInfo(self::TEMP_TABLE);
		$gateway = new TTableGateway($info, static::$conn);

		$this->assertTrue($gateway->getTableExists(), 'pre-condition: table must exist before drop');

		static::$conn->createCommand('DROP TABLE ' . self::TEMP_TABLE)->execute();

		$this->assertFalse($gateway->getTableExists());
	}
}
