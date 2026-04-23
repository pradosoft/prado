<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

/**
 * MysqlTableExistsTest — driver-specific tests for {@see TTableGateway::getTableExists()} on MySQL.
 *
 * Skipped automatically when pdo_mysql is unavailable or the prado_unitest DB cannot be reached.
 *
 * MySQL's TMysqlTableInfo::getTableFullName() produces backtick-quoted names,
 * e.g. `upsert_test` or `schema`.`upsert_test`.
 *
 * Requires: prado_unitest MySQL database with the upsert_test table (tests/initdb_mysql.sql).
 */

use Prado\Data\Common\TDbMetaData;
use Prado\Data\DataGateway\TTableGateway;
use Prado\Data\TDbConnection;

class MysqlTableExistsTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected static ?TDbConnection $conn = null;

	private const TEMP_TABLE = 'tbl_exists_tmp';

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
		static::$conn->createCommand(
			'DROP TABLE IF EXISTS `' . self::TEMP_TABLE . '`'
		)->execute();
	}

	protected function tearDown(): void
	{
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

	public function test_getTableExists_returns_true_for_existing_table(): void
	{
		$gateway = new TTableGateway('upsert_test', static::$conn);
		$this->assertTrue($gateway->getTableExists());
	}

	public function test_getTableExists_returns_true_for_newly_created_table(): void
	{
		static::$conn->createCommand(
			'CREATE TABLE `' . self::TEMP_TABLE . '` (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY)'
		)->execute();

		$gateway = new TTableGateway(self::TEMP_TABLE, static::$conn);
		$this->assertTrue($gateway->getTableExists());
	}

	public function test_getTableExists_returns_false_after_table_is_dropped(): void
	{
		static::$conn->createCommand(
			'CREATE TABLE `' . self::TEMP_TABLE . '` (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY)'
		)->execute();

		// Construct while the table exists so the metadata lookup succeeds.
		$info = TDbMetaData::getInstance(static::$conn)->getTableInfo(self::TEMP_TABLE);
		$gateway = new TTableGateway($info, static::$conn);

		$this->assertTrue($gateway->getTableExists(), 'pre-condition: table must exist before drop');

		static::$conn->createCommand('DROP TABLE `' . self::TEMP_TABLE . '`')->execute();

		$this->assertFalse($gateway->getTableExists());
	}
}
