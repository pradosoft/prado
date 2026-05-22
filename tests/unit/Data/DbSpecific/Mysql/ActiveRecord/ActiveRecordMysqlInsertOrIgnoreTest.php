<?php

require_once(__DIR__ . '/../../../../PradoUnit.php');
require_once(__DIR__ . '/records/MysqlUpsertTestRecord.php');
require_once(__DIR__ . '/records/UserRecord.php');

use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Data\ActiveRecord\TActiveRecordChangeEventParameter;
use Prado\Data\TDbConnection;

/**
 * ActiveRecordMysqlInsertOrIgnoreTest — integration tests for {@see TActiveRecord::insertOrIgnore()} on MySQL.
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
class ActiveRecordMysqlInsertOrIgnoreTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected static ?TDbConnection $conn = null;

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupMysqlConnection';
	}

	protected function getDatabaseName(): ?string
	{
		return 'prado_unitest';
	}

	protected function getIsForActiveRecord(): bool
	{
		return true;
	}

	protected function getTestTables(): array
	{
		return ['upsert_test', 'Users'];
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
		$record = new MysqlUpsertTestRecord();
		$record->username = 'alice';
		$record->score = 10;

		$result = $record->insertOrIgnore();

		$this->assertNotFalse($result);
		$this->assertGreaterThan(0, (int) $result);
	}

	public function test_insertOrIgnore_populates_pk_field_after_insert(): void
	{
		$record = new MysqlUpsertTestRecord();
		$record->username = 'alice';
		$record->score = 10;

		$record->insertOrIgnore();

		$this->assertNotNull($record->id);
		$this->assertGreaterThan(0, (int) $record->id);
	}

	public function test_insertOrIgnore_new_record_transitions_to_state_loaded(): void
	{
		$record = new MysqlUpsertTestRecord();
		$record->username = 'alice';
		$record->score = 10;

		$this->assertSame(TActiveRecord::STATE_NEW, $record->getRecordState(), 'should start STATE_NEW');

		$record->insertOrIgnore();

		$this->assertSame(TActiveRecord::STATE_LOADED, $record->getRecordState());
	}

	public function test_insertOrIgnore_new_record_stores_data_in_db(): void
	{
		$record = new MysqlUpsertTestRecord();
		$record->username = 'alice';
		$record->score = 42;

		$record->insertOrIgnore();

		$found = MysqlUpsertTestRecord::finder()->find('username = ?', 'alice');
		$this->assertNotNull($found);
		$this->assertSame('alice', $found->username);
		$this->assertSame(42, (int) $found->score);
	}

	public function test_insertOrIgnore_successive_new_records_return_incrementing_ids(): void
	{
		$alice = new MysqlUpsertTestRecord();
		$alice->username = 'alice';
		$alice->score = 1;
		$idAlice = (int) $alice->insertOrIgnore();

		$bob = new MysqlUpsertTestRecord();
		$bob->username = 'bob';
		$bob->score = 2;
		$idBob = (int) $bob->insertOrIgnore();

		$this->assertGreaterThan($idAlice, $idBob);
	}

	// -----------------------------------------------------------------------
	// Duplicate key — conflict silently ignored
	// -----------------------------------------------------------------------

	public function test_insertOrIgnore_duplicate_returns_false(): void
	{
		$first = new MysqlUpsertTestRecord();
		$first->username = 'alice';
		$first->score = 10;
		$first->insertOrIgnore();

		$duplicate = new MysqlUpsertTestRecord();
		$duplicate->username = 'alice';
		$duplicate->score = 99;

		$result = $duplicate->insertOrIgnore();

		$this->assertFalse($result);
	}

	public function test_insertOrIgnore_conflict_leaves_state_new(): void
	{
		$first = new MysqlUpsertTestRecord();
		$first->username = 'alice';
		$first->score = 10;
		$first->insertOrIgnore();

		$duplicate = new MysqlUpsertTestRecord();
		$duplicate->username = 'alice';
		$duplicate->score = 99;
		$duplicate->insertOrIgnore();

		$this->assertSame(TActiveRecord::STATE_NEW, $duplicate->getRecordState());
	}

	public function test_insertOrIgnore_conflict_does_not_overwrite_existing_row(): void
	{
		$first = new MysqlUpsertTestRecord();
		$first->username = 'alice';
		$first->score = 10;
		$first->insertOrIgnore();

		$duplicate = new MysqlUpsertTestRecord();
		$duplicate->username = 'alice';
		$duplicate->score = 99;
		$duplicate->insertOrIgnore();

		$found = MysqlUpsertTestRecord::finder()->find('username = ?', 'alice');
		$this->assertSame(10, (int) $found->score, 'original score must be unchanged');
	}

	public function test_insertOrIgnore_fires_oninsert_event(): void
	{
		$record = new MysqlUpsertTestRecord();
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

	public function test_insertOrIgnore_oninsert_can_veto(): void
	{
		$record = new MysqlUpsertTestRecord();
		$record->username = 'alice';
		$record->score = 10;

		$record->OnInsert[] = function ($sender, $param): void {
			$param->setIsValid(false);
		};

		$result = $record->insertOrIgnore();

		$this->assertFalse($result);
	}

	// -----------------------------------------------------------------------
	// String (non-auto-increment) PK — uses the existing `Users` table
	// -----------------------------------------------------------------------

	public function test_insertOrIgnore_string_pk_new_record_returns_truthy(): void
	{
		$user = new MysqlUserRecord();
		$user->username = 'insertIgnoreTestUser';
		$user->password = md5('pass');
		$user->email = 'test@example.com';

		$result = $user->insertOrIgnore();

		$this->assertNotFalse($result);

		// cleanup
		MysqlUserRecord::finder()->findByPk('insertIgnoreTestUser')?->delete();
	}

	public function test_insertOrIgnore_string_pk_duplicate_returns_false(): void
	{
		// 'admin' is seeded by initdb_mysql.sql
		$user = new MysqlUserRecord();
		$user->username = 'admin';
		$user->password = md5('other');
		$user->email = 'other@example.com';

		$result = $user->insertOrIgnore();

		$this->assertFalse($result);
	}

	public function test_insertOrIgnore_string_pk_duplicate_does_not_overwrite(): void
	{
		$user = new MysqlUserRecord();
		$user->username = 'admin';
		$user->email = 'overwrite@example.com';

		$user->insertOrIgnore();

		$found = MysqlUserRecord::finder()->findByPk('admin');
		$this->assertNotSame('overwrite@example.com', $found->email, 'original email must be unchanged');
	}
}
