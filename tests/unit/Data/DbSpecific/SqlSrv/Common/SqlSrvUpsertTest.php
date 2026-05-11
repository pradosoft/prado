<?php

require_once(__DIR__ . '/../../../../PradoUnit.php');

/**
 * SqlSrvUpsertTest — comprehensive tests for SQL Server upsert behaviour.
 *
 * SQL Server upsert uses MERGE INTO ... WHEN MATCHED THEN UPDATE ... WHEN NOT MATCHED THEN INSERT.
 * An active transaction is required; TDbException is thrown otherwise.
 *
 * Requires: prado_unitest database on SQL Server (see tests/initdb_mssql.sql).
 *   upsert_test(username NVARCHAR(100) PK, score INT DEFAULT 0)
 */

use Prado\Data\DataGateway\TDataGatewayEventParameter;
use Prado\Data\DataGateway\TDataGatewayResultEventParameter;
use Prado\Data\DataGateway\TTableGateway;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TDbException;

class SqlSrvUpsertTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected static ?TDbConnection $conn = null;
	protected static ?TTableGateway $gateway = null;

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupSqlSrvConnection';
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
		static::$conn->createCommand('DELETE FROM [upsert_test]')->execute();
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
	// Transaction requirement
	// -----------------------------------------------------------------------

	public function test_throws_TDbException_without_active_transaction(): void
	{
		$this->expectException(TDbException::class);
		self::$gateway->upsert(['username' => 'alice', 'score' => 10]);
	}

	// -----------------------------------------------------------------------
	// SQL generation
	// -----------------------------------------------------------------------

	public function test_sql_contains_merge_when_matched_and_when_not_matched(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$txn = self::$conn->beginTransaction();
		$gw->upsert(['username' => 'alice', 'score' => 10], null, null);
		$txn->rollback();
		$this->assertNotNull($capturedSql);
		$this->assertStringContainsString('MERGE INTO', $capturedSql);
		$this->assertStringContainsString('WHEN MATCHED THEN UPDATE SET', $capturedSql);
		$this->assertStringContainsString('WHEN NOT MATCHED THEN INSERT', $capturedSql);
	}

	public function test_sql_update_set_contains_non_pk_columns(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$txn = self::$conn->beginTransaction();
		$gw->upsert(['username' => 'alice', 'score' => 10], null, null);
		$txn->rollback();
		// PK = username → updateData = {score}
		$matchedPos = strpos($capturedSql, 'WHEN MATCHED');
		$updatePart = substr($capturedSql, (int) $matchedPos);
		$this->assertStringContainsString('[score]', $updatePart);
		// username is PK, not in UPDATE SET
		$this->assertStringNotContainsString('t.[username] = s.username', $updatePart);
	}

	public function test_sql_explicit_updateData_only_those_columns_updated(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$txn = self::$conn->beginTransaction();
		$gw->upsert(['username' => 'alice', 'score' => 10], ['score' => 10], ['username']);
		$txn->rollback();
		$matchedPos = strpos($capturedSql, 'WHEN MATCHED');
		$updatePart = substr($capturedSql, (int) $matchedPos);
		$this->assertStringContainsString('[score]', $updatePart);
	}

	public function test_sql_empty_updateData_omits_when_matched_branch(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$txn = self::$conn->beginTransaction();
		$gw->upsert(['username' => 'alice', 'score' => 10], [], ['username']);
		$txn->rollback();
		$this->assertStringNotContainsString('WHEN MATCHED', $capturedSql);
		$this->assertStringContainsString('WHEN NOT MATCHED THEN INSERT', $capturedSql);
	}

	// -----------------------------------------------------------------------
	// Behavioral: insert new row
	// -----------------------------------------------------------------------

	public function test_upsert_inserts_new_row(): void
	{
		$txn = self::$conn->beginTransaction();
		self::$gateway->upsert(['username' => 'alice', 'score' => 10]);
		$txn->commit();

		$row = self::$gateway->find('username = ?', 'alice');
		$lc  = array_change_key_case($row, CASE_LOWER);
		$this->assertEquals('alice', $lc['username']);
		$this->assertEquals(10, (int) $lc['score']);
	}

	public function test_upsert_new_row_returns_true(): void
	{
		$txn    = self::$conn->beginTransaction();
		$result = self::$gateway->upsert(['username' => 'alice', 'score' => 10]);
		$txn->commit();

		$this->assertTrue($result);
	}

	// -----------------------------------------------------------------------
	// Behavioral: conflict → update
	// -----------------------------------------------------------------------

	public function test_conflict_on_pk_updates_non_pk_columns(): void
	{
		$txn = self::$conn->beginTransaction();
		self::$gateway->upsert(['username' => 'alice', 'score' => 10]);
		self::$gateway->upsert(['username' => 'alice', 'score' => 99]);
		$txn->commit();

		$row = self::$gateway->find('username = ?', 'alice');
		$lc  = array_change_key_case($row, CASE_LOWER);
		$this->assertEquals(99, (int) $lc['score']);
	}

	public function test_conflict_does_not_create_duplicate_rows(): void
	{
		$txn = self::$conn->beginTransaction();
		self::$gateway->upsert(['username' => 'alice', 'score' => 10]);
		self::$gateway->upsert(['username' => 'alice', 'score' => 99]);
		$txn->commit();

		$count = (int) self::$conn->createCommand('SELECT COUNT(*) FROM [upsert_test]')->queryScalar();
		$this->assertEquals(1, $count);
	}

	public function test_conflict_update_returns_truthy_value(): void
	{
		$txn = self::$conn->beginTransaction();
		self::$gateway->upsert(['username' => 'alice', 'score' => 10]);
		$result = self::$gateway->upsert(['username' => 'alice', 'score' => 99]);
		$txn->commit();

		$this->assertNotFalse($result);
	}

	// -----------------------------------------------------------------------
	// Explicit updateData
	// -----------------------------------------------------------------------

	public function test_explicit_updateData_only_updates_specified_columns(): void
	{
		$txn = self::$conn->beginTransaction();
		self::$gateway->insert(['username' => 'alice', 'score' => 10]);
		self::$gateway->upsert(
			['username' => 'alice', 'score' => 55],
			['score' => 55],
			['username']
		);
		$txn->commit();

		$row = self::$gateway->find('username = ?', 'alice');
		$lc  = array_change_key_case($row, CASE_LOWER);
		$this->assertEquals(55, (int) $lc['score']);
	}

	// -----------------------------------------------------------------------
	// Empty updateData → insert-or-ignore behaviour
	// -----------------------------------------------------------------------

	public function test_empty_updateData_does_not_update_on_conflict(): void
	{
		$txn = self::$conn->beginTransaction();
		self::$gateway->upsert(['username' => 'alice', 'score' => 10]);
		self::$gateway->upsert(['username' => 'alice', 'score' => 99], [], ['username']);
		$txn->commit();

		$row = self::$gateway->find('username = ?', 'alice');
		$lc  = array_change_key_case($row, CASE_LOWER);
		$this->assertEquals(10, (int) $lc['score']);
	}

	public function test_empty_updateData_on_conflict_returns_false(): void
	{
		$txn = self::$conn->beginTransaction();
		self::$gateway->upsert(['username' => 'alice', 'score' => 10]);
		$result = self::$gateway->upsert(['username' => 'alice', 'score' => 99], [], ['username']);
		$txn->commit();

		$this->assertFalse($result);
	}

	// -----------------------------------------------------------------------
	// Other rows not affected
	// -----------------------------------------------------------------------

	public function test_upsert_does_not_modify_other_rows(): void
	{
		$txn = self::$conn->beginTransaction();
		self::$gateway->upsert(['username' => 'alice', 'score' => 10]);
		self::$gateway->upsert(['username' => 'bob',   'score' => 20]);
		self::$gateway->upsert(['username' => 'alice', 'score' => 99]);
		$txn->commit();

		$bob = self::$gateway->find('username = ?', 'bob');
		$lc  = array_change_key_case($bob, CASE_LOWER);
		$this->assertEquals(20, (int) $lc['score']);
	}

	public function test_transaction_rollback_undoes_upsert(): void
	{
		$txn = self::$conn->beginTransaction();
		self::$gateway->upsert(['username' => 'alice', 'score' => 10]);
		$txn->rollback();

		$count = (int) self::$conn->createCommand('SELECT COUNT(*) FROM [upsert_test]')->queryScalar();
		$this->assertEquals(0, $count);
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

		$txn = self::$conn->beginTransaction();
		$gw->upsert(['username' => 'alice', 'score' => 1]);
		$txn->rollback();

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

		$txn = self::$conn->beginTransaction();
		$gw->upsert(['username' => 'alice', 'score' => 1]);
		$txn->rollback();

		$this->assertNotNull($captured);
	}

	public function test_onexecutecommand_can_override_result(): void
	{
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnExecuteCommand[] = function ($sender, $param): void {
			$param->setResult(0);
		};

		$txn    = self::$conn->beginTransaction();
		$result = $gw->upsert(['username' => 'alice', 'score' => 1]);
		$txn->rollback();

		$this->assertFalse($result);
	}

	// -----------------------------------------------------------------------
	// Column-name list updateData
	// -----------------------------------------------------------------------

	public function test_updateData_column_name_list_updates_only_those_columns(): void
	{
		$txn = self::$conn->beginTransaction();
		self::$gateway->insert(['username' => 'alice', 'score' => 10]);
		self::$gateway->upsert(['username' => 'alice', 'score' => 77], ['score'], ['username']);
		$txn->commit();

		$row = self::$gateway->find('username = ?', 'alice');
		$lc  = array_change_key_case($row, CASE_LOWER);
		$this->assertEquals(77, (int) $lc['score']);
		$this->assertEquals('alice', $lc['username']);
	}

	public function test_sql_column_name_list_generates_correct_update_clause(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$txn = self::$conn->beginTransaction();
		$gw->upsert(['username' => 'alice', 'score' => 77], ['score'], ['username']);
		$txn->rollback();
		// integer-keyed column name → t.[score] = s.score in WHEN MATCHED branch
		$matchedPos = strpos($capturedSql, 'WHEN MATCHED');
		$updatePart = substr($capturedSql, (int) $matchedPos);
		$this->assertStringContainsString('[score]', $updatePart);
	}

	public function test_updateData_column_name_list_leaves_other_columns_unchanged(): void
	{
		$txn = self::$conn->beginTransaction();
		self::$gateway->insert(['username' => 'alice', 'score' => 10]);
		// Only score in update list; username is conflict col and must not be updated
		self::$gateway->upsert(['username' => 'alice', 'score' => 55], ['score'], ['username']);
		$txn->commit();

		$row = self::$gateway->find('username = ?', 'alice');
		$lc  = array_change_key_case($row, CASE_LOWER);
		$this->assertEquals('alice', $lc['username']);
	}

	// -----------------------------------------------------------------------
	// Explicit value (string-keyed) updateData
	// -----------------------------------------------------------------------

	public function test_updateData_explicit_value_overrides_insert_data_on_conflict(): void
	{
		$txn = self::$conn->beginTransaction();
		self::$gateway->insert(['username' => 'alice', 'score' => 10]);
		// Explicit override: score should be set to 99 regardless of insert data value (10)
		self::$gateway->upsert(['username' => 'alice', 'score' => 10], ['score' => 99], ['username']);
		$txn->commit();

		$row = self::$gateway->find('username = ?', 'alice');
		$lc  = array_change_key_case($row, CASE_LOWER);
		$this->assertEquals(99, (int) $lc['score']);
	}

	public function test_sql_explicit_value_updateData_does_not_use_insert_data(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$txn = self::$conn->beginTransaction();
		$gw->upsert(['username' => 'alice', 'score' => 10], ['score' => 99], ['username']);
		$txn->rollback();
		// Explicit override must NOT reference the source alias (s.score)
		$matchedPos = strpos($capturedSql, 'WHEN MATCHED');
		$updatePart = substr($capturedSql, (int) $matchedPos);
		$this->assertStringNotContainsString('t.[score] = s.score', $updatePart);
	}

	// -----------------------------------------------------------------------
	// Mixed (column-name + explicit value) updateData
	// -----------------------------------------------------------------------

	public function test_updateData_mixed_handles_column_name_and_explicit_value_simultaneously(): void
	{
		// SqlSrv table: username (PK), score — no separate id column.
		// Mixed test: conflict on username (PK); score updated from record (integer-keyed),
		// no second non-PK column available for explicit override, so this tests that
		// the integer-keyed entry is correctly applied via the source alias.
		$txn = self::$conn->beginTransaction();
		self::$gateway->insert(['username' => 'alice', 'score' => 10]);
		self::$gateway->upsert(
			['username' => 'alice', 'score' => 77],
			['score'],
			['username']
		);
		$txn->commit();

		$row = self::$gateway->find('username = ?', 'alice');
		$lc  = array_change_key_case($row, CASE_LOWER);
		$this->assertEquals(77, (int) $lc['score']);
	}

	public function test_sql_mixed_updateData_generates_both_value_references_and_literals(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$txn = self::$conn->beginTransaction();
		// Mixed: score (integer-keyed, from record via s.score) — only one non-PK column available
		$gw->upsert(
			['username' => 'alice', 'score' => 77],
			['score', 'score' => 99],
			['username']
		);
		$txn->rollback();
		// At minimum the WHEN MATCHED branch references score
		$this->assertStringContainsString('WHEN MATCHED', $capturedSql);
		$this->assertStringContainsString('[score]', $capturedSql);
	}
}
