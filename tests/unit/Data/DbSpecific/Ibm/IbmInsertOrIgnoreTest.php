<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

/**
 * IbmInsertOrIgnoreTest — comprehensive tests for IBM DB2 insertOrIgnore behaviour.
 *
 * IBM DB2 has no native INSERT OR IGNORE; Prado uses a MERGE statement with
 * USING (SELECT ... FROM SYSIBM.SYSDUMMY1) AS s syntax.  MERGE requires an
 * active transaction — tests verify both the exception thrown without one and
 * the correct MERGE SQL generated/executed within one.
 *
 * upsert_test uses username as natural-key PK (no identity) so MERGE ON
 * clauses can reference the PK column that is always present in $data.
 *
 * DB2 stores unquoted identifiers as uppercase; column names returned by
 * queries are uppercase — use array_change_key_case($row, CASE_LOWER).
 * DB2 MERGE uses AS t / AS s aliases (useAsAlias=true).
 *
 * Connection parameters are read from environment variables:
 *   DB2_USER     (default: db2inst1)
 *   DB2_PASSWORD (default: Prado_Unitest1)
 *   DB2_DATABASE (default: pradount)
 *
 * Requires: IBM DB2 with pradount database (see tests/initdb_ibm.sql).
 */

use Prado\Data\DataGateway\TDataGatewayEventParameter;
use Prado\Data\DataGateway\TDataGatewayResultEventParameter;
use Prado\Data\DataGateway\TTableGateway;
use Prado\Data\TDbConnection;
use Prado\Exceptions\TDbException;

class IbmInsertOrIgnoreTest extends PHPUnit\Framework\TestCase
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
		// No transaction started — must throw
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 10]);
	}

	// -----------------------------------------------------------------------
	// SQL generation
	// -----------------------------------------------------------------------

	public function test_sql_uses_merge_when_not_matched_then_insert(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$txn = self::$conn->beginTransaction();
		$gw->insertOrIgnore(['username' => 'alice', 'score' => 10]);
		$txn->rollback();
		$this->assertNotNull($capturedSql);
		$this->assertStringContainsString('MERGE INTO', $capturedSql);
		$this->assertStringContainsString('WHEN NOT MATCHED THEN INSERT', $capturedSql);
		$this->assertStringNotContainsString('WHEN MATCHED', $capturedSql);
	}

	public function test_sql_using_select_contains_from_sysibm_sysdummy1(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$txn = self::$conn->beginTransaction();
		$gw->insertOrIgnore(['username' => 'alice', 'score' => 10]);
		$txn->rollback();
		$this->assertStringContainsString('FROM SYSIBM.SYSDUMMY1', $capturedSql);
	}

	public function test_sql_uses_as_alias_keywords(): void
	{
		// DB2 MERGE uses AS t / AS s aliases (useAsAlias=true)
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$txn = self::$conn->beginTransaction();
		$gw->insertOrIgnore(['username' => 'alice', 'score' => 10]);
		$txn->rollback();
		$this->assertStringContainsString(' AS t ', $capturedSql);
		$this->assertStringContainsString(' AS s ', $capturedSql);
	}

	public function test_sql_has_no_dual_or_rdb_source(): void
	{
		$capturedSql = null;
		$gw = new TTableGateway('upsert_test', self::$conn);
		$gw->OnCreateCommand[] = function ($sender, $param) use (&$capturedSql): void {
			$capturedSql = $param->getCommand()->Text;
		};
		$txn = self::$conn->beginTransaction();
		$gw->insertOrIgnore(['username' => 'alice', 'score' => 10]);
		$txn->rollback();
		$this->assertStringNotContainsString('DUAL', $capturedSql);
		$this->assertStringNotContainsString('RDB$', $capturedSql);
	}

	// -----------------------------------------------------------------------
	// Behavioral: insert within transaction
	// -----------------------------------------------------------------------

	public function test_new_row_inserted_within_transaction(): void
	{
		$txn = self::$conn->beginTransaction();
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 10]);
		$txn->commit();

		$row = self::$gateway->find('username = ?', 'alice');
		$this->assertIsArray($row);
		$lc = array_change_key_case($row, CASE_LOWER);
		$this->assertEquals('alice', $lc['username']);
		$this->assertEquals(10, (int) $lc['score']);
	}

	public function test_new_row_returns_true_for_natural_key_table(): void
	{
		$txn    = self::$conn->beginTransaction();
		$result = self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 10]);
		$txn->commit();

		// Natural key table (no identity) → getLastInsertID()=null → returns true
		$this->assertTrue($result);
	}

	// -----------------------------------------------------------------------
	// Behavioral: duplicate ignored
	// -----------------------------------------------------------------------

	public function test_duplicate_pk_returns_false(): void
	{
		$txn = self::$conn->beginTransaction();
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 10]);
		$result = self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 99]);
		$txn->commit();

		$this->assertFalse($result);
	}

	public function test_duplicate_does_not_increase_row_count(): void
	{
		$txn = self::$conn->beginTransaction();
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 10]);
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 99]);
		$txn->commit();

		$count = (int) self::$conn->createCommand('SELECT COUNT(*) FROM upsert_test')->queryScalar();
		$this->assertEquals(1, $count);
	}

	public function test_existing_row_unchanged_after_ignored_insert(): void
	{
		$txn = self::$conn->beginTransaction();
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 10]);
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 99]);
		$txn->commit();

		$row = self::$gateway->find('username = ?', 'alice');
		$lc  = array_change_key_case($row, CASE_LOWER);
		$this->assertEquals(10, (int) $lc['score']);
	}

	// -----------------------------------------------------------------------
	// Mixed inserts
	// -----------------------------------------------------------------------

	public function test_only_conflicting_row_ignored_others_inserted(): void
	{
		$txn = self::$conn->beginTransaction();
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 10]);
		$res2 = self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 99]);
		$res3 = self::$gateway->insertOrIgnore(['username' => 'bob',   'score' => 20]);
		$txn->commit();

		$this->assertFalse($res2);
		$this->assertTrue($res3);
		$count = (int) self::$conn->createCommand('SELECT COUNT(*) FROM upsert_test')->queryScalar();
		$this->assertEquals(2, $count);
	}

	public function test_transaction_rollback_undoes_insert(): void
	{
		$txn = self::$conn->beginTransaction();
		self::$gateway->insertOrIgnore(['username' => 'alice', 'score' => 10]);
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
		$gw->insertOrIgnore(['username' => 'alice', 'score' => 1]);
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
		$gw->insertOrIgnore(['username' => 'alice', 'score' => 1]);
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
		$result = $gw->insertOrIgnore(['username' => 'alice', 'score' => 1]);
		$txn->rollback();

		$this->assertFalse($result);
	}
}
