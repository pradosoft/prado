<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

/**
 * PgsqlUpsertTest — comprehensive tests for PostgreSQL upsert behaviour.
 *
 * PostgreSQL uses INSERT ... ON CONFLICT (cols) DO UPDATE SET or DO NOTHING.
 * Unlike MySQL, PostgreSQL requires an explicit conflict target in the SQL.
 * This means default conflictColumns=null (PK) requires the PK to be in $data
 * for the conflict clause to match; for UNIQUE-column conflicts, pass conflictColumns explicitly.
 *
 * Requires: prado_unitest database with upsert_test table (see tests/initdb_pgsql.sql).
 */

use Prado\Data\Common\TDbCommandBuilder;
use Prado\Data\DataGateway\TDataGatewayEventParameter;
use Prado\Data\DataGateway\TDataGatewayResultEventParameter;
use Prado\Data\DataGateway\TTableGateway;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TDbException;

class PgsqlUpsertTest extends PHPUnit\Framework\TestCase
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

	public function test_sql_on_conflict_do_update_set_with_excluded(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$gw->upsert(['username' => 'test', 'score' => 1], null, ['username']);
		$this->assertNotNull($capturedSql);
		$this->assertStringContainsString('ON CONFLICT', $capturedSql);
		$this->assertStringContainsString('DO UPDATE SET', $capturedSql);
		$this->assertStringContainsString('EXCLUDED.', $capturedSql);
	}

	public function test_sql_conflict_clause_names_the_conflict_column(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$gw->upsert(['username' => 'test', 'score' => 1], null, ['username']);
		// ON CONFLICT ("username") — quoted username in conflict clause
		$this->assertStringContainsString('"username"', $capturedSql);
	}

	public function test_sql_update_set_references_excluded_pseudotable(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$gw->upsert(['username' => 'test', 'score' => 1], null, ['username']);
		// score updated via EXCLUDED.score
		$this->assertStringContainsString('"score" = EXCLUDED."score"', $capturedSql);
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

	public function test_sql_default_pk_conflict_uses_id(): void
	{
		// conflictColumns=null → resolves to PK ('id')
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$gw->upsert(['id' => 1, 'username' => 'test', 'score' => 1], null, null);
		$this->assertStringContainsString('"id"', $capturedSql);
		$this->assertStringContainsString('ON CONFLICT', $capturedSql);
	}

	public function test_sql_explicit_updateData_only_those_columns_in_set(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$gw->upsert(['username' => 'test', 'score' => 1], ['score' => 1], ['username']);
		$setPos  = strpos($capturedSql, 'DO UPDATE SET');
		$setPart = substr($capturedSql, (int) $setPos);
		$this->assertStringContainsString('"score"', $setPart);
		$this->assertStringNotContainsString('"username" = EXCLUDED."username"', $setPart);
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
	// Behavioral: conflict on UNIQUE username (explicit conflict col)
	// -----------------------------------------------------------------------

	public function test_conflict_on_unique_username_updates_score(): void
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

	public function test_conflict_update_returns_truthy_value(): void
	{
		self::$gateway->upsert(['username' => 'alice', 'score' => 10], null, ['username']);
		$result = self::$gateway->upsert(['username' => 'alice', 'score' => 99], null, ['username']);
		$this->assertNotFalse($result);
	}

	// -----------------------------------------------------------------------
	// Behavioral: conflict on PK (default conflictColumns, id in data)
	// -----------------------------------------------------------------------

	public function test_conflict_on_pk_with_id_in_data_updates_row(): void
	{
		$id = (int) self::$gateway->insert(['username' => 'alice', 'score' => 10]);
		self::$gateway->upsert(['id' => $id, 'username' => 'alice', 'score' => 77]);

		$row = self::$gateway->find('username = ?', 'alice');
		$this->assertEquals(77, (int) $row['score']);
	}

	// -----------------------------------------------------------------------
	// Explicit updateData
	// -----------------------------------------------------------------------

	public function test_explicit_updateData_updates_only_specified_columns(): void
	{
		self::$gateway->insert(['username' => 'alice', 'score' => 10]);
		self::$gateway->upsert(
			['username' => 'alice', 'score' => 55],
			['score' => 55],
			['username']
		);

		$row = self::$gateway->find('username = ?', 'alice');
		$this->assertEquals(55, (int) $row['score']);
		$this->assertEquals('alice', $row['username']);
	}

	public function test_null_updateData_updates_all_non_conflict_columns(): void
	{
		self::$gateway->insert(['username' => 'alice', 'score' => 10]);
		self::$gateway->upsert(
			['username' => 'alice', 'score' => 88],
			null,
			['username']
		);
		$row = self::$gateway->find('username = ?', 'alice');
		$this->assertEquals(88, (int) $row['score']);
	}

	// -----------------------------------------------------------------------
	// Empty updateData → DO NOTHING
	// -----------------------------------------------------------------------

	public function test_empty_updateData_acts_as_insert_or_ignore(): void
	{
		self::$gateway->upsert(['username' => 'alice', 'score' => 10], null, ['username']);
		self::$gateway->upsert(['username' => 'alice', 'score' => 99], [],   ['username']);

		$row = self::$gateway->find('username = ?', 'alice');
		$this->assertEquals(10, (int) $row['score']);
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

		$gw->upsert(['username' => 'alice', 'score' => 1], null, ['username']);
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

	// -----------------------------------------------------------------------
	// Base class throws TDbException
	// -----------------------------------------------------------------------

	public function test_base_builder_throws_for_upsert(): void
	{
		$meta      = new \Prado\Data\Common\Pgsql\TPgsqlMetaData(self::$conn);
		$tableInfo = $meta->getTableInfo('upsert_test');
		$base      = new TDbCommandBuilder(self::$conn, $tableInfo);

		$this->expectException(TDbException::class);
		$base->createUpsertCommand(['username' => 'x', 'score' => 1]);
	}
}
