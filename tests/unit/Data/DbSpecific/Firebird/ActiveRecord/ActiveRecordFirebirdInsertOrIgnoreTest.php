<?php

require_once(__DIR__ . '/../../../../PradoUnit.php');
require_once(__DIR__ . '/records/FirebirdUpsertTestRecord.php');

use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Data\ActiveRecord\TActiveRecordChangeEventParameter;
use Prado\Data\TDbConnection;

/**
 * ActiveRecordFirebirdInsertOrIgnoreTest — integration tests for {@see TActiveRecord::insertOrIgnore()} on Firebird.
 *
 * Firebird's upsert_test table uses `username` as the PK (no auto-increment id).
 *
 * Requires: prado_unitest SQL Server database with the `upsert_test` table
 *   (see tests/initdb_firebird.sql).
 */
class ActiveRecordFirebirdInsertOrIgnoreTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected static ?TDbConnection $conn = null;
	protected static ?\Prado\Data\TDbTransaction $txn = null;

	protected function getPradoUnitSetup(): ?string
	{
		return 'setupFirebirdConnection';
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
		// pdo_firebird keeps an implicit transaction alive after each auto-committed
		// statement. Committing it here resets the internal handle so that our
		// explicit beginTransaction() below succeeds without "already active" errors.
		try { static::$conn->getPdoInstance()->commit(); } catch (\Exception $e) {}
		// TFirebirdCommandBuilder::createInsertOrIgnoreCommand() requires an active
		// transaction — begin one that covers the entire test method.
		static::$txn = static::$conn->beginTransaction();
	}

	protected function tearDown(): void
	{
		if (static::$txn !== null) {
			try { static::$txn->rollback(); } catch (\Exception $e) {}
			static::$txn = null;
		}
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
		$record = new FirebirdUpsertTestRecord();
		$record->username = 'alice';
		$record->score = 10;

		$result = $record->insertOrIgnore();

		$this->assertNotFalse($result);
	}

	public function test_insertOrIgnore_new_record_transitions_to_state_loaded(): void
	{
		$record = new FirebirdUpsertTestRecord();
		$record->username = 'alice';
		$record->score = 10;

		$this->assertSame(TActiveRecord::STATE_NEW, $record->getRecordState(), 'should start STATE_NEW');

		$record->insertOrIgnore();

		$this->assertSame(TActiveRecord::STATE_LOADED, $record->getRecordState());
	}

	public function test_insertOrIgnore_new_record_stores_data_in_db(): void
	{
		$record = new FirebirdUpsertTestRecord();
		$record->username = 'alice';
		$record->score = 42;

		$record->insertOrIgnore();

		$found = FirebirdUpsertTestRecord::finder()->findByPk('alice');
		$this->assertNotNull($found);
		$this->assertSame('alice', $found->username);
		$this->assertSame(42, (int) $found->score);
	}

	// -----------------------------------------------------------------------
	// Duplicate key — conflict silently ignored
	// -----------------------------------------------------------------------

	public function test_insertOrIgnore_duplicate_returns_false(): void
	{
		$first = new FirebirdUpsertTestRecord();
		$first->username = 'alice';
		$first->score = 10;
		$first->insertOrIgnore();

		$duplicate = new FirebirdUpsertTestRecord();
		$duplicate->username = 'alice';
		$duplicate->score = 99;

		$result = $duplicate->insertOrIgnore();

		$this->assertFalse($result);
	}

	public function test_insertOrIgnore_conflict_leaves_state_new(): void
	{
		$first = new FirebirdUpsertTestRecord();
		$first->username = 'alice';
		$first->score = 10;
		$first->insertOrIgnore();

		$duplicate = new FirebirdUpsertTestRecord();
		$duplicate->username = 'alice';
		$duplicate->score = 99;
		$duplicate->insertOrIgnore();

		$this->assertSame(TActiveRecord::STATE_NEW, $duplicate->getRecordState());
	}

	public function test_insertOrIgnore_conflict_does_not_overwrite_existing_row(): void
	{
		$first = new FirebirdUpsertTestRecord();
		$first->username = 'alice';
		$first->score = 10;
		$first->insertOrIgnore();

		$duplicate = new FirebirdUpsertTestRecord();
		$duplicate->username = 'alice';
		$duplicate->score = 99;
		$duplicate->insertOrIgnore();

		$found = FirebirdUpsertTestRecord::finder()->findByPk('alice');
		$this->assertSame(10, (int) $found->score, 'original score must be unchanged');
	}

	public function test_insertOrIgnore_fires_oninsert_event(): void
	{
		$record = new FirebirdUpsertTestRecord();
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
		$record = new FirebirdUpsertTestRecord();
		$record->username = 'alice';
		$record->score = 10;

		$record->OnInsert[] = function ($sender, $param): void {
			$param->setIsValid(false);
		};

		$result = $record->insertOrIgnore();

		$this->assertFalse($result);
	}
}
