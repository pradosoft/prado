<?php

use Prado\Data\Common\TDbMetaData;
use Prado\Data\TDbCommand;
use Prado\Data\TDbConnection;
use Prado\Data\TDbTransaction;
use Prado\Exceptions\TDbException;
use Prado\TApplication;
use Prado\Util\TBehavior;
use Prado\Util\TCallChain;

if (!defined('TEST_DB_FILE')) {
	define('TEST_DB_FILE', __DIR__ . '/db/test.db');
}

class TDbTransactionTest extends PHPUnit\Framework\TestCase
{
	private $_connection;

	protected function setUp(): void
	{
		// Remove any stale DB file from a previous test run (guards against Windows
		// file-lock failures leaving the file behind after tearDown).
		@unlink(TEST_DB_FILE);

		// create application just to provide application mode
		new TApplication(__DIR__, false, TApplication::CONFIG_TYPE_PHP);

		$this->_connection = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$this->_connection->Active = true;
		$this->_connection->createCommand('CREATE TABLE foo (id INTEGER NOT NULL PRIMARY KEY, name VARCHAR(8))')->execute();
	}

	protected function tearDown(): void
	{
		// Explicitly close the PDO connection before unlinking to release the file
		// lock on Windows (where an open handle prevents unlink from succeeding).
		if ($this->_connection !== null) {
			$this->_connection->Active = false;
			$this->_connection = null;
		}
		@unlink(TEST_DB_FILE);
	}

	public function testRollBack()
	{
		$sql = 'INSERT INTO foo(id,name) VALUES (1,\'my name\')';
		$transaction = $this->_connection->beginTransaction();
		try {
			$this->_connection->createCommand($sql)->execute();
			$this->_connection->createCommand($sql)->execute();
			$this->fail('Expected exception not raised');
			$transaction->commit();
		} catch (Exception $e) {
			$this->assertTrue($transaction->Active);
			$transaction->rollBack();
			$this->assertFalse($transaction->Active);
			$reader = $this->_connection->createCommand('SELECT * FROM foo')->query();
			$this->assertFalse($reader->read());
		}
	}

	public function testCommit()
	{
		$sql1 = 'INSERT INTO foo(id,name) VALUES (1,\'my name\')';
		$sql2 = 'INSERT INTO foo(id,name) VALUES (2,\'my name\')';
		$transaction = $this->_connection->beginTransaction();
		try {
			$this->_connection->createCommand($sql1)->execute();
			$this->_connection->createCommand($sql2)->execute();
			$this->assertTrue($transaction->Active);
			$transaction->commit();
			$this->assertFalse($transaction->Active);
		} catch (Exception $e) {
			$transaction->rollBack();
			$this->fail('Unexpected exception');
		}
		$result = $this->_connection->createCommand('SELECT * FROM foo')->query()->readAll();
		$this->assertEquals(count($result), 2);
	}

	// -----------------------------------------------------------------------
	// Helpers
	// -----------------------------------------------------------------------

	/**
	 * Make an anonymous TBehavior that intercepts dyIsTransactionComplete and
	 * always returns the supplied boolean, ignoring the call chain.
	 */
	private function makeDyBehavior(bool $force): TBehavior
	{
		return new class($force) extends TBehavior {
			private bool $_force;

			public function __construct(bool $force)
			{
				$this->_force = $force;
				parent::__construct();
			}

			public function dyIsTransactionComplete($returnValue, ?TCallChain $chain = null): bool
			{
				return $this->_force;
			}
		};
	}

	/**
	 * Build a mock TDbConnection whose PDO instance is entirely controlled by
	 * the test. The original constructor is suppressed so no real DB is opened.
	 */
	private function createMockConnectionWithPdo(object $mockPdo): TDbConnection
	{
		$conn = $this->getMockBuilder(TDbConnection::class)
			->disableOriginalConstructor()
			->onlyMethods(['getActive', 'getPdoInstance'])
			->getMock();

		$conn->method('getActive')->willReturn(true);
		$conn->method('getPdoInstance')->willReturn($mockPdo);

		return $conn;
	}

	/**
	 * Build a mock PDO-like stub whose commit / rollBack / getAttribute calls
	 * can be asserted. getAttribute(PDO::ATTR_DRIVER_NAME) returns $driver.
	 */
	private function createMockPdo(string $driver): object
	{
		$pdo = $this->getMockBuilder(\stdClass::class)
			->addMethods(['commit', 'rollBack', 'getAttribute'])
			->getMock();

		$pdo->method('getAttribute')
			->with(PDO::ATTR_DRIVER_NAME)
			->willReturn($driver);

		return $pdo;
	}

	// -----------------------------------------------------------------------
	// Constructor / basic accessors
	// -----------------------------------------------------------------------

	public function testConstructorCreatesActiveTransaction(): void
	{
		$tx = $this->_connection->beginTransaction();
		$this->assertTrue($tx->getActive());
		$tx->rollBack();
	}

	public function testGetConnectionReturnsConnection(): void
	{
		$tx = $this->_connection->beginTransaction();
		$this->assertSame($this->_connection, $tx->getConnection());
		$tx->rollBack();
	}

	public function testGetSerialDefaultsFalse(): void
	{
		$tx = $this->_connection->beginTransaction();
		$this->assertFalse($tx->getSerial());
		$tx->rollBack();
	}

	public function testCreateCommandDelegatesToConnection(): void
	{
		$tx = $this->_connection->beginTransaction();
		$cmd = $tx->createCommand('SELECT 1');
		$this->assertInstanceOf(TDbCommand::class, $cmd);
		$tx->rollBack();
	}

	public function testGetDbMetaDataReturnsTDbMetaData(): void
	{
		$tx = $this->_connection->beginTransaction();
		$meta = $tx->getDbMetaData();
		$this->assertInstanceOf(TDbMetaData::class, $meta);
		$tx->rollBack();
	}

	// -----------------------------------------------------------------------
	// commit() / rollBack() deactivate the transaction
	// -----------------------------------------------------------------------

	public function testCommitDeactivatesTransaction(): void
	{
		$tx = $this->_connection->beginTransaction();
		$this->assertTrue($tx->getActive());
		$tx->commit();
		$this->assertFalse($tx->getActive());
	}

	public function testRollBackDeactivatesTransaction(): void
	{
		$tx = $this->_connection->beginTransaction();
		$this->assertTrue($tx->getActive());
		$tx->rollBack();
		$this->assertFalse($tx->getActive());
	}

	// -----------------------------------------------------------------------
	// commit() / rollBack() throw when the transaction or connection is inactive
	// -----------------------------------------------------------------------

	public function testCommitThrowsWhenTransactionInactive(): void
	{
		$tx = $this->_connection->beginTransaction();
		$tx->commit();
		$this->expectException(TDbException::class);
		$tx->commit();
	}

	public function testRollBackThrowsWhenTransactionInactive(): void
	{
		$tx = $this->_connection->beginTransaction();
		$tx->rollBack();
		$this->expectException(TDbException::class);
		$tx->rollBack();
	}

	public function testCommitThrowsWhenConnectionInactive(): void
	{
		$tx = $this->_connection->beginTransaction();
		// Close the connection while the transaction is open.
		// SQLite silently rolls back; the TDbTransaction object retains Active=true
		// so the guard check fires on the next call.
		$this->_connection->Active = false;
		$this->expectException(TDbException::class);
		$tx->commit();
	}

	public function testRollBackThrowsWhenConnectionInactive(): void
	{
		$tx = $this->_connection->beginTransaction();
		$this->_connection->Active = false;
		$this->expectException(TDbException::class);
		$tx->rollBack();
	}

	// -----------------------------------------------------------------------
	// dyIsTransactionComplete — dynamic event (behavior interception)
	// -----------------------------------------------------------------------

	/**
	 * A behavior that returns false from dyIsTransactionComplete prevents
	 * setActive(false) from being called, so the TDbTransaction stays "active"
	 * at the PHP level even though the underlying PDO transaction was committed.
	 */
	public function testDyBehaviorCanKeepTransactionActiveAfterCommit(): void
	{
		$tx = $this->_connection->beginTransaction();
		$tx->attachBehavior('keepAlive', $this->makeDyBehavior(false));

		// PDO commits, but the behavior blocks deactivation by returning false.
		$tx->commit();

		$this->assertTrue($tx->getActive());
	}

	/**
	 * A behavior that returns true from dyIsTransactionComplete forces the
	 * transaction to be marked complete — the same outcome as the default
	 * (no-behavior) path.
	 */
	public function testDyBehaviorReturningTrueDeactivatesTransaction(): void
	{
		$tx = $this->_connection->beginTransaction();
		$tx->attachBehavior('forceComplete', $this->makeDyBehavior(true));

		$tx->commit(); // behavior returns true → setActive(false) is called

		$this->assertFalse($tx->getActive());
	}

	/**
	 * With no behaviors attached, dyIsTransactionComplete passes through the
	 * default value (true), so commit() always deactivates the transaction.
	 */
	public function testDyIsTransactionCompleteDefaultPassesThroughTrue(): void
	{
		$tx = $this->_connection->beginTransaction();
		$tx->commit();
		// Default: no behaviors → isTransactionComplete returns true → inactive
		$this->assertFalse($tx->getActive());
	}

	// -----------------------------------------------------------------------
	// setActive() bug regression — $active vs $value
	// -----------------------------------------------------------------------

	/**
	 * setActive(true) must NOT clear the serial flag.
	 *
	 * The original setActive() used the undefined variable `$active` (evaluating
	 * to null, so `!null === true`), which caused setSerial(false) to run even
	 * when activating the transaction. The fix changed the guard to `!$value`.
	 */
	public function testSetActiveTrueDoesNotClearSerialFlag(): void
	{
		$tx  = $this->_connection->beginTransaction();
		$ref = new \ReflectionClass($tx);

		$setSerial = $ref->getMethod('setSerial');
		$setSerial->setAccessible(true);
		$setActive = $ref->getMethod('setActive');
		$setActive->setAccessible(true);

		// Manually enable serial mode.
		$setSerial->invoke($tx, true);
		$this->assertTrue($tx->getSerial(), 'Precondition: serial must be true.');

		// setActive(true) must leave the serial flag untouched.
		$setActive->invoke($tx, true);
		$this->assertTrue(
			$tx->getSerial(),
			'setActive(true) must NOT reset the serial flag to false (was bug: used $active instead of $value).'
		);

		// setActive(false) MUST clear the serial flag.
		$setActive->invoke($tx, false);
		$this->assertFalse(
			$tx->getSerial(),
			'setActive(false) must reset the serial flag to false.'
		);

		// The underlying PDO transaction is still open (setActive via reflection did
		// not call PDO::rollBack).  Restore active=true so we can roll back cleanly
		// via the normal TDbTransaction API without calling beginTransaction() again
		// (which would throw "already active" since PDO is still in a transaction).
		$setActive->invoke($tx, true);
		$tx->rollBack();
	}

	// -----------------------------------------------------------------------
	// Firebird post-transaction flush — PDO::commit() called a second time
	// -----------------------------------------------------------------------

	/**
	 * For Firebird connections, pdo_firebird opens an implicit transaction
	 * immediately after isc_commit_transaction. A second PDO::commit() must
	 * be issued to flush that implicit transaction so subsequent reads see the
	 * committed data. TDbTransaction::commit() therefore calls PDO::commit() twice.
	 */
	public function testCommitIssuedTwiceForFirebird(): void
	{
		$pdo = $this->createMockPdo('firebird');
		$pdo->expects($this->exactly(2))->method('commit');

		$tx = new TDbTransaction($this->createMockConnectionWithPdo($pdo));
		$tx->commit();

		$this->assertFalse($tx->getActive());
	}

	/**
	 * After rollBack() on a Firebird connection, the same implicit-transaction
	 * problem applies: a single PDO::commit() must be issued to flush it.
	 */
	public function testRollBackFlushesImplicitTransactionForFirebird(): void
	{
		$pdo = $this->createMockPdo('firebird');
		$pdo->expects($this->once())->method('rollBack');
		$pdo->expects($this->once())->method('commit'); // flush only, not a real commit

		$tx = new TDbTransaction($this->createMockConnectionWithPdo($pdo));
		$tx->rollBack();

		$this->assertFalse($tx->getActive());
	}

	/**
	 * For non-Firebird drivers (e.g. MySQL) only one PDO::commit() is issued
	 * and rollBack() never calls PDO::commit() at all.
	 */
	public function testCommitIssuedOnceForMysql(): void
	{
		$pdo = $this->createMockPdo('mysql');
		$pdo->expects($this->once())->method('commit');

		$tx = new TDbTransaction($this->createMockConnectionWithPdo($pdo));
		$tx->commit();

		$this->assertFalse($tx->getActive());
	}

	public function testRollBackIssuesNoPostFlushForMysql(): void
	{
		$pdo = $this->createMockPdo('mysql');
		$pdo->expects($this->once())->method('rollBack');
		$pdo->expects($this->never())->method('commit');

		$tx = new TDbTransaction($this->createMockConnectionWithPdo($pdo));
		$tx->rollBack();
	}

	/**
	 * SQLite does not require the post-transaction flush; rollBack() must not
	 * issue any PDO::commit() call.
	 */
	public function testRollBackIssuesNoPostFlushForSqlite(): void
	{
		$pdo = $this->createMockPdo('sqlite');
		$pdo->expects($this->once())->method('rollBack');
		$pdo->expects($this->never())->method('commit');

		$tx = new TDbTransaction($this->createMockConnectionWithPdo($pdo));
		$tx->rollBack();
	}

	/**
	 * PostgreSQL does not require the post-transaction flush.
	 */
	public function testCommitIssuedOnceForPgsql(): void
	{
		$pdo = $this->createMockPdo('pgsql');
		$pdo->expects($this->once())->method('commit');

		$tx = new TDbTransaction($this->createMockConnectionWithPdo($pdo));
		$tx->commit();
	}

	/**
	 * The 'interbase' driver alias does NOT receive the post-transaction flush.
	 * Only the literal string 'firebird' triggers the flush, because
	 * TDbDriverCapabilities::requiresPostTransactionFlush() uses a direct
	 * comparison, not the charset-alias map. This verifies the intentional
	 * asymmetry between charset aliasing (interbase → firebird) and flush
	 * behaviour (interbase is excluded).
	 */
	public function testInterbaseDoesNotReceivePostFlushCommit(): void
	{
		$pdo = $this->createMockPdo('interbase');
		$pdo->expects($this->once())->method('commit');
		$pdo->expects($this->never())->method('rollBack');

		$tx = new TDbTransaction($this->createMockConnectionWithPdo($pdo));
		$tx->commit();
	}

	/**
	 * MSSQL (sqlsrv) does not require the post-transaction flush.
	 */
	public function testCommitIssuedOnceForSqlsrv(): void
	{
		$pdo = $this->createMockPdo('sqlsrv');
		$pdo->expects($this->once())->method('commit');

		$tx = new TDbTransaction($this->createMockConnectionWithPdo($pdo));
		$tx->commit();
	}

	/**
	 * Oracle (oci) does not require the post-transaction flush.
	 */
	public function testCommitIssuedOnceForOci(): void
	{
		$pdo = $this->createMockPdo('oci');
		$pdo->expects($this->once())->method('commit');

		$tx = new TDbTransaction($this->createMockConnectionWithPdo($pdo));
		$tx->commit();
	}
}
