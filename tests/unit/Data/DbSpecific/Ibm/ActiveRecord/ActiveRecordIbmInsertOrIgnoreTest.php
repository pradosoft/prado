<?php

require_once(__DIR__ . '/../../../../PradoUnit.php');
require_once(__DIR__ . '/records/IbmUpsertTestRecord.php');

use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Data\ActiveRecord\TActiveRecordChangeEventParameter;
use Prado\Data\TDbConnection;

/**
 * ActiveRecordIbmInsertOrIgnoreTest — integration tests for {@see TActiveRecord::insertOrIgnore()} on IBM DB2.
 *
 * IBM DB2's upsert_test table uses `username` as the PK (no auto-increment id).
 *
 * IBM DB2 uses MERGE for insertOrIgnore/upsert, which requires an active explicit
 * transaction.  Every test that calls insertOrIgnore() wraps the call(s) in an
 * explicit transaction so that TIbmCommandBuilder does not throw.
 *
 * Requires: prado_unitest IBM DB2 database with the `upsert_test` table
 *   (see tests/initdb_ibm.sql).
 */
class ActiveRecordIbmInsertOrIgnoreTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected static ?TDbConnection $conn = null;

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupIbmConnection';
	}

	protected function getDatabaseName(): ?string
	{
		return null;
	}

	protected function getIsForActiveRecord(): bool
	{
		return true;
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
		static::$conn->createCommand('DELETE FROM upsert_test')->execute();
	}

	public static function tearDownAfterClass(): void
	{
		if (static::$conn !== null) {
			static::$conn->Active = false;
			static::$conn = null;
		}
	}

	// -----------------------------------------------------------------------
	// New record
	// -----------------------------------------------------------------------

	public function test_insertOrIgnore_new_record_returns_truthy(): void
	{
		$record = new IbmUpsertTestRecord();
		$record->username = 'alice';
		$record->score = 10;

		$txn = static::$conn->beginTransaction();
		$result = $record->insertOrIgnore();
		$txn->commit();

		$this->assertNotFalse($result);
	}

	public function test_insertOrIgnore_new_record_transitions_to_state_loaded(): void
	{
		$record = new IbmUpsertTestRecord();
		$record->username = 'alice';
		$record->score = 10;

		$this->assertSame(TActiveRecord::STATE_NEW, $record->getRecordState(), 'should start STATE_NEW');

		$txn = static::$conn->beginTransaction();
		$record->insertOrIgnore();
		$txn->commit();

		$this->assertSame(TActiveRecord::STATE_LOADED, $record->getRecordState());
	}

	public function test_insertOrIgnore_new_record_stores_data_in_db(): void
	{
		$record = new IbmUpsertTestRecord();
		$record->username = 'alice';
		$record->score = 42;

		$txn = static::$conn->beginTransaction();
		$record->insertOrIgnore();
		$txn->commit();

		$found = IbmUpsertTestRecord::finder()->findByPk('alice');
		$this->assertNotNull($found);
		$this->assertSame('alice', $found->username);
		$this->assertSame(42, (int) $found->score);
	}

	// -----------------------------------------------------------------------
	// Duplicate key — conflict silently ignored
	// -----------------------------------------------------------------------

	public function test_insertOrIgnore_duplicate_returns_false(): void
	{
		$first = new IbmUpsertTestRecord();
		$first->username = 'alice';
		$first->score = 10;
		$txn = static::$conn->beginTransaction();
		$first->insertOrIgnore();
		$txn->commit();

		$duplicate = new IbmUpsertTestRecord();
		$duplicate->username = 'alice';
		$duplicate->score = 99;
		$txn = static::$conn->beginTransaction();
		$result = $duplicate->insertOrIgnore();
		$txn->commit();

		$this->assertFalse($result);
	}

	public function test_insertOrIgnore_conflict_leaves_state_new(): void
	{
		$first = new IbmUpsertTestRecord();
		$first->username = 'alice';
		$first->score = 10;
		$txn = static::$conn->beginTransaction();
		$first->insertOrIgnore();
		$txn->commit();

		$duplicate = new IbmUpsertTestRecord();
		$duplicate->username = 'alice';
		$duplicate->score = 99;
		$txn = static::$conn->beginTransaction();
		$duplicate->insertOrIgnore();
		$txn->commit();

		$this->assertSame(TActiveRecord::STATE_NEW, $duplicate->getRecordState());
	}

	public function test_insertOrIgnore_conflict_does_not_overwrite_existing_row(): void
	{
		$first = new IbmUpsertTestRecord();
		$first->username = 'alice';
		$first->score = 10;
		$txn = static::$conn->beginTransaction();
		$first->insertOrIgnore();
		$txn->commit();

		$duplicate = new IbmUpsertTestRecord();
		$duplicate->username = 'alice';
		$duplicate->score = 99;
		$txn = static::$conn->beginTransaction();
		$duplicate->insertOrIgnore();
		$txn->commit();

		$found = IbmUpsertTestRecord::finder()->findByPk('alice');
		$this->assertSame(10, (int) $found->score, 'original score must be unchanged');
	}

	public function test_insertOrIgnore_fires_oninsert_event(): void
	{
		$record = new IbmUpsertTestRecord();
		$record->username = 'alice';
		$record->score = 10;

		$eventFired = false;
		$record->OnInsert[] = function ($sender, $param) use (&$eventFired): void {
			$this->assertInstanceOf(TActiveRecordChangeEventParameter::class, $param);
			$eventFired = true;
		};

		$txn = static::$conn->beginTransaction();
		$record->insertOrIgnore();
		$txn->commit();

		$this->assertTrue($eventFired, 'OnInsert event was not fired');
	}

	public function test_insertOrIgnore_oninsert_can_veto(): void
	{
		$record = new IbmUpsertTestRecord();
		$record->username = 'alice';
		$record->score = 10;

		$record->OnInsert[] = function ($sender, $param): void {
			$param->setIsValid(false);
		};

		// Veto fires before the MERGE is issued — no transaction required.
		$result = $record->insertOrIgnore();

		$this->assertFalse($result);
	}
}
