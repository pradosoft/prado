<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

/**
 * SqliteUpsertTest — comprehensive tests for SQLite upsert behaviour.
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

class SqliteUpsertTest extends PHPUnit\Framework\TestCase
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
	// SQL generation — upsert (ON CONFLICT DO UPDATE SET)
	// -----------------------------------------------------------------------

	public function test_sql_contains_on_conflict_do_update_set(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$gw->upsert(['username' => 'test', 'score' => 1], null, ['username']);
		$this->assertNotNull($capturedSql);
		$this->assertStringContainsString('INSERT INTO', $capturedSql);
		$this->assertStringContainsString('ON CONFLICT', $capturedSql);
		$this->assertStringContainsString('DO UPDATE SET', $capturedSql);
		// excluded pseudo-table (SQLite uses lowercase 'excluded')
		$this->assertStringContainsString('excluded.', $capturedSql);
	}

	public function test_sql_conflict_clause_uses_specified_columns(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$gw->upsert(['username' => 'test', 'score' => 1], null, ['username']);
		// ON CONFLICT("username") — conflict target is quoted username column
		$this->assertStringContainsString('"username"', $capturedSql);
	}

	public function test_sql_update_set_contains_non_conflict_columns(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$gw->upsert(['username' => 'test', 'score' => 1], null, ['username']);
		// score is non-conflict → appears in DO UPDATE SET
		$this->assertStringContainsString('"score"', $capturedSql);
	}

	public function test_sql_empty_updateData_produces_do_nothing(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$gw->upsert(['username' => 'test', 'score' => 1], [], ['username']);
		$this->assertStringContainsString('DO NOTHING', $capturedSql);
		$this->assertStringNotContainsString('DO UPDATE', $capturedSql);
	}

	public function test_sql_explicit_updateData_only_those_columns_in_set(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		// Only score in updateData; username is conflict col
		$gw->upsert(['username' => 'test', 'score' => 1], ['score' => 1], ['username']);
		$this->assertStringContainsString('"score"', $capturedSql);
		// username is the conflict target only, not in the SET clause again
		$setPos = strpos($capturedSql, 'DO UPDATE SET');
		$this->assertNotFalse($setPos);
		$setPart = substr($capturedSql, $setPos);
		$this->assertStringNotContainsString('"username" = excluded."username"', $setPart);
	}

	public function test_sql_default_conflict_columns_uses_pk(): void
	{
		// When conflictColumns=null, resolved to PK ('id')
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$gw->upsert(['id' => 1, 'username' => 'test', 'score' => 1], null, null);
		$this->assertStringContainsString('"id"', $capturedSql);
		$this->assertStringContainsString('ON CONFLICT', $capturedSql);
	}

	// -----------------------------------------------------------------------
	// Base class throws TDbException
	// -----------------------------------------------------------------------

	public function test_base_builder_throws_for_upsert(): void
	{
		$meta      = new TSqliteMetaData(self::$conn);
		$tableInfo = $meta->getTableInfo('upsert_test');
		$base      = new TDbCommandBuilder(self::$conn, $tableInfo);

		$this->expectException(TDbException::class);
		$base->createUpsertCommand(['username' => 'x', 'score' => 1]);
	}

	// -----------------------------------------------------------------------
	// Behavioral: insert new row
	// -----------------------------------------------------------------------

	public function test_upsert_inserts_new_row(): void
	{
		self::$gateway->upsert(['username' => 'alice', 'score' => 10], null, ['username']);

		$row = self::$gateway->find('username = ?', 'alice');
		$this->assertIsArray($row);
		$this->assertEquals('alice', $row['username']);
		$this->assertEquals(10, (int) $row['score']);
	}

	public function test_upsert_new_row_returns_integer_id(): void
	{
		$result = self::$gateway->upsert(['username' => 'alice', 'score' => 10], null, ['username']);
		$this->assertNotFalse($result);
		$this->assertGreaterThan(0, (int) $result);
	}

	// -----------------------------------------------------------------------
	// Behavioral: conflict → update
	// -----------------------------------------------------------------------

	public function test_conflict_on_unique_column_triggers_update(): void
	{
		self::$gateway->upsert(['username' => 'alice', 'score' => 10], null, ['username']);
		self::$gateway->upsert(['username' => 'alice', 'score' => 99], null, ['username']);

		$row = self::$gateway->find('username = ?', 'alice');
		$this->assertEquals(99, (int) $row['score']);
	}

	public function test_conflict_does_not_create_duplicate_rows(): void
	{
		self::$gateway->upsert(['username' => 'alice', 'score' => 10], null, ['username']);
		self::$gateway->upsert(['username' => 'alice', 'score' => 99], null, ['username']);

		$count = (int) self::$conn->createCommand('SELECT COUNT(*) FROM upsert_test')->queryScalar();
		$this->assertEquals(1, $count);
	}

	public function test_conflict_on_pk_triggers_update(): void
	{
		// Insert with explicit id, then upsert with same id (PK conflict)
		$id = (int) self::$gateway->insert(['username' => 'alice', 'score' => 10]);
		self::$gateway->upsert(['id' => $id, 'username' => 'alice', 'score' => 77]);

		$row = self::$gateway->find('username = ?', 'alice');
		$this->assertEquals(77, (int) $row['score']);
	}

	public function test_conflict_update_returns_truthy_value(): void
	{
		self::$gateway->upsert(['username' => 'alice', 'score' => 10], null, ['username']);
		$result = self::$gateway->upsert(['username' => 'alice', 'score' => 99], null, ['username']);
		$this->assertNotFalse($result);
	}

	// -----------------------------------------------------------------------
	// Explicit updateData
	// -----------------------------------------------------------------------

	public function test_explicit_updateData_only_updates_specified_columns(): void
	{
		self::$gateway->insert(['username' => 'alice', 'score' => 10]);
		// updateData only updates score; username should remain 'alice'
		self::$gateway->upsert(
			['username' => 'alice', 'score' => 50],
			['score' => 50],
			['username']
		);

		$row = self::$gateway->find('username = ?', 'alice');
		$this->assertEquals(50, (int) $row['score']);
		$this->assertEquals('alice', $row['username']);
	}

	public function test_null_updateData_defaults_to_all_non_conflict_columns(): void
	{
		self::$gateway->insert(['username' => 'alice', 'score' => 10]);
		self::$gateway->upsert(
			['username' => 'alice', 'score' => 88],
			null,        // default: all non-conflict cols = [score]
			['username']
		);

		$row = self::$gateway->find('username = ?', 'alice');
		$this->assertEquals(88, (int) $row['score']);
	}

	// -----------------------------------------------------------------------
	// Empty updateData → DO NOTHING (insert-or-ignore behaviour)
	// -----------------------------------------------------------------------

	public function test_empty_updateData_acts_as_insert_or_ignore_on_conflict(): void
	{
		self::$gateway->upsert(['username' => 'alice', 'score' => 10], null, ['username']);
		self::$gateway->upsert(['username' => 'alice', 'score' => 99], [], ['username']);

		$row = self::$gateway->find('username = ?', 'alice');
		$this->assertEquals(10, (int) $row['score'], 'score must remain unchanged with empty updateData');
	}

	public function test_empty_updateData_on_conflict_returns_false(): void
	{
		self::$gateway->upsert(['username' => 'alice', 'score' => 10], null, ['username']);
		$result = self::$gateway->upsert(['username' => 'alice', 'score' => 99], [], ['username']);
		$this->assertFalse($result);
	}

	// -----------------------------------------------------------------------
	// Other rows not affected
	// -----------------------------------------------------------------------

	public function test_upsert_does_not_modify_other_rows(): void
	{
		self::$gateway->insert(['username' => 'alice', 'score' => 10]);
		self::$gateway->insert(['username' => 'bob',   'score' => 20]);

		self::$gateway->upsert(['username' => 'alice', 'score' => 99], null, ['username']);

		$bob = self::$gateway->find('username = ?', 'bob');
		$this->assertEquals(20, (int) $bob['score']);
	}

	// -----------------------------------------------------------------------
	// resolveConflictColumns / resolveUpdateData helpers via SQL capture
	// -----------------------------------------------------------------------

	public function test_resolve_conflict_columns_defaults_to_pk(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$gw->upsert(['id' => 5, 'username' => 'test', 'score' => 1], null, null);
		// Default conflict → "id" (the PK); score and username in SET
		$this->assertStringContainsString('"id"', $capturedSql);
	}

	public function test_resolve_update_data_excludes_conflict_columns(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		// conflictColumns=['username'] → updateData = [score] only
		$gw->upsert(['username' => 'test', 'score' => 1], null, ['username']);
		$setPos  = strpos($capturedSql, 'DO UPDATE SET');
		$setPart = substr($capturedSql, (int) $setPos);
		// score should be updated
		$this->assertStringContainsString('"score"', $setPart);
		// username is conflict target, not in SET
		$this->assertStringNotContainsString('"username" = excluded."username"', $setPart);
	}

	// -----------------------------------------------------------------------
	// Events
	// -----------------------------------------------------------------------

	public function test_oncreatecommand_event_is_raised(): void
	{
		$fired = false;
		$gw    = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$fired): void {
			$this->assertInstanceOf(TDataGatewayEventParameter::class, $param);
			$fired = true;
		};

		$gw->upsert(['username' => 'alice', 'score' => 1], null, ['username']);
		$this->assertTrue($fired, 'OnCreateCommand was not raised');
	}

	public function test_onexecutecommand_event_is_raised(): void
	{
		$captured = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnExecuteCommand[] = function ($sender, $param) use (&$captured): void {
			$this->assertInstanceOf(TDataGatewayResultEventParameter::class, $param);
			$captured = $param->getResult();
		};

		$gw->upsert(['username' => 'alice', 'score' => 1], null, ['username']);
		$this->assertNotNull($captured);
	}

	public function test_onexecutecommand_can_override_result(): void
	{
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnExecuteCommand[] = function ($sender, $param): void {
			$param->setResult(0);
		};

		$result = $gw->upsert(['username' => 'alice', 'score' => 1], null, ['username']);
		$this->assertFalse($result);
	}
}
