<?php

require_once(__DIR__ . '/../../../../PradoUnit.php');

/**
 * MysqlUpsertTest — comprehensive tests for MySQL upsert behaviour.
 *
 * MySQL uses INSERT ... ON DUPLICATE KEY UPDATE which fires on ANY unique-constraint
 * violation (PK or UNIQUE KEY), unlike PostgreSQL/SQLite which require an explicit
 * conflict target. The $conflictColumns parameter controls only which columns are
 * excluded from the ON DUPLICATE KEY UPDATE clause (not the SQL conflict target).
 *
 * Requires: prado_unitest database with upsert_test table (see tests/initdb_mysql.sql).
 */

use Prado\Data\Common\TDbCommandBuilder;
use Prado\Data\DataGateway\TDataGatewayEventParameter;
use Prado\Data\DataGateway\TDataGatewayResultEventParameter;
use Prado\Data\DataGateway\TTableGateway;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TDbException;

class MysqlUpsertTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected static ?TDbConnection $conn = null;
	protected static ?TTableGateway $gateway = null;

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
				static::$gateway = new TTableGateway('upsert_test', $conn);
			}
		}
		static::$conn->createCommand('DELETE FROM `upsert_test`')->execute();
		static::$conn->createCommand('ALTER TABLE `upsert_test` AUTO_INCREMENT = 1')->execute();
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

	public function test_mysql_sql_uses_on_duplicate_key_update(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$gw->upsert(['username' => 'test', 'score' => 1], null, null);
		$this->assertNotNull($capturedSql);
		$this->assertStringContainsString('INSERT INTO', $capturedSql);
		$this->assertStringContainsString('ON DUPLICATE KEY UPDATE', $capturedSql);
		$this->assertStringContainsString('VALUES(`score`)', $capturedSql);
	}

	public function test_mysql_sql_update_clause_excludes_pk_columns(): void
	{
		// PK is 'id'; updateData defaults to non-PK: username, score
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$gw->upsert(['username' => 'test', 'score' => 1], null, null);
		// id excluded from UPDATE; username and score updated via VALUES()
		$dupPos = strpos($capturedSql, 'ON DUPLICATE KEY UPDATE');
		$updatePart = substr($capturedSql, (int) $dupPos);
		$this->assertStringNotContainsString('`id`=VALUES(`id`)', $updatePart);
		$this->assertStringContainsString('`username`=VALUES(`username`)', $updatePart);
		$this->assertStringContainsString('`score`=VALUES(`score`)', $updatePart);
	}

	public function test_mysql_sql_explicit_conflictColumns_excludes_them_from_update(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$gw->upsert(['username' => 'test', 'score' => 1], null, ['username']);
		$dupPos = strpos($capturedSql, 'ON DUPLICATE KEY UPDATE');
		$updatePart = substr($capturedSql, (int) $dupPos);
		// username is conflict col → excluded from UPDATE; score is updated
		$this->assertStringNotContainsString('`username`=VALUES(`username`)', $updatePart);
		$this->assertStringContainsString('`score`=VALUES(`score`)', $updatePart);
	}

	public function test_mysql_sql_explicit_updateData_only_those_columns_in_update(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		// integer-keyed column-name list: only 'score' appears in UPDATE via VALUES()
		$gw->upsert(['username' => 'test', 'score' => 1], ['score'], ['username']);
		$dupPos = strpos($capturedSql, 'ON DUPLICATE KEY UPDATE');
		$updatePart = substr($capturedSql, (int) $dupPos);
		$this->assertStringContainsString('`score`=VALUES(`score`)', $updatePart);
		$this->assertStringNotContainsString('`username`=VALUES(`username`)', $updatePart);
	}

	public function test_mysql_sql_empty_updateData_uses_insert_ignore(): void
	{
		// When updateData=[], falls back to INSERT IGNORE (no ON DUPLICATE KEY UPDATE)
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$gw->upsert(['username' => 'test', 'score' => 1], [], ['username']);
		$this->assertStringContainsString('INSERT IGNORE INTO', $capturedSql);
		$this->assertStringNotContainsString('ON DUPLICATE KEY UPDATE', $capturedSql);
	}

	// -----------------------------------------------------------------------
	// Behavioral: insert new row
	// -----------------------------------------------------------------------

	public function test_mysql_upsert_inserts_new_row(): void
	{
		self::$gateway->upsert(['username' => 'alice', 'score' => 10]);

		$row = self::$gateway->find('username = ?', 'alice');
		$this->assertIsArray($row);
		$this->assertEquals('alice', $row['username']);
		$this->assertEquals(10, (int) $row['score']);
	}

	public function test_mysql_upsert_new_row_returns_integer_id(): void
	{
		$result = self::$gateway->upsert(['username' => 'alice', 'score' => 10]);
		$this->assertNotFalse($result);
		$this->assertGreaterThan(0, (int) $result);
	}

	// -----------------------------------------------------------------------
	// Behavioral: conflict on UNIQUE username → update via ON DUPLICATE KEY
	// -----------------------------------------------------------------------

	public function test_mysql_conflict_on_unique_username_updates_score(): void
	{
		self::$gateway->upsert(['username' => 'alice', 'score' => 10]);
		self::$gateway->upsert(['username' => 'alice', 'score' => 99]);

		$row = self::$gateway->find('username = ?', 'alice');
		$this->assertEquals(99, (int) $row['score']);
	}

	public function test_mysql_conflict_does_not_create_duplicate_rows(): void
	{
		self::$gateway->upsert(['username' => 'alice', 'score' => 10]);
		self::$gateway->upsert(['username' => 'alice', 'score' => 99]);

		$count = (int) self::$conn->createCommand('SELECT COUNT(*) FROM `upsert_test`')->queryScalar();
		$this->assertEquals(1, $count);
	}

	public function test_mysql_conflict_update_returns_truthy_value(): void
	{
		self::$gateway->upsert(['username' => 'alice', 'score' => 10]);
		$result = self::$gateway->upsert(['username' => 'alice', 'score' => 99]);
		$this->assertNotFalse($result);
	}

	public function test_mysql_conflict_with_explicit_conflict_columns_updates_score(): void
	{
		self::$gateway->upsert(['username' => 'alice', 'score' => 10], null, ['username']);
		self::$gateway->upsert(['username' => 'alice', 'score' => 77], null, ['username']);

		$row = self::$gateway->find('username = ?', 'alice');
		$this->assertEquals(77, (int) $row['score']);
	}

	// -----------------------------------------------------------------------
	// Explicit updateData
	// -----------------------------------------------------------------------

	public function test_mysql_explicit_updateData_only_updates_specified_columns(): void
	{
		self::$gateway->insert(['username' => 'alice', 'score' => 10]);
		// Only score in updateData; username also present in data but won't be in UPDATE
		self::$gateway->upsert(
			['username' => 'alice', 'score' => 55],
			['score' => 55],
			['username']
		);

		$row = self::$gateway->find('username = ?', 'alice');
		$this->assertEquals(55, (int) $row['score']);
		$this->assertEquals('alice', $row['username']);
	}

	public function test_mysql_null_updateData_updates_all_non_conflict_columns(): void
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
	// Empty updateData → no ON DUPLICATE KEY UPDATE (acts as INSERT IGNORE)
	// -----------------------------------------------------------------------

	public function test_mysql_empty_updateData_does_not_update_on_conflict(): void
	{
		self::$gateway->insert(['username' => 'alice', 'score' => 10]);
		self::$gateway->upsert(['username' => 'alice', 'score' => 99], [], ['username']);

		$row = self::$gateway->find('username = ?', 'alice');
		$this->assertEquals(10, (int) $row['score']);
	}

	// -----------------------------------------------------------------------
	// Other rows not affected
	// -----------------------------------------------------------------------

	public function test_mysql_upsert_does_not_affect_other_rows(): void
	{
		self::$gateway->insert(['username' => 'alice', 'score' => 10]);
		self::$gateway->insert(['username' => 'bob',   'score' => 20]);

		self::$gateway->upsert(['username' => 'alice', 'score' => 99]);

		$bob = self::$gateway->find('username = ?', 'bob');
		$this->assertEquals(20, (int) $bob['score']);
	}

	// -----------------------------------------------------------------------
	// Events
	// -----------------------------------------------------------------------

	public function test_mysql_oncreatecommand_event_is_raised(): void
	{
		$fired = false;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$fired): void {
			$this->assertInstanceOf(TDataGatewayEventParameter::class, $param);
			$fired = true;
		};

		$gw->upsert(['username' => 'alice', 'score' => 1]);
		$this->assertTrue($fired);
	}

	public function test_mysql_onexecutecommand_event_is_raised(): void
	{
		$captured = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnExecuteCommand[] = function ($sender, $param) use (&$captured): void {
			$this->assertInstanceOf(TDataGatewayResultEventParameter::class, $param);
			$captured = $param->getResult();
		};

		$gw->upsert(['username' => 'alice', 'score' => 1]);
		// MySQL returns 1 for INSERT, 2 for UPDATE via ON DUPLICATE KEY
		$this->assertNotNull($captured);
		$this->assertGreaterThan(0, $captured);
	}

	public function test_mysql_onexecutecommand_reports_two_on_update(): void
	{
		self::$gateway->insert(['username' => 'alice', 'score' => 10]);

		$captured = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnExecuteCommand[] = function ($sender, $param) use (&$captured): void {
			$captured = $param->getResult();
		};

		$gw->upsert(['username' => 'alice', 'score' => 99]);
		// MySQL ON DUPLICATE KEY UPDATE returns 2 for an update (counts old + new)
		$this->assertEquals(2, $captured);
	}

	public function test_mysql_onexecutecommand_can_override_result(): void
	{
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnExecuteCommand[] = function ($sender, $param): void {
			$param->setResult(0);
		};

		$result = $gw->upsert(['username' => 'alice', 'score' => 1]);
		$this->assertFalse($result);
	}

	// -----------------------------------------------------------------------
	// Base class throws TDbException
	// -----------------------------------------------------------------------

	public function test_mysql_base_builder_throws_for_upsert(): void
	{
		$meta      = new \Prado\Data\Common\Mysql\TMysqlMetaData(self::$conn);
		$tableInfo = $meta->getTableInfo('upsert_test');
		$base      = new TDbCommandBuilder(self::$conn, $tableInfo);

		$this->expectException(TDbException::class);
		$base->createUpsertCommand(['username' => 'x', 'score' => 1]);
	}

	// -----------------------------------------------------------------------
	// Column-name list updateData
	// -----------------------------------------------------------------------

	public function test_mysql_updateData_column_name_list_updates_only_those_columns(): void
	{
		self::$gateway->insert(['username' => 'alice', 'score' => 10]);
		self::$gateway->upsert(['username' => 'alice', 'score' => 77], ['score'], ['username']);

		$row = self::$gateway->find('username = ?', 'alice');
		$this->assertEquals(77, (int) $row['score']);
		$this->assertEquals('alice', $row['username']);
	}

	public function test_mysql_sql_column_name_list_generates_correct_update_clause(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$gw->upsert(['username' => 'alice', 'score' => 77], ['score'], ['username']);
		$this->assertStringContainsString('`score`=VALUES(`score`)', $capturedSql);
		$this->assertStringNotContainsString('`username`=VALUES(`username`)', $capturedSql);
	}

	public function test_mysql_sql_column_name_list_uses_values_function(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$gw->upsert(['username' => 'alice', 'score' => 77], ['score'], ['username']);
		$this->assertStringContainsString('VALUES(', $capturedSql);
	}

	public function test_mysql_updateData_column_name_list_leaves_other_columns_unchanged(): void
	{
		self::$gateway->insert(['username' => 'alice', 'score' => 10]);
		// Only score in the update list; username is the conflict col and is not updated
		self::$gateway->upsert(['username' => 'alice', 'score' => 55], ['score'], ['username']);

		$row = self::$gateway->find('username = ?', 'alice');
		$this->assertEquals('alice', $row['username']);
	}

	// -----------------------------------------------------------------------
	// Explicit value (string-keyed) updateData
	// -----------------------------------------------------------------------

	public function test_mysql_updateData_explicit_value_overrides_insert_data_on_conflict(): void
	{
		self::$gateway->insert(['username' => 'alice', 'score' => 10]);
		// Explicit override: score should be set to 99 regardless of insert data value (10)
		self::$gateway->upsert(['username' => 'alice', 'score' => 10], ['score' => 99], ['username']);

		$row = self::$gateway->find('username = ?', 'alice');
		$this->assertEquals(99, (int) $row['score']);
	}

	public function test_mysql_sql_explicit_value_updateData_does_not_use_insert_data(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$gw->upsert(['username' => 'alice', 'score' => 10], ['score' => 99], ['username']);
		// Explicit override must NOT use VALUES(col) syntax
		$this->assertStringNotContainsString('`score`=VALUES(`score`)', $capturedSql);
		// Must contain a bound param reference instead
		$this->assertStringContainsString(':_upsert_score', $capturedSql);
	}

	public function test_mysql_sql_explicit_value_does_not_use_values_function(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		// Only explicit override — no integer-keyed columns — so VALUES() must not appear in UPDATE clause
		$gw->upsert(['username' => 'alice', 'score' => 10], ['score' => 99], ['username']);
		$dupPos = strpos($capturedSql, 'ON DUPLICATE KEY UPDATE');
		$updatePart = substr($capturedSql, (int) $dupPos);
		$this->assertStringNotContainsString('VALUES(`score`)', $updatePart);
	}

	// -----------------------------------------------------------------------
	// Mixed (column-name + explicit value) updateData
	// -----------------------------------------------------------------------

	public function test_mysql_updateData_mixed_handles_column_name_and_explicit_value_simultaneously(): void
	{
		$id = (int) self::$gateway->insert(['username' => 'alice', 'score' => 10]);
		// Conflict on PK (id): update score from INSERT row (77), rename username explicitly to 'alice_renamed'
		self::$gateway->upsert(
			['id' => $id, 'username' => 'alice', 'score' => 77],
			['score', 'username' => 'alice_renamed'],
			['id']
		);

		$row = self::$gateway->find('id = ?', $id);
		$this->assertEquals(77, (int) $row['score']);
		$this->assertEquals('alice_renamed', $row['username']);
	}

	public function test_mysql_sql_mixed_updateData_generates_both_value_references_and_literals(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$gw->upsert(
			['id' => 1, 'username' => 'alice', 'score' => 77],
			['score', 'username' => 'alice_renamed'],
			['id']
		);
		// score uses VALUES() (integer-keyed column name)
		$this->assertStringContainsString('`score`=VALUES(`score`)', $capturedSql);
		// username uses explicit bound param (string-keyed override)
		$this->assertStringContainsString(':_upsert_username', $capturedSql);
		$this->assertStringNotContainsString('`username`=VALUES(`username`)', $capturedSql);
	}
}
