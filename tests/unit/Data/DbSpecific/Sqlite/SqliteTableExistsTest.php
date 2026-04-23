<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

/**
 * SqliteTableExistsTest — driver-specific tests for {@see TTableGateway::getTableExists()} on SQLite.
 *
 * Uses an in-memory SQLite database so no external server is required.
 * Skipped automatically when the pdo_sqlite extension is unavailable.
 *
 * SQLite's TSqliteTableInfo::getTableFullName() wraps the name in single-quotes,
 * e.g. 'upsert_test', which is the quoting style used by all SQLite probe queries.
 */

use Prado\Data\Common\TDbMetaData;
use Prado\Data\DataGateway\TTableGateway;
use Prado\Data\TDbConnection;

class SqliteTableExistsTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected static ?TDbConnection $conn = null;

	private const TEMP_TABLE = 'tbl_exists_tmp';

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupSqliteConnection';
	}

	protected function getDatabaseName(): ?string
	{
		return null; // in-memory
	}

	protected function getTestTables(): array
	{
		return []; // schema created below
	}

	protected function setUp(): void
	{
		if (static::$conn === null) {
			$conn = $this->setUpConnection();
			if ($conn instanceof TDbConnection) {
				$conn->createCommand(
					'CREATE TABLE upsert_test (
						id       INTEGER PRIMARY KEY AUTOINCREMENT,
						username TEXT    NOT NULL UNIQUE,
						score    INTEGER NOT NULL DEFAULT 0
					)'
				)->execute();
				static::$conn = $conn;
			}
		}
		static::$conn->createCommand(
			'DROP TABLE IF EXISTS ' . self::TEMP_TABLE
		)->execute();
	}

	public static function tearDownAfterClass(): void
	{
		if (static::$conn !== null) {
			static::$conn->Active = false;
			static::$conn = null;
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
			'CREATE TABLE ' . self::TEMP_TABLE . ' (id INTEGER PRIMARY KEY)'
		)->execute();

		$gateway = new TTableGateway(self::TEMP_TABLE, static::$conn);
		$this->assertTrue($gateway->getTableExists());
	}

	public function test_getTableExists_returns_false_after_table_is_dropped(): void
	{
		static::$conn->createCommand(
			'CREATE TABLE ' . self::TEMP_TABLE . ' (id INTEGER PRIMARY KEY)'
		)->execute();

		// Construct while the table exists so the metadata lookup succeeds.
		$info = TDbMetaData::getInstance(static::$conn)->getTableInfo(self::TEMP_TABLE);
		$gateway = new TTableGateway($info, static::$conn);

		$this->assertTrue($gateway->getTableExists(), 'pre-condition: table must exist before drop');

		static::$conn->createCommand('DROP TABLE ' . self::TEMP_TABLE)->execute();

		$this->assertFalse($gateway->getTableExists());
	}
}
