<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

/**
 * SqliteInsertOrIgnoreTest — comprehensive tests for SQLite insertOrIgnore behaviour.
 *
 * Tests both SQL generation and live execution using an in-memory SQLite database.
 * Skipped automatically when the pdo_sqlite extension is unavailable.
 *
 * Table under test (created in-memory):
 *   upsert_test(id INTEGER PK AUTOINCREMENT, username TEXT UNIQUE NOT NULL, score INTEGER DEFAULT 0)
 */

use Prado\Data\Common\Sqlite\TSqliteMetaData;
use Prado\Data\Common\TDbCommandBuilder;
use Prado\Data\DataGateway\TDataGatewayEventParameter;
use Prado\Data\DataGateway\TDataGatewayResultEventParameter;
use Prado\Data\DataGateway\TTableGateway;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TDbException;

class SqliteInsertOrIgnoreTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected static ?TDbConnection $conn = null;
	protected static ?TTableGateway $gateway = null;

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupSqliteConnection';
	}

	protected function getDatabaseName(): ?string
	{
		return null;
	}

	protected function getTestTables(): array
	{
		return [];
	}

	protected function setUp(): void
	{
		if (static::$conn === null) {
			$conn = $this->setUpConnection();
			if ($conn instanceof TDbConnection) {
				$conn->createCommand('
					CREATE TABLE upsert_test (
						id       INTEGER PRIMARY KEY AUTOINCREMENT,
						username TEXT    NOT NULL,
						score    INTEGER NOT NULL DEFAULT 0,
						UNIQUE (username)
					)
				')->execute();
				static::$conn = $conn;
				static::$gateway = new TTableGateway('upsert_test', $conn);
			}
		}
		static::$conn->createCommand('DELETE FROM upsert_test')->execute();
	}

	public static function tearDownAfterClass(): void
	{
		if (static::$conn !== null) {
			static::$conn->Active = false;
			static::$conn = null;
			static::$gateway = null;
		}
	}

	// -----------------------------------------------------------------------
	// SQL generation
	// -----------------------------------------------------------------------

	public function test_sql_uses_insert_or_ignore_keyword(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$gw->insertOrIgnore(['username' => 'test', 'score' => 1]);
		$this->assertNotNull($capturedSql);
		$this->assertStringContainsString('INSERT OR IGNORE INTO', $capturedSql);
		$this->assertStringContainsString('"username"', $capturedSql);
		$this->assertStringContainsString('"score"', $capturedSql);
		$this->assertStringContainsString(':username', $capturedSql);
		$this->assertStringContainsString(':score', $capturedSql);
		$this->assertStringNotContainsString('ON CONFLICT', $capturedSql);
	}

	public function test_sql_omits_id_when_not_in_data(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$gw->insertOrIgnore(['username' => 'test', 'score' => 1]);
		$this->assertStringNotContainsString('"id"', $capturedSql);
	}

	public function test_sql_includes_id_when_provided(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$gw->insertOrIgnore(['id' => 5, 'username' => 'test', 'score' => 1]);
		$this->assertStringContainsString('"id"', $capturedSql);
		$this->assertStringContainsString(':id', $capturedSql);
	}

	// -----------------------------------------------------------------------
	// Insert new row
	// -----------------------------------------------------------------------

	public function test_new_row_is_inserted_into_table(): void
	{
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 10]);

		$count = (int) self::$conn->createCommand('SELECT COUNT(*) FROM upsert_test')->queryScalar();
		$this->assertEquals(1, $count);
	}

	public function test_new_row_values_are_stored_correctly(): void
	{
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 42]);

		$row = self::$gateway->find('username = ?', 'alice');
		$this->assertIsArray($row);
		$this->assertEquals('alice', $row['username']);
		$this->assertEquals(42, (int) $row['score']);
	}

	public function test_new_row_returns_integer_last_insert_id(): void
	{
		$result = self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 10]);
		$this->assertNotFalse($result);
		$this->assertGreaterThan(0, (int) $result);
	}

	public function test_successive_new_rows_return_incrementing_ids(): void
	{
		$id1 = (int) self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 1]);
		$id2 = (int) self::$gateway->insertOrIgnore(['username' => 'bob',   'score' => 2]);
		$this->assertGreaterThan($id1, $id2);
	}

	public function test_new_row_with_default_score_omitted_uses_zero(): void
	{
		// SQLite fills DEFAULT 0 when score is omitted from the data
		self::$conn->createCommand("INSERT OR IGNORE INTO upsert_test (username) VALUES ('alice')")->execute();
		$row = self::$gateway->find('username = ?', 'alice');
		$this->assertEquals(0, (int) $row['score']);
	}

	// -----------------------------------------------------------------------
	// Duplicate silently ignored
	// -----------------------------------------------------------------------

	public function test_duplicate_username_returns_false(): void
	{
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 10]);
		$result = self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 99]);
		$this->assertFalse($result);
	}

	public function test_duplicate_does_not_create_additional_rows(): void
	{
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 10]);
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 99]);

		$count = (int) self::$conn->createCommand('SELECT COUNT(*) FROM upsert_test')->queryScalar();
		$this->assertEquals(1, $count);
	}

	public function test_existing_row_not_modified_after_ignored_insert(): void
	{
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 10]);
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 99]);

		$row = self::$gateway->find('username = ?', 'alice');
		$this->assertEquals(10, (int) $row['score']);
	}

	public function test_duplicate_on_explicit_id_returns_false(): void
	{
		self::$gateway->insertOrIgnore(['id' => 1, 'username' => 'alice', 'score' => 10]);
		$result = self::$gateway->insertOrIgnore(['id' => 1, 'username' => 'bob', 'score' => 20]);
		$this->assertFalse($result);
	}

	// -----------------------------------------------------------------------
	// Mixed: some conflict, some new
	// -----------------------------------------------------------------------

	public function test_only_conflicting_row_is_ignored_others_inserted(): void
	{
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 10]);
		$res2 = self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 99]); // conflict
		$res3 = self::$gateway->insertOrIgnore(['username' => 'bob',   'score' => 20]); // new

		$this->assertFalse($res2);
		$this->assertGreaterThan(0, (int) $res3);
		$this->assertEquals(2, (int) self::$conn->createCommand('SELECT COUNT(*) FROM upsert_test')->queryScalar());
	}

	public function test_non_conflicting_row_after_conflict_has_correct_values(): void
	{
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 10]);
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 99]);
		self::$gateway->insertOrIgnore(['username' => 'bob',   'score' => 55]);

		$alice = self::$gateway->find('username = ?', 'alice');
		$bob   = self::$gateway->find('username = ?', 'bob');

		$this->assertEquals(10, (int) $alice['score'], 'alice score unchanged');
		$this->assertEquals(55, (int) $bob['score'],   'bob score stored correctly');
	}

	// -----------------------------------------------------------------------
	// Events
	// -----------------------------------------------------------------------

	public function test_oncreatecommand_event_is_raised_on_insert(): void
	{
		$fired = false;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$fired): void {
			$this->assertInstanceOf(TDataGatewayEventParameter::class, $param);
			$fired = true;
		};

		$gw->insertOrIgnore(['username' => 'alice', 'score' => 1]);
		$this->assertTrue($fired, 'OnCreateCommand was not raised');
	}

	public function test_onexecutecommand_event_is_raised_with_result(): void
	{
		$captured = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnExecuteCommand[] = function ($sender, $param) use (&$captured): void {
			$this->assertInstanceOf(TDataGatewayResultEventParameter::class, $param);
			$captured = $param->getResult();
		};

		$gw->insertOrIgnore(['username' => 'alice', 'score' => 1]);
		// execute() returns rows affected; 1 for a fresh insert
		$this->assertEquals(1, $captured);
	}

	public function test_onexecutecommand_can_override_result_to_false(): void
	{
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnExecuteCommand[] = function ($sender, $param): void {
			// Force 0 rows affected → insertOrIgnore returns false
			$param->setResult(0);
		};

		$result = $gw->insertOrIgnore(['username' => 'alice', 'score' => 1]);
		$this->assertFalse($result);
	}

	public function test_oncreatecommand_event_is_raised_on_conflict(): void
	{
		$callCount = 0;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$callCount): void {
			$callCount++;
		};

		$gw->insertOrIgnore(['username' => 'alice', 'score' => 10]);
		$gw->insertOrIgnore(['username' => 'alice', 'score' => 99]);
		// Event fires once per call regardless of whether conflict occurs
		$this->assertEquals(2, $callCount);
	}

	// -----------------------------------------------------------------------
	// Base class throws TDbException
	// -----------------------------------------------------------------------

	public function test_base_builder_throws_for_insertOrIgnore(): void
	{
		$meta      = new TSqliteMetaData(self::$conn);
		$tableInfo = $meta->getTableInfo('upsert_test');
		$base      = new TDbCommandBuilder(self::$conn, $tableInfo);

		$this->expectException(TDbException::class);
		$base->createInsertOrIgnoreCommand(['username' => 'x', 'score' => 1]);
	}
}
