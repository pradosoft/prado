<?php

require_once(__DIR__ . '/../../PradoUnit.php');
require_once(__DIR__ . '/records/UpsertTestRecord.php');
require_once(__DIR__ . '/records/UserRecord.php');

use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Data\ActiveRecord\TActiveRecordChangeEventParameter;
use Prado\Data\TDbConnection;

/**
 * ActiveRecordInsertOrIgnoreTest — integration tests for {@see TActiveRecord::insertOrIgnore()}.
 *
 * Verifies the full TActiveRecord-layer behaviour:
 *   - Return value (last-insert ID / true / false)
 *   - Auto-increment PK population via updatePostInsert()
 *   - Record-state transitions (STATE_NEW → STATE_LOADED on success; stays STATE_NEW on conflict)
 *   - OnInsert event is raised and can veto the operation
 *   - Database contents are correct after each call
 *
 * Requires: prado_unitest MySQL database with the `upsert_test` table
 *   (see tests/initdb_mysql.sql).
 */
class ActiveRecordInsertOrIgnoreTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected static ?TDbConnection $conn = null;

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
		static::$conn->createCommand('DELETE FROM `upsert_test`')->execute();
		static::$conn->createCommand('ALTER TABLE `upsert_test` AUTO_INCREMENT = 1')->execute();
	}

	public static function tearDownAfterClass(): void
	{
		if (static::$conn !== null) {
			static::$conn->Active = false;
			static::$conn = null;
		}
	}

	// -----------------------------------------------------------------------
	// New record — auto-increment PK
	// -----------------------------------------------------------------------

	public function test_insertOrIgnore_new_record_returns_last_insert_id(): void
	{
		$record = new UpsertTestRecord();
		$record->username = 'alice';
		$record->score = 10;

		$result = $record->insertOrIgnore();

		$this->assertNotFalse($result);
		$this->assertGreaterThan(0, (int) $result);
	}

	public function test_insertOrIgnore_populates_pk_field_after_insert(): void
	{
		$record = new UpsertTestRecord();
		$record->username = 'alice';
		$record->score = 10;

		$record->insertOrIgnore();

		$this->assertNotNull($record->id);
		$this->assertGreaterThan(0, (int) $record->id);
	}

	public function test_insertOrIgnore_new_record_transitions_to_state_loaded(): void
	{
		$record = new UpsertTestRecord();
		$record->username = 'alice';
		$record->score = 10;

		$this->assertSame(TActiveRecord::STATE_NEW, $record->getRecordState(), 'should start STATE_NEW');

		$record->insertOrIgnore();

		$this->assertSame(TActiveRecord::STATE_LOADED, $record->getRecordState());
	}

	public function test_insertOrIgnore_new_record_stores_data_in_db(): void
	{
		$record = new UpsertTestRecord();
		$record->username = 'alice';
		$record->score = 42;

		$record->insertOrIgnore();

		$found = UpsertTestRecord::finder()->find('username = ?', 'alice');
		$this->assertNotNull($found);
		$this->assertSame('alice', $found->username);
		$this->assertSame(42, (int) $found->score);
	}

	public function test_insertOrIgnore_successive_new_records_return_incrementing_ids(): void
	{
		$alice = new UpsertTestRecord();
		$alice->username = 'alice';
		$alice->score = 1;
		$idAlice = (int) $alice->insertOrIgnore();

		$bob = new UpsertTestRecord();
		$bob->username = 'bob';
		$bob->score = 2;
		$idBob = (int) $bob->insertOrIgnore();

		$this->assertGreaterThan($idAlice, $idBob);
	}

	// -----------------------------------------------------------------------
	// Duplicate key — conflict silently ignored
	// -----------------------------------------------------------------------

	public function test_insertOrIgnore_duplicate_username_returns_false(): void
	{
		$first = new UpsertTestRecord();
		$first->username = 'alice';
		$first->score = 10;
		$first->insertOrIgnore();

		$duplicate = new UpsertTestRecord();
		$duplicate->username = 'alice';
		$duplicate->score = 99;

		$result = $duplicate->insertOrIgnore();

		$this->assertFalse($result);
	}

	public function test_insertOrIgnore_conflict_leaves_state_new(): void
	{
		$first = new UpsertTestRecord();
		$first->username = 'alice';
		$first->score = 10;
		$first->insertOrIgnore();

		$duplicate = new UpsertTestRecord();
		$duplicate->username = 'alice';
		$duplicate->score = 99;
		$duplicate->insertOrIgnore();

		$this->assertSame(TActiveRecord::STATE_NEW, $duplicate->getRecordState());
	}

	public function test_insertOrIgnore_conflict_does_not_populate_pk(): void
	{
		$first = new UpsertTestRecord();
		$first->username = 'alice';
		$first->score = 10;
		$first->insertOrIgnore();

		$duplicate = new UpsertTestRecord();
		$duplicate->username = 'alice';
		$duplicate->score = 99;
		$duplicate->insertOrIgnore();

		$this->assertNull($duplicate->id);
	}

	public function test_insertOrIgnore_conflict_does_not_overwrite_existing_row(): void
	{
		$first = new UpsertTestRecord();
		$first->username = 'alice';
		$first->score = 10;
		$first->insertOrIgnore();

		$duplicate = new UpsertTestRecord();
		$duplicate->username = 'alice';
		$duplicate->score = 99;
		$duplicate->insertOrIgnore();

		$found = UpsertTestRecord::finder()->find('username = ?', 'alice');
		$this->assertSame(10, (int) $found->score, 'original score must be unchanged');
	}

	public function test_insertOrIgnore_conflict_does_not_increase_row_count(): void
	{
		$first = new UpsertTestRecord();
		$first->username = 'alice';
		$first->score = 10;
		$first->insertOrIgnore();

		$duplicate = new UpsertTestRecord();
		$duplicate->username = 'alice';
		$duplicate->score = 99;
		$duplicate->insertOrIgnore();

		$count = (int) static::$conn->createCommand('SELECT COUNT(*) FROM `upsert_test`')->queryScalar();
		$this->assertSame(1, $count);
	}

	// -----------------------------------------------------------------------
	// Mixed: conflict row then new row
	// -----------------------------------------------------------------------

	public function test_insertOrIgnore_non_conflicting_insert_after_conflict_succeeds(): void
	{
		$first = new UpsertTestRecord();
		$first->username = 'alice';
		$first->score = 10;
		$first->insertOrIgnore();

		$conflict = new UpsertTestRecord();
		$conflict->username = 'alice';
		$conflict->score = 99;
		$result1 = $conflict->insertOrIgnore();

		$bob = new UpsertTestRecord();
		$bob->username = 'bob';
		$bob->score = 20;
		$result2 = $bob->insertOrIgnore();

		$this->assertFalse($result1, 'conflict must return false');
		$this->assertNotFalse($result2, 'new row must succeed');
		$this->assertGreaterThan(0, (int) $result2);
	}

	public function test_insertOrIgnore_correct_values_after_mixed_operations(): void
	{
		$alice = new UpsertTestRecord();
		$alice->username = 'alice';
		$alice->score = 10;
		$alice->insertOrIgnore();

		$aliceDup = new UpsertTestRecord();
		$aliceDup->username = 'alice';
		$aliceDup->score = 99;
		$aliceDup->insertOrIgnore();

		$bob = new UpsertTestRecord();
		$bob->username = 'bob';
		$bob->score = 55;
		$bob->insertOrIgnore();

		$foundAlice = UpsertTestRecord::finder()->find('username = ?', 'alice');
		$foundBob = UpsertTestRecord::finder()->find('username = ?', 'bob');

		$this->assertSame(10, (int) $foundAlice->score, 'alice score must be unchanged');
		$this->assertSame(55, (int) $foundBob->score, 'bob score must be stored');
	}

	// -----------------------------------------------------------------------
	// OnInsert event
	// -----------------------------------------------------------------------

	public function test_insertOrIgnore_fires_oninsert_event(): void
	{
		$record = new UpsertTestRecord();
		$record->username = 'alice';
		$record->score = 10;

		$eventFired = false;
		$record->OnInsert[] = function ($sender, $param) use (&$eventFired): void {
			$this->assertInstanceOf(TActiveRecordChangeEventParameter::class, $param);
			$eventFired = true;
		};

		$record->insertOrIgnore();

		$this->assertTrue($eventFired, 'OnInsert event was not fired');
	}

	public function test_insertOrIgnore_fires_oninsert_even_when_conflict_occurs(): void
	{
		$first = new UpsertTestRecord();
		$first->username = 'alice';
		$first->score = 10;
		$first->insertOrIgnore();

		$duplicate = new UpsertTestRecord();
		$duplicate->username = 'alice';
		$duplicate->score = 99;

		$eventFired = false;
		$duplicate->OnInsert[] = function ($sender, $param) use (&$eventFired): void {
			$eventFired = true;
		};

		$duplicate->insertOrIgnore();

		$this->assertTrue($eventFired, 'OnInsert event must fire even when DB ignores the row');
	}

	public function test_insertOrIgnore_oninsert_can_veto_the_operation(): void
	{
		$record = new UpsertTestRecord();
		$record->username = 'alice';
		$record->score = 10;

		$record->OnInsert[] = function ($sender, $param): void {
			$param->setIsValid(false);
		};

		$result = $record->insertOrIgnore();

		$this->assertFalse($result);
	}

	public function test_insertOrIgnore_veto_leaves_state_new(): void
	{
		$record = new UpsertTestRecord();
		$record->username = 'alice';
		$record->score = 10;

		$record->OnInsert[] = function ($sender, $param): void {
			$param->setIsValid(false);
		};

		$record->insertOrIgnore();

		$this->assertSame(TActiveRecord::STATE_NEW, $record->getRecordState());
	}

	public function test_insertOrIgnore_veto_writes_nothing_to_db(): void
	{
		$record = new UpsertTestRecord();
		$record->username = 'alice';
		$record->score = 10;

		$record->OnInsert[] = function ($sender, $param): void {
			$param->setIsValid(false);
		};

		$record->insertOrIgnore();

		$count = (int) static::$conn->createCommand('SELECT COUNT(*) FROM `upsert_test`')->queryScalar();
		$this->assertSame(0, $count);
	}

	// -----------------------------------------------------------------------
	// String (non-auto-increment) PK — uses the existing `Users` table
	// -----------------------------------------------------------------------

	public function test_insertOrIgnore_string_pk_new_record_returns_truthy(): void
	{
		$user = new UserRecord();
		$user->username = 'insertIgnoreTestUser';
		$user->password = md5('pass');
		$user->email = 'test@example.com';

		$result = $user->insertOrIgnore();

		$this->assertNotFalse($result);

		// cleanup
		UserRecord::finder()->findByPk('insertIgnoreTestUser')?->delete();
	}

	public function test_insertOrIgnore_string_pk_duplicate_returns_false(): void
	{
		// 'admin' is seeded by initdb_mysql.sql
		$user = new UserRecord();
		$user->username = 'admin';
		$user->password = md5('other');
		$user->email = 'other@example.com';

		$result = $user->insertOrIgnore();

		$this->assertFalse($result);
	}

	public function test_insertOrIgnore_string_pk_duplicate_does_not_overwrite(): void
	{
		$user = new UserRecord();
		$user->username = 'admin';
		$user->email = 'overwrite@example.com';

		$user->insertOrIgnore();

		$found = UserRecord::finder()->findByPk('admin');
		$this->assertNotSame('overwrite@example.com', $found->email, 'original email must be unchanged');
	}
}
