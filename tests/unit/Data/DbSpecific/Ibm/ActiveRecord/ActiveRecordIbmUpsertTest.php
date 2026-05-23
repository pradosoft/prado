<?php

require_once(__DIR__ . '/../../../../PradoUnit.php');
require_once(__DIR__ . '/records/IbmUpsertTestRecord.php');

use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Data\ActiveRecord\TActiveRecordChangeEventParameter;
use Prado\Data\TDbConnection;

/**
 * ActiveRecordIbmUpsertTest — integration tests for {@see TActiveRecord::upsert()} on IBM DB2.
 *
 * IBM DB2's upsert_test table uses `username` as the PK (no auto-increment id).
 * upsert() returns true (not an integer ID) on success.
 *
 * IBM DB2 uses MERGE for insertOrIgnore/upsert, which requires an active explicit
 * transaction.  Every test that calls upsert() wraps the call(s) in an explicit
 * transaction so that TIbmCommandBuilder does not throw.
 *
 * Requires: prado_unitest IBM DB2 database with the `upsert_test` table
 *   (see tests/initdb_ibm.sql).
 */
class ActiveRecordIbmUpsertTest extends PHPUnit\Framework\TestCase
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
	// Insert new record
	// -----------------------------------------------------------------------

	public function test_upsert_new_record_populates_pk_field(): void
	{
		$record = new IbmUpsertTestRecord();
		$record->username = 'alice';
		$record->score = 10;

		$txn = static::$conn->beginTransaction();
		$record->upsert();
		$txn->commit();

		$this->assertNotNull($record->username);
		$this->assertSame('alice', $record->username);
	}

	public function test_upsert_new_record_transitions_to_state_loaded(): void
	{
		$record = new IbmUpsertTestRecord();
		$record->username = 'alice';
		$record->score = 10;

		$this->assertSame(TActiveRecord::STATE_NEW, $record->getRecordState(), 'should start STATE_NEW');

		$txn = static::$conn->beginTransaction();
		$record->upsert();
		$txn->commit();

		$this->assertSame(TActiveRecord::STATE_LOADED, $record->getRecordState());
	}

	public function test_upsert_new_record_stores_data_in_db(): void
	{
		$record = new IbmUpsertTestRecord();
		$record->username = 'alice';
		$record->score = 42;

		$txn = static::$conn->beginTransaction();
		$record->upsert();
		$txn->commit();

		$found = IbmUpsertTestRecord::finder()->findByPk('alice');
		$this->assertNotNull($found);
		$this->assertSame('alice', $found->username);
		$this->assertSame(42, (int) $found->score);
	}

	public function test_upsert_new_record_returns_truthy(): void
	{
		$record = new IbmUpsertTestRecord();
		$record->username = 'alice';
		$record->score = 10;

		$txn = static::$conn->beginTransaction();
		$result = $record->upsert();
		$txn->commit();

		$this->assertNotFalse($result);
	}

	// -----------------------------------------------------------------------
	// Conflict → update existing row
	// -----------------------------------------------------------------------

	public function test_upsert_conflict_updates_existing_row(): void
	{
		$original = new IbmUpsertTestRecord();
		$original->username = 'alice';
		$original->score = 10;
		$txn = static::$conn->beginTransaction();
		$original->upsert();
		$txn->commit();

		$update = new IbmUpsertTestRecord();
		$update->username = 'alice';
		$update->score = 99;
		$txn = static::$conn->beginTransaction();
		$update->upsert();
		$txn->commit();

		$found = IbmUpsertTestRecord::finder()->findByPk('alice');
		$this->assertSame(99, (int) $found->score);
	}

	public function test_upsert_conflict_returns_truthy(): void
	{
		$original = new IbmUpsertTestRecord();
		$original->username = 'alice';
		$original->score = 10;
		$txn = static::$conn->beginTransaction();
		$original->upsert();
		$txn->commit();

		$update = new IbmUpsertTestRecord();
		$update->username = 'alice';
		$update->score = 99;
		$txn = static::$conn->beginTransaction();
		$result = $update->upsert();
		$txn->commit();

		$this->assertNotFalse($result);
	}

	public function test_upsert_conflict_does_not_create_duplicate_rows(): void
	{
		$original = new IbmUpsertTestRecord();
		$original->username = 'alice';
		$original->score = 10;
		$txn = static::$conn->beginTransaction();
		$original->upsert();
		$txn->commit();

		$update = new IbmUpsertTestRecord();
		$update->username = 'alice';
		$update->score = 99;
		$txn = static::$conn->beginTransaction();
		$update->upsert();
		$txn->commit();

		$count = (int) static::$conn->createCommand('SELECT COUNT(*) FROM upsert_test')->queryScalar();
		$this->assertSame(1, $count);
	}

	// -----------------------------------------------------------------------
	// $updateData parameter
	// -----------------------------------------------------------------------

	public function test_upsert_null_updateData_updates_all_non_pk_columns(): void
	{
		static::$conn->createCommand(
			"INSERT INTO upsert_test (username, score) VALUES ('alice', 10)"
		)->execute();

		$update = new IbmUpsertTestRecord();
		$update->username = 'alice';
		$update->score = 88;
		$txn = static::$conn->beginTransaction();
		$update->upsert(null, ['username']);
		$txn->commit();

		$found = IbmUpsertTestRecord::finder()->findByPk('alice');
		$this->assertSame(88, (int) $found->score);
	}

	public function test_upsert_empty_updateData_does_not_update_on_conflict(): void
	{
		static::$conn->createCommand(
			"INSERT INTO upsert_test (username, score) VALUES ('alice', 10)"
		)->execute();

		$update = new IbmUpsertTestRecord();
		$update->username = 'alice';
		$update->score = 99;
		$txn = static::$conn->beginTransaction();
		$update->upsert([], ['username']);
		$txn->commit();

		$found = IbmUpsertTestRecord::finder()->findByPk('alice');
		$this->assertSame(10, (int) $found->score, 'score must not change when updateData is empty');
	}

	// -----------------------------------------------------------------------
	// resolveUpdateData modes
	// -----------------------------------------------------------------------

	public function test_upsert_column_name_list_updateData_updates_from_record(): void
	{
		static::$conn->createCommand(
			"INSERT INTO upsert_test (username, score) VALUES ('alice', 10)"
		)->execute();

		$update = new IbmUpsertTestRecord();
		$update->username = 'alice';
		$update->score = 77;
		$txn = static::$conn->beginTransaction();
		$update->upsert(['score'], ['username']);
		$txn->commit();

		$found = IbmUpsertTestRecord::finder()->findByPk('alice');
		$this->assertSame(77, (int) $found->score);
	}

	public function test_upsert_explicit_value_updateData_overrides_value(): void
	{
		static::$conn->createCommand(
			"INSERT INTO upsert_test (username, score) VALUES ('alice', 10)"
		)->execute();

		$update = new IbmUpsertTestRecord();
		$update->username = 'alice';
		$update->score = 55;
		$txn = static::$conn->beginTransaction();
		$update->upsert(['score' => 99], ['username']);
		$txn->commit();

		$found = IbmUpsertTestRecord::finder()->findByPk('alice');
		$this->assertSame(99, (int) $found->score);
	}

	public function test_upsert_mixed_updateData(): void
	{
		static::$conn->createCommand(
			"INSERT INTO upsert_test (username, score) VALUES ('alice', 10)"
		)->execute();

		$update = new IbmUpsertTestRecord();
		$update->username = 'alice';
		$update->score = 42;
		// score from record (int-keyed), score is 42 so we also pass an explicit value
		$txn = static::$conn->beginTransaction();
		$update->upsert(['score' => 42], ['username']);
		$txn->commit();

		$found = IbmUpsertTestRecord::finder()->findByPk('alice');
		$this->assertSame(42, (int) $found->score);
	}

	// -----------------------------------------------------------------------
	// Unrelated rows are not affected
	// -----------------------------------------------------------------------

	public function test_upsert_does_not_affect_other_rows(): void
	{
		static::$conn->createCommand(
			"INSERT INTO upsert_test (username, score) VALUES ('alice', 10)"
		)->execute();
		static::$conn->createCommand(
			"INSERT INTO upsert_test (username, score) VALUES ('bob', 20)"
		)->execute();

		$update = new IbmUpsertTestRecord();
		$update->username = 'alice';
		$update->score = 99;
		$txn = static::$conn->beginTransaction();
		$update->upsert();
		$txn->commit();

		$bob = IbmUpsertTestRecord::finder()->findByPk('bob');
		$this->assertSame(20, (int) $bob->score, 'bob must be unaffected');
	}

	// -----------------------------------------------------------------------
	// OnInsert event
	// -----------------------------------------------------------------------

	public function test_upsert_fires_oninsert_event_on_insert(): void
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
		$record->upsert();
		$txn->commit();

		$this->assertTrue($eventFired, 'OnInsert event was not fired on insert path');
	}

	public function test_upsert_fires_oninsert_event_on_conflict_update(): void
	{
		static::$conn->createCommand(
			"INSERT INTO upsert_test (username, score) VALUES ('alice', 10)"
		)->execute();

		$update = new IbmUpsertTestRecord();
		$update->username = 'alice';
		$update->score = 99;

		$eventFired = false;
		$update->OnInsert[] = function ($sender, $param) use (&$eventFired): void {
			$eventFired = true;
		};

		$txn = static::$conn->beginTransaction();
		$update->upsert();
		$txn->commit();

		$this->assertTrue($eventFired, 'OnInsert event must fire on the update (conflict) path too');
	}

	public function test_upsert_oninsert_can_veto_the_operation(): void
	{
		$record = new IbmUpsertTestRecord();
		$record->username = 'alice';
		$record->score = 10;

		$record->OnInsert[] = function ($sender, $param): void {
			$param->setIsValid(false);
		};

		// Veto fires before the MERGE is issued — no transaction required.
		$result = $record->upsert();

		$this->assertFalse($result);
	}
}
