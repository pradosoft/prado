<?php

require_once(__DIR__ . '/../../../../PradoUnit.php');

use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Data\TDbConnection;

/**
 * Concrete subclass of TActiveRecord for sleep/wakeup testing.
 * No real database table is needed for serialization unit tests.
 */
class SqliteSleepTestRecord extends TActiveRecord
{
	public const TABLE_NAME = 'sleep_test';

	public $id;
	public $name;
}

/**
 * Sleep / wakeup tests for TActiveRecord._getZappableSleepProps.
 *
 * TActiveRecord excludes the protected $_connection property from serialization
 * because live PDO connections cannot be serialized.  After unserialization the
 * record must be reconnectable by setting a new connection.
 */
class SqliteTActiveRecordSleepTest extends PHPUnit\Framework\TestCase
{
	// -----------------------------------------------------------------------
	//  _connection is always excluded
	// -----------------------------------------------------------------------

	public function testSqliteConnectionExcludedFromSleep(): void
	{
		$record = new SqliteSleepTestRecord();
		$props = $record->__sleep();
		// Protected property mangled name for _connection
		$this->assertNotContains("\0*\0_connection", $props);
	}

	public function testSqliteConnectionExcludedEvenWhenSet(): void
	{
		$record = new SqliteSleepTestRecord();
		// Set a connection on the record (inactive — no live DB needed)
		$conn = new TDbConnection('sqlite::memory:');
		$ref = new \ReflectionProperty(TActiveRecord::class, '_connection');
		$ref->setAccessible(true);
		$ref->setValue($record, $conn);

		$props = $record->__sleep();
		$this->assertNotContains("\0*\0_connection", $props);
	}

	// -----------------------------------------------------------------------
	//  Public fields and non-excluded props survive the round trip
	// -----------------------------------------------------------------------

	public function testSqlitePublicFieldsPreservedAfterRoundTrip(): void
	{
		$record = new SqliteSleepTestRecord();
		$record->id   = 7;
		$record->name = 'Alice';

		$restored = unserialize(serialize($record));

		$this->assertSame(7,       $restored->id);
		$this->assertSame('Alice', $restored->name);
	}

	public function testSqliteConnectionNullAfterRoundTrip(): void
	{
		$record = new SqliteSleepTestRecord();
		// Set a live-ish connection; it must be gone after unserialize
		$conn = new TDbConnection('sqlite::memory:');
		$ref = new \ReflectionProperty(TActiveRecord::class, '_connection');
		$ref->setAccessible(true);
		$ref->setValue($record, $conn);

		$restored = unserialize(serialize($record));

		$resRef = new \ReflectionProperty(TActiveRecord::class, '_connection');
		$resRef->setAccessible(true);
		$this->assertNull($resRef->getValue($restored));
	}

	// -----------------------------------------------------------------------
	//  __wakeup restores column mapping and relations
	// -----------------------------------------------------------------------

	public function testSqliteWakeupDoesNotThrow(): void
	{
		$record = new SqliteSleepTestRecord();
		$record->id = 1;
		// __wakeup calls setupColumnMapping() and setupRelations() — must not throw
		$restored = unserialize(serialize($record));
		$this->assertInstanceOf(SqliteSleepTestRecord::class, $restored);
	}
}
