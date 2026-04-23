<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

/**
 * IbmUpsertTest — comprehensive tests for IBM DB2 upsert behaviour.
 *
 * IBM DB2 upsert uses MERGE INTO ... WHEN MATCHED THEN UPDATE SET ...
 * WHEN NOT MATCHED THEN INSERT with USING (SELECT ... FROM SYSIBM.SYSDUMMY1) AS s.
 * An active transaction is required; TDbException is thrown otherwise.
 *
 * DB2 MERGE uses AS t / AS s aliases (useAsAlias=true).
 * DB2 stores unquoted identifiers as uppercase; use array_change_key_case.
 *
 * Connection parameters are read from environment variables:
 *   DB2_USER     (default: db2inst1)
 *   DB2_PASSWORD (default: Prado_Unitest1)
 *   DB2_DATABASE (default: pradount)
 *
 * Requires: IBM DB2 with pradount database (see tests/initdb_ibm.sql).
 *   upsert_test(username VARCHAR(100) PK, score INTEGER DEFAULT 0 NOT NULL)
 */

use Prado\Data\DataGateway\TDataGatewayEventParameter;
use Prado\Data\DataGateway\TDataGatewayResultEventParameter;
use Prado\Data\DataGateway\TTableGateway;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TDbException;

class IbmUpsertTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected static ?TDbConnection $conn = null;
	protected static ?TTableGateway $gateway = null;

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupIbmConnection';
	}

	protected function getDatabaseName(): ?string
	{
		return null;
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

	public function test_sql_using_contains_from_sysibm_sysdummy1(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$txn = self::$conn->beginTransaction();
		$gw->upsert(['username' => 'alice', 'score' => 10], null, null);
		$txn->rollback();

		$this->assertStringContainsString('FROM SYSIBM.SYSDUMMY1', $capturedSql);
	}

	public function test_sql_uses_as_alias_keywords(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$txn = self::$conn->beginTransaction();
		$gw->upsert(['username' => 'alice', 'score' => 10], null, null);
		$txn->rollback();

		$this->assertStringContainsString(' AS t ', $capturedSql);
		$this->assertStringContainsString(' AS s ', $capturedSql);
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

		// PK = username → updateData = {score}; "SCORE" appears in WHEN MATCHED branch
		$matchedPos = stripos($capturedSql, 'WHEN MATCHED');
		$updatePart = substr($capturedSql, (int) $matchedPos);
		$this->assertMatchesRegularExpression('/"?SCORE"?/i', $updatePart);
		// username is PK — must not appear on the left-hand side of the UPDATE SET
		$this->assertStringNotContainsString('t."USERNAME" = s.username', $updatePart);
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

		$matchedPos = stripos($capturedSql, 'WHEN MATCHED');
		$updatePart = substr($capturedSql, (int) $matchedPos);
		$this->assertMatchesRegularExpression('/"?SCORE"?/i', $updatePart);
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

		$count = (int) self::$conn->createCommand('SELECT COUNT(*) FROM upsert_test')->queryScalar();
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

		$count = (int) self::$conn->createCommand('SELECT COUNT(*) FROM upsert_test')->queryScalar();
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
}
