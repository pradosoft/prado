<?php

require_once(__DIR__ . '/BaseCase.php');

use Prado\Data\ActiveRecord\TActiveRecord;
use Prado\Data\ActiveRecord\TActiveRecordManager;

class ActiveAccount extends TActiveRecord
{
	public $Account_Id;
	public $Account_FirstName;
	public $Account_LastName;
	public $Account_Email;

	public $Account_Banner_Option;
	public $Account_Cart_Option;

	const TABLE = 'Accounts';

	public static function finder($className = __CLASS__)
	{
		return parent::finder($className);
	}

	/**
	 * Marks this instance as STATE_LOADED so that save() calls update()
	 * instead of insert(). SqlMap hydrates AR objects via new ActiveAccount(),
	 * leaving _recordState at STATE_NEW; call this after SqlMap loads the object
	 * when you intend to update the existing row.
	 */
	public function markLoaded(): void
	{
		$this->_recordState = self::STATE_LOADED;
	}
}

abstract class ActiveRecordSqlMapTestCase extends BaseCase
{
	public static function setUpBeforeClass(): void
	{
		// Clear any stale SqlMap gateway left over from a prior test class.
		// BaseCase::$sqlmap is a shared static; if a previous class succeeded and
		// set it, then a later class that fails to connect will leave it non-null
		// while static::$config becomes null — causing getConnection() on null below.
		static::$sqlmap = null;
		parent::setUpBeforeClass();
		// NOTE: do NOT null static::$connection before parent::setUpBeforeClass().
		// initSchema() uses CopyFileScriptRunner which replaces tests.db on disk.
		// If conn2 (static::$config->getConnection()) were already open at copy
		// time, SQLite would see a stale/inconsistent page cache.  By letting the
		// copy happen first (with whatever stale connection parent opens — ignored
		// by CopyFileScriptRunner anyway), we can then do a clean open of conn2.
		self::initSqlMap();
		if (static::$sqlmap !== null) {
			// static::$config is guaranteed non-null here: initSqlMap() only
			// assigns static::$sqlmap when static::$config !== null.
			$conn = static::$config->getConnection();
			// Cycle the connection closed → open so SQLite re-reads the database
			// file that CopyFileScriptRunner just replaced, guaranteeing a clean,
			// consistent view with no stale page-cache from before the copy.
			$conn->setActive(false);
			$conn->setActive(true);
			TActiveRecordManager::getInstance()->setDbConnection($conn);
			// Evict stale BaseCase::$connection from prior test classes so future
			// getConnection() calls re-anchor to the current config's connection.
			static::$connection = null;
		}
	}

	protected function setUp(): void
	{
		parent::setUp(); // calls skipIfUnavailable()
		// Re-anchor the AR manager connection before every test. Also cycle the
		// connection (setActive false→true) so SQLite discards any stale page-cache
		// that accumulated since setUpBeforeClass ran the file copy, ensuring both
		// the SqlMap gateway and the AR manager see a fully consistent database view.
		if (static::$sqlmap !== null && static::$config !== null) {
			$conn = static::$config->getConnection();
			$conn->setActive(false);
			$conn->setActive(true);
			TActiveRecordManager::getInstance()->setDbConnection($conn);
		}
	}

	public function testLoadWithSqlMap_SaveWithActiveRecord()
	{
		$record = self::$sqlmap->queryForObject('GetActiveRecordAccounts');
		// SqlMap hydrates via new ActiveAccount(), so _recordState = STATE_NEW.
		// markLoaded() transitions to STATE_LOADED so save() calls update() instead of insert().
		$record->markLoaded();
		$record->Account_FirstName = "Testing 123";

		$this->assertTrue($record->save());

		$check1 = self::$sqlmap->queryForObject('GetActiveRecordAccounts');
		// Use a fresh instance instead of ActiveAccount::finder() to avoid the
		// function-local static finder cache holding a stale connection from a
		// previously-run test class (e.g. MysqlActiveRecordSqlMapTest which runs
		// before SqliteActiveRecordSqlMapTest alphabetically and caches the finder
		// with a MySQL _connection, causing findBy* to query MySQL instead of SQLite).
		$check2 = (new ActiveAccount())->findByAccount_FirstName($record->Account_FirstName);


		$this->assertSameAccount($record,$check1);
		$this->assertSameAccount($record,$check2);

		$this->initScript('account-init.sql');
	}

	public function assertSameAccount($account1, $account2)
	{
		$props = ['Account_Id', 'Account_FirstName', 'Account_LastName',
						'Account_Email', 'Account_Banner_Option', 'Account_Cart_Option'];
		foreach ($props as $prop) {
			$this->assertEquals($account1->{$prop}, $account2->{$prop});
		}
	}
}
