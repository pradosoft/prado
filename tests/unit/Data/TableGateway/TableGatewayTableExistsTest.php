<?php

require_once(__DIR__ . '/../../PradoUnit.php');

use Prado\Data\Common\TDbMetaData;
use Prado\Data\DataGateway\TTableGateway;
use Prado\Data\TDbConnection;

/**
 * TableGatewayTableExistsTest — tests for {@see TTableGateway::getTableExists()}.
 *
 * Verifies:
 *   - Returns true for a table that exists.
 *   - Returns true when the gateway was constructed from a TDbTableInfo rather than
 *     a table-name string (proving the method is not coupled to the constructor path).
 *   - Returns false for a table that has been dropped after construction. This case
 *     requires constructing the gateway via TDbTableInfo so the constructor itself does
 *     not throw; only the later getTableExists() probe sees the missing table.
 *   - SQLite in-memory variant: covers a driver where the probe SQL uses no quoting
 *     characters and table creation/deletion is trivial.
 */
class TableGatewayTableExistsTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected static ?TDbConnection $conn = null;

	/** Temporary table created and dropped during tests. */
	private const TEMP_TABLE = 'tbl_exists_probe';

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupMysqlConnection';
	}

	protected function getDatabaseName(): ?string
	{
		return 'prado_unitest';
	}

	protected function getTestTables(): array
	{
		// Only check a table we know exists; the temp table is managed per-test.
		return ['address'];
	}

	protected function setUp(): void
	{
		if (static::$conn === null) {
			$conn = $this->setUpConnection();
			if ($conn instanceof TDbConnection) {
				static::$conn = $conn;
			}
		}
		// Ensure temp table is absent at the start of each test.
		static::$conn->createCommand(
			'DROP TABLE IF EXISTS `' . self::TEMP_TABLE . '`'
		)->execute();
	}

	protected function tearDown(): void
	{
		// Always clean up the temp table, even if a test fails mid-way.
		if (static::$conn !== null) {
			static::$conn->createCommand(
				'DROP TABLE IF EXISTS `' . self::TEMP_TABLE . '`'
			)->execute();
		}
	}

	public static function tearDownAfterClass(): void
	{
		if (static::$conn !== null) {
			static::$conn->Active = false;
			static::$conn = null;
		}
	}

	// -----------------------------------------------------------------------
	// Returns true — table exists
	// -----------------------------------------------------------------------

	public function test_getTableExists_returns_true_for_existing_table(): void
	{
		$gateway = new TTableGateway('address', static::$conn);

		$this->assertTrue($gateway->getTableExists());
	}

	public function test_getTableExists_returns_true_when_constructed_from_table_info(): void
	{
		// Construction via TDbTableInfo bypasses the string-based metadata lookup;
		// getTableExists() must still correctly probe the live database.
		$info = TDbMetaData::getInstance(static::$conn)->getTableInfo('address');
		$gateway = new TTableGateway($info, static::$conn);

		$this->assertTrue($gateway->getTableExists());
	}

	public function test_getTableExists_returns_true_for_newly_created_table(): void
	{
		static::$conn->createCommand(
			'CREATE TABLE `' . self::TEMP_TABLE . '` (`id` INT NOT NULL PRIMARY KEY)'
		)->execute();

		$gateway = new TTableGateway(self::TEMP_TABLE, static::$conn);

		$this->assertTrue($gateway->getTableExists());
	}

	// -----------------------------------------------------------------------
	// Returns false — table does not exist
	// -----------------------------------------------------------------------

	public function test_getTableExists_returns_false_after_table_is_dropped(): void
	{
		// Create and introspect the temp table so we have a valid TDbTableInfo.
		static::$conn->createCommand(
			'CREATE TABLE `' . self::TEMP_TABLE . '` (`id` INT NOT NULL PRIMARY KEY)'
		)->execute();

		// Build gateway from the info object — constructor succeeds because metadata
		// was fetched while the table existed.
		$info = TDbMetaData::getInstance(static::$conn)->getTableInfo(self::TEMP_TABLE);
		$gateway = new TTableGateway($info, static::$conn);

		$this->assertTrue($gateway->getTableExists(), 'pre-condition: table must exist before drop');

		// Drop the table; the gateway still holds the stale TDbTableInfo.
		static::$conn->createCommand(
			'DROP TABLE `' . self::TEMP_TABLE . '`'
		)->execute();

		$this->assertFalse($gateway->getTableExists());
	}

	// -----------------------------------------------------------------------
	// SQLite in-memory variant — driver-agnostic coverage
	// -----------------------------------------------------------------------

	public function test_getTableExists_returns_true_on_sqlite_for_existing_table(): void
	{
		$conn = PradoUnit::setupSqliteConnection();
		if (!$conn instanceof TDbConnection) {
			$this->markTestSkipped((string) $conn);
		}
		$conn->createCommand('CREATE TABLE probe (id INTEGER PRIMARY KEY)')->execute();

		$gateway = new TTableGateway('probe', $conn);

		$this->assertTrue($gateway->getTableExists());

		$conn->Active = false;
	}

	public function test_getTableExists_returns_false_on_sqlite_after_drop(): void
	{
		$conn = PradoUnit::setupSqliteConnection();
		if (!$conn instanceof TDbConnection) {
			$this->markTestSkipped((string) $conn);
		}
		$conn->createCommand('CREATE TABLE probe (id INTEGER PRIMARY KEY)')->execute();

		$info = TDbMetaData::getInstance($conn)->getTableInfo('probe');
		$gateway = new TTableGateway($info, $conn);

		$conn->createCommand('DROP TABLE probe')->execute();

		$this->assertFalse($gateway->getTableExists());

		$conn->Active = false;
	}
}
