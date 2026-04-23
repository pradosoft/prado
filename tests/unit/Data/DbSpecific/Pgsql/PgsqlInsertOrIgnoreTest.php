<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

/**
 * PgsqlInsertOrIgnoreTest — comprehensive tests for PostgreSQL insertOrIgnore behaviour.
 *
 * PostgreSQL uses INSERT ... ON CONFLICT DO NOTHING.
 * Skipped automatically when pdo_pgsql is unavailable or the DB cannot be reached.
 *
 * Requires: prado_unitest database with upsert_test table (see tests/initdb_pgsql.sql).
 *   upsert_test(id SERIAL PK, username VARCHAR(100) UNIQUE NOT NULL, score INT DEFAULT 0)
 */

use Prado\Data\Common\TDbCommandBuilder;
use Prado\Data\DataGateway\TDataGatewayEventParameter;
use Prado\Data\DataGateway\TDataGatewayResultEventParameter;
use Prado\Data\DataGateway\TTableGateway;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TDbException;

class PgsqlInsertOrIgnoreTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected static ?TDbConnection $conn = null;
	protected static ?TTableGateway $gateway = null;

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupPgsqlConnection';
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

	public function test_sql_uses_on_conflict_do_nothing(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$gw->insertOrIgnore(['username' => 'test', 'score' => 1]);
		$this->assertNotNull($capturedSql);
		$this->assertStringContainsString('INSERT INTO', $capturedSql);
		$this->assertStringContainsString('ON CONFLICT DO NOTHING', $capturedSql);
		$this->assertStringContainsString('"username"', $capturedSql);
		$this->assertStringContainsString('"score"', $capturedSql);
		$this->assertStringContainsString(':username', $capturedSql);
		$this->assertStringContainsString(':score', $capturedSql);
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

	// -----------------------------------------------------------------------
	// Insert new row
	// -----------------------------------------------------------------------

	public function test_new_row_is_inserted(): void
	{
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 10]);

		$row = self::$gateway->find('username = ?', 'alice');
		$this->assertIsArray($row);
		$this->assertEquals('alice', $row['username']);
		$this->assertEquals(10, (int) $row['score']);
	}

	public function test_new_row_returns_integer_last_insert_id(): void
	{
		$result = self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 10]);
		$this->assertNotFalse($result);
		$this->assertGreaterThan(0, (int) $result);
	}

	public function test_successive_inserts_return_incrementing_ids(): void
	{
		$id1 = (int) self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 1]);
		$id2 = (int) self::$gateway->insertOrIgnore(['username' => 'bob',   'score' => 2]);
		$this->assertGreaterThan($id1, $id2);
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

	public function test_existing_row_unchanged_after_ignored_insert(): void
	{
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 10]);
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 99]);

		$row = self::$gateway->find('username = ?', 'alice');
		$this->assertEquals(10, (int) $row['score']);
	}

	public function test_duplicate_on_serial_pk_returns_false(): void
	{
		// Insert with explicit id, then insert same id again
		$id = (int) self::$gateway->insert(['username' => 'alice', 'score' => 10]);
		$result = self::$gateway->insertOrIgnore(['id' => $id, 'username' => 'bob', 'score' => 20]);
		$this->assertFalse($result);
		// Original row still there
		$row = self::$gateway->find('username = ?', 'alice');
		$this->assertIsArray($row);
	}

	// -----------------------------------------------------------------------
	// Mixed inserts
	// -----------------------------------------------------------------------

	public function test_only_conflicting_row_ignored(): void
	{
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 10]);
		$res2 = self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 99]);
		$res3 = self::$gateway->insertOrIgnore(['username' => 'bob',   'score' => 20]);

		$this->assertFalse($res2);
		$this->assertGreaterThan(0, (int) $res3);
		$this->assertEquals(
			2,
			(int) self::$conn->createCommand('SELECT COUNT(*) FROM upsert_test')->queryScalar()
		);
	}

	public function test_correct_values_after_mixed_inserts(): void
	{
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 10]);
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 99]);
		self::$gateway->insertOrIgnore(['username' => 'bob',   'score' => 55]);

		$this->assertEquals(10, (int) self::$gateway->find('username = ?', 'alice')['score']);
		$this->assertEquals(55, (int) self::$gateway->find('username = ?', 'bob')['score']);
	}

	// -----------------------------------------------------------------------
	// Events
	// -----------------------------------------------------------------------

	public function test_oncreatecommand_event_is_raised(): void
	{
		$fired = false;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$fired): void {
			$this->assertInstanceOf(TDataGatewayEventParameter::class, $param);
			$fired = true;
		};

		$gw->insertOrIgnore(['username' => 'alice', 'score' => 1]);
		$this->assertTrue($fired);
	}

	public function test_onexecutecommand_event_is_raised(): void
	{
		$captured = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnExecuteCommand[] = function ($sender, $param) use (&$captured): void {
			$this->assertInstanceOf(TDataGatewayResultEventParameter::class, $param);
			$captured = $param->getResult();
		};

		$gw->insertOrIgnore(['username' => 'alice', 'score' => 1]);
		$this->assertEquals(1, $captured);
	}

	public function test_onexecutecommand_result_is_zero_on_pgsql_conflict(): void
	{
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 10]);

		$captured = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnExecuteCommand[] = function ($sender, $param) use (&$captured): void {
			$captured = $param->getResult();
		};
		$gw->insertOrIgnore(['username' => 'alice', 'score' => 99]);
		// ON CONFLICT DO NOTHING returns 0 affected rows
		$this->assertEquals(0, $captured);
	}

	public function test_onexecutecommand_can_override_result(): void
	{
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnExecuteCommand[] = function ($sender, $param): void {
			$param->setResult(0);
		};

		$result = $gw->insertOrIgnore(['username' => 'alice', 'score' => 1]);
		$this->assertFalse($result);
	}

	// -----------------------------------------------------------------------
	// Base class throws TDbException
	// -----------------------------------------------------------------------

	public function test_base_builder_throws_for_insertOrIgnore(): void
	{
		$meta      = new \Prado\Data\Common\Pgsql\TPgsqlMetaData(self::$conn);
		$tableInfo = $meta->getTableInfo('upsert_test');
		$base      = new TDbCommandBuilder(self::$conn, $tableInfo);

		$this->expectException(TDbException::class);
		$base->createInsertOrIgnoreCommand(['username' => 'x', 'score' => 1]);
	}
}
