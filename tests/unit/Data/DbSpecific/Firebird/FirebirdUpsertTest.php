<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

/**
 * FirebirdUpsertTest — comprehensive tests for Firebird upsert behaviour.
 *
 * Firebird upsert uses MERGE INTO ... WHEN MATCHED THEN UPDATE SET ...
 * WHEN NOT MATCHED THEN INSERT with USING (SELECT ... FROM RDB$DATABASE).
 * An active transaction is required; TDbException is thrown otherwise.
 *
 * Firebird MERGE uses bare t / s aliases (useAsAlias=false, no AS keyword).
 * Firebird stores unquoted identifiers as uppercase; use array_change_key_case.
 *
 * Requires: prado_unitest.fdb on a Firebird 4 server (see tests/initdb_firebird.sql).
 *   upsert_test(username VARCHAR(100) PK, score INTEGER DEFAULT 0 NOT NULL)
 */

use Prado\Data\DataGateway\TDataGatewayEventParameter;
use Prado\Data\DataGateway\TDataGatewayResultEventParameter;
use Prado\Data\DataGateway\TTableGateway;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TDbException;

class FirebirdUpsertTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected static ?TDbConnection $conn = null;
	protected static ?TTableGateway $gateway = null;

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupFirebirdConnection';
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
		// pdo_firebird in autocommit mode always keeps an implicit transaction alive:
		// after each statement it auto-commits and immediately starts the next one.
		// Calling PDO::beginTransaction() while that implicit transaction is active
		// raises "There is already an active transaction". Explicitly committing the
		// empty post-DELETE transaction resets the internal handle to NULL so that
		// explicit beginTransaction() calls in the test methods succeed.
		try {
			static::$conn->getPdoInstance()->commit();
		} catch (\Exception $e) {
			// No implicit transaction was active — safe to ignore.
		}
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

	public function test_sql_using_contains_from_rdb_database(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$txn = self::$conn->beginTransaction();
		$gw->upsert(['username' => 'alice', 'score' => 10], null, null);
		$txn->rollback();
		$this->assertStringContainsString('FROM RDB$DATABASE', $capturedSql);
	}

	public function test_sql_uses_bare_aliases_without_as_keyword(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$txn = self::$conn->beginTransaction();
		$gw->upsert(['username' => 'alice', 'score' => 10], null, null);
		$txn->rollback();
		// Must contain bare alias references (e.g. ") s ON")
		$this->assertMatchesRegularExpression('/USING\s*\(.*\)\s+s\s+ON/si', $capturedSql);
		// Must NOT contain AS-keyword aliases for t or s — use word-boundary regex
		// to avoid false-positives on column aliases like "CAST(... AS score)".
		$this->assertDoesNotMatchRegularExpression('/\bAS\s+t\b/i', $capturedSql);
		$this->assertDoesNotMatchRegularExpression('/\)\s+AS\s+s\b/i', $capturedSql);
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
		// PK = username → updateData = {score}; SCORE appears in WHEN MATCHED branch
		$matchedPos = stripos($capturedSql, 'WHEN MATCHED');
		$updatePart = substr($capturedSql, (int) $matchedPos);
		// Firebird column name "SCORE" appears in UPDATE SET
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
