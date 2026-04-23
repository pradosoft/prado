<?php

require_once(__DIR__ . '/../../PradoUnit.php');
require_once(__DIR__ . '/records/UpsertTestRecord.php');
require_once(__DIR__ . '/records/UserRecord.php');

use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Data\ActiveRecord\TActiveRecordChangeEventParameter;
use Prado\Data\TDbConnection;

/**
 * ActiveRecordUpsertTest — integration tests for {@see TActiveRecord::upsert()}.
 *
 * Verifies the full TActiveRecord-layer behaviour:
 *   - Insert path: return value, PK population, STATE_LOADED, DB contents
 *   - Update path: conflict updates the existing row, returns truthy, STATE_LOADED
 *   - $updateData parameter: controls which columns are updated on conflict
 *   - $conflictColumns parameter: passed through to the command builder
 *   - Empty $updateData falls back to insert-or-ignore semantics
 *   - OnInsert event is raised (for both insert and update paths) and can veto
 *   - Unrelated rows are not affected
 *
 * Requires: prado_unitest MySQL database with the `upsert_test` table
 *   (see tests/initdb_mysql.sql).
 */
class ActiveRecordUpsertTest extends PHPUnit\Framework\TestCase
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
	// Insert new record
	// -----------------------------------------------------------------------

	public function test_upsert_new_record_returns_last_insert_id(): void
	{
		$record = new UpsertTestRecord();
		$record->username = 'alice';
		$record->score = 10;

		$result = $record->upsert();

		$this->assertNotFalse($result);
		$this->assertGreaterThan(0, (int) $result);
	}

	public function test_upsert_new_record_populates_pk_field(): void
	{
		$record = new UpsertTestRecord();
		$record->username = 'alice';
		$record->score = 10;

		$record->upsert();

		$this->assertNotNull($record->id);
		$this->assertGreaterThan(0, (int) $record->id);
	}

	public function test_upsert_new_record_transitions_to_state_loaded(): void
	{
		$record = new UpsertTestRecord();
		$record->username = 'alice';
		$record->score = 10;

		$this->assertSame(TActiveRecord::STATE_NEW, $record->getRecordState(), 'should start STATE_NEW');

		$record->upsert();

		$this->assertSame(TActiveRecord::STATE_LOADED, $record->getRecordState());
	}

	public function test_upsert_new_record_stores_data_in_db(): void
	{
		$record = new UpsertTestRecord();
		$record->username = 'alice';
		$record->score = 42;

		$record->upsert();

		$found = UpsertTestRecord::finder()->find('username = ?', 'alice');
		$this->assertNotNull($found);
		$this->assertSame('alice', $found->username);
		$this->assertSame(42, (int) $found->score);
	}

	// -----------------------------------------------------------------------
	// Conflict → update existing row
	// -----------------------------------------------------------------------

	public function test_upsert_conflict_updates_existing_row(): void
	{
		$original = new UpsertTestRecord();
		$original->username = 'alice';
		$original->score = 10;
		$original->upsert();

		$update = new UpsertTestRecord();
		$update->username = 'alice';
		$update->score = 99;
		$update->upsert();

		$found = UpsertTestRecord::finder()->find('username = ?', 'alice');
		$this->assertSame(99, (int) $found->score);
	}

	public function test_upsert_conflict_returns_truthy(): void
	{
		$original = new UpsertTestRecord();
		$original->username = 'alice';
		$original->score = 10;
		$original->upsert();

		$update = new UpsertTestRecord();
		$update->username = 'alice';
		$update->score = 99;

		$result = $update->upsert();

		$this->assertNotFalse($result);
	}

	public function test_upsert_conflict_transitions_to_state_loaded(): void
	{
		$original = new UpsertTestRecord();
		$original->username = 'alice';
		$original->score = 10;
		$original->upsert();

		$update = new UpsertTestRecord();
		$update->username = 'alice';
		$update->score = 99;

		$this->assertSame(TActiveRecord::STATE_NEW, $update->getRecordState());

		$update->upsert();

		$this->assertSame(TActiveRecord::STATE_LOADED, $update->getRecordState());
	}

	public function test_upsert_conflict_does_not_create_duplicate_rows(): void
	{
		$original = new UpsertTestRecord();
		$original->username = 'alice';
		$original->score = 10;
		$original->upsert();

		$update = new UpsertTestRecord();
		$update->username = 'alice';
		$update->score = 99;
		$update->upsert();

		$count = (int) static::$conn->createCommand('SELECT COUNT(*) FROM `upsert_test`')->queryScalar();
		$this->assertSame(1, $count);
	}

	// -----------------------------------------------------------------------
	// $updateData parameter
	// -----------------------------------------------------------------------

	public function test_upsert_null_updateData_updates_all_non_pk_columns(): void
	{
		static::$conn->createCommand(
			"INSERT INTO `upsert_test` (`username`, `score`) VALUES ('alice', 10)"
		)->execute();

		$update = new UpsertTestRecord();
		$update->username = 'alice';
		$update->score = 88;
		$update->upsert(null, ['username']);

		$found = UpsertTestRecord::finder()->find('username = ?', 'alice');
		$this->assertSame(88, (int) $found->score);
	}

	public function test_upsert_explicit_updateData_only_updates_listed_columns(): void
	{
		static::$conn->createCommand(
			"INSERT INTO `upsert_test` (`username`, `score`) VALUES ('alice', 10)"
		)->execute();

		$update = new UpsertTestRecord();
		$update->username = 'alice';
		$update->score = 55;
		$update->upsert(['score' => 55], ['username']);

		$found = UpsertTestRecord::finder()->find('username = ?', 'alice');
		$this->assertSame(55, (int) $found->score);
		$this->assertSame('alice', $found->username);
	}

	public function test_upsert_empty_updateData_does_not_update_on_conflict(): void
	{
		// Empty updateData degrades to INSERT IGNORE semantics — no update on conflict.
		static::$conn->createCommand(
			"INSERT INTO `upsert_test` (`username`, `score`) VALUES ('alice', 10)"
		)->execute();

		$update = new UpsertTestRecord();
		$update->username = 'alice';
		$update->score = 99;
		$update->upsert([], ['username']);

		$found = UpsertTestRecord::finder()->find('username = ?', 'alice');
		$this->assertSame(10, (int) $found->score, 'score must not change when updateData is empty');
	}

	// -----------------------------------------------------------------------
	// Unrelated rows are not affected
	// -----------------------------------------------------------------------

	public function test_upsert_does_not_affect_other_rows(): void
	{
		static::$conn->createCommand(
			"INSERT INTO `upsert_test` (`username`, `score`) VALUES ('alice', 10), ('bob', 20)"
		)->execute();

		$update = new UpsertTestRecord();
		$update->username = 'alice';
		$update->score = 99;
		$update->upsert();

		$bob = UpsertTestRecord::finder()->find('username = ?', 'bob');
		$this->assertSame(20, (int) $bob->score, 'bob must be unaffected');
	}

	// -----------------------------------------------------------------------
	// OnInsert event
	// -----------------------------------------------------------------------

	public function test_upsert_fires_oninsert_event_on_insert(): void
	{
		$record = new UpsertTestRecord();
		$record->username = 'alice';
		$record->score = 10;

		$eventFired = false;
		$record->OnInsert[] = function ($sender, $param) use (&$eventFired): void {
			$this->assertInstanceOf(TActiveRecordChangeEventParameter::class, $param);
			$eventFired = true;
		};

		$record->upsert();

		$this->assertTrue($eventFired, 'OnInsert event was not fired on insert path');
	}

	public function test_upsert_fires_oninsert_event_on_conflict_update(): void
	{
		static::$conn->createCommand(
			"INSERT INTO `upsert_test` (`username`, `score`) VALUES ('alice', 10)"
		)->execute();

		$update = new UpsertTestRecord();
		$update->username = 'alice';
		$update->score = 99;

		$eventFired = false;
		$update->OnInsert[] = function ($sender, $param) use (&$eventFired): void {
			$eventFired = true;
		};

		$update->upsert();

		$this->assertTrue($eventFired, 'OnInsert event must fire on the update (conflict) path too');
	}

	public function test_upsert_oninsert_can_veto_the_operation(): void
	{
		$record = new UpsertTestRecord();
		$record->username = 'alice';
		$record->score = 10;

		$record->OnInsert[] = function ($sender, $param): void {
			$param->setIsValid(false);
		};

		$result = $record->upsert();

		$this->assertFalse($result);
	}

	public function test_upsert_veto_leaves_state_new(): void
	{
		$record = new UpsertTestRecord();
		$record->username = 'alice';
		$record->score = 10;

		$record->OnInsert[] = function ($sender, $param): void {
			$param->setIsValid(false);
		};

		$record->upsert();

		$this->assertSame(TActiveRecord::STATE_NEW, $record->getRecordState());
	}

	public function test_upsert_veto_writes_nothing_to_db(): void
	{
		$record = new UpsertTestRecord();
		$record->username = 'alice';
		$record->score = 10;

		$record->OnInsert[] = function ($sender, $param): void {
			$param->setIsValid(false);
		};

		$record->upsert();

		$count = (int) static::$conn->createCommand('SELECT COUNT(*) FROM `upsert_test`')->queryScalar();
		$this->assertSame(0, $count);
	}

	// -----------------------------------------------------------------------
	// String (non-auto-increment) PK — uses the existing `Users` table
	// -----------------------------------------------------------------------

	public function test_upsert_string_pk_new_record_returns_truthy(): void
	{
		$user = new UserRecord();
		$user->username = 'upsertTestUser';
		$user->password = md5('pass');
		$user->email = 'upsert@example.com';

		$result = $user->upsert();

		$this->assertNotFalse($result);

		// cleanup
		UserRecord::finder()->findByPk('upsertTestUser')?->delete();
	}

	public function test_upsert_string_pk_conflict_updates_row(): void
	{
		// Upsert over the seeded 'admin' row and verify the email is updated.
		$adminOriginal = UserRecord::finder()->findByPk('admin');
		$this->assertNotNull($adminOriginal);
		$originalEmail = $adminOriginal->email;

		$user = new UserRecord();
		$user->username = 'admin';
		$user->password = $adminOriginal->password;
		$user->email = 'updated_by_upsert@example.com';
		$user->first_name = $adminOriginal->first_name;
		$user->last_name = $adminOriginal->last_name;
		$user->active = $adminOriginal->active;
		$user->department_id = $adminOriginal->department_id;

		$result = $user->upsert();

		$this->assertNotFalse($result);

		$found = UserRecord::finder()->findByPk('admin');
		$this->assertSame('updated_by_upsert@example.com', $found->email);

		// restore original email
		$found->email = $originalEmail;
		$found->save();
	}
}
