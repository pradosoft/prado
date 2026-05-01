<?php

use Prado\Data\Common\TDbMetaData;
use Prado\Data\TDbCommand;
use Prado\Data\TDbConnection;
use Prado\Data\TDbTransaction;
use Prado\Exceptions\TDbException;
use Prado\TApplication;

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
	 * Build a mock TDbConnection whose PDO instance is entirely controlled by
	 * the test. The original constructor is suppressed so no real DB is opened.
	 * getDriverName() is derived from the mock PDO's ATTR_DRIVER_NAME.
	 */
	private function createMockConnectionWithPdo(object $mockPdo): TDbConnection
	{
		$conn = $this->getMockBuilder(TDbConnection::class)
			->disableOriginalConstructor()
			->onlyMethods(['getActive', 'getPdoInstance', 'getDriverName', 'getLastTransaction'])
			->getMock();

		$conn->method('getActive')->willReturn(true);
		$conn->method('getPdoInstance')->willReturn($mockPdo);
		$conn->method('getDriverName')->willReturn(
			$mockPdo->getAttribute(PDO::ATTR_DRIVER_NAME)
		);
		// getLastTransaction() defaults to null; override per-test when
		// TDbTransaction::beginTransaction() is exercised so the supersession
		// guard sees the correct transaction object.

		return $conn;
	}

	/**
	 * Build a mock PDO stub whose commit / rollBack / getAttribute /
	 * beginTransaction calls can be asserted.
	 * getAttribute(PDO::ATTR_DRIVER_NAME) returns $driver.
	 *
	 * Must extend \PDO (via disableOriginalConstructor) so that the strict
	 * return type `PDO` on TDbTransaction::assertActive() is satisfied.
	 */
	private function createMockPdo(string $driver): \PDO
	{
		$pdo = $this->getMockBuilder(\PDO::class)
			->disableOriginalConstructor()
			->onlyMethods(['commit', 'rollBack', 'getAttribute', 'beginTransaction'])
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

	// -----------------------------------------------------------------------
	// beginTransaction() — reactivates a committed / rolled-back transaction
	// -----------------------------------------------------------------------

	public function testBeginTransactionOnCommittedTransactionReactivatesIt(): void
	{
		$tx = $this->_connection->beginTransaction();
		$tx->commit();
		$this->assertFalse($tx->getActive());

		$tx->beginTransaction();
		$this->assertTrue($tx->getActive());
		$tx->rollBack();
	}

	public function testBeginTransactionOnRolledBackTransactionReactivatesIt(): void
	{
		$tx = $this->_connection->beginTransaction();
		$tx->rollBack();
		$this->assertFalse($tx->getActive());

		$tx->beginTransaction();
		$this->assertTrue($tx->getActive());
		$tx->rollBack();
	}

	public function testBeginTransactionReturnsStatic(): void
	{
		$tx = $this->_connection->beginTransaction();
		$tx->commit();
		$result = $tx->beginTransaction();
		$this->assertSame($tx, $result);
		$tx->rollBack();
	}

	public function testBeginTransactionOnActiveTransactionThrows(): void
	{
		$tx = $this->_connection->beginTransaction();
		$this->expectException(TDbException::class);
		try {
			$tx->beginTransaction();
		} finally {
			$tx->rollBack();
		}
	}

	public function testBeginTransactionThrowsWhenConnectionInactive(): void
	{
		$tx = $this->_connection->beginTransaction();
		$tx->commit();
		$this->_connection->Active = false;
		$this->expectException(TDbException::class);
		$tx->beginTransaction();
	}

	public function testBeginTransactionThrowsWhenSupersededByNewTransaction(): void
	{
		// After $tx1 commits, calling $conn->beginTransaction() creates $tx2 and
		// stores it as the connection's last transaction.  $tx1 is now superseded:
		// $tx1->beginTransaction() must throw because the connection no longer
		// tracks $tx1 — restarting it would silently bypass $tx2's lifecycle.
		$tx1 = $this->_connection->beginTransaction();
		$tx1->commit();

		$tx2 = $this->_connection->beginTransaction(); // supersedes $tx1

		try {
			$this->expectException(TDbException::class);
			$tx1->beginTransaction();
		} finally {
			// Clean up: roll back the still-active superseding transaction so that
			// tearDown() can close the connection without a dangling transaction.
			if ($tx2->getActive()) {
				$tx2->rollBack();
			}
		}
	}

	public function testLastTransactionReflectsNewestObject(): void
	{
		// getLastTransaction() must always return the most recently created
		// TDbTransaction, not the one that was superseded.
		$tx1 = $this->_connection->beginTransaction();
		$this->assertSame($tx1, $this->_connection->getLastTransaction());
		$tx1->commit();

		$tx2 = $this->_connection->beginTransaction();
		$this->assertSame($tx2, $this->_connection->getLastTransaction());
		$tx2->rollBack();
	}

	public function testBeginTransactionRestoresConnectionCurrentTransaction(): void
	{
		// After beginTransaction(), getCurrentTransaction() must return $tx.
		$tx = $this->_connection->beginTransaction();
		$tx->commit();
		$this->assertNull($this->_connection->getCurrentTransaction());

		$tx->beginTransaction();
		$this->assertSame($tx, $this->_connection->getCurrentTransaction());
		$tx->rollBack();
	}

	public function testBeginTransactionCommitCycleCanRepeat(): void
	{
		// Two full begin/commit cycles using the same transaction object.
		$tx = $this->_connection->beginTransaction();
		$this->_connection->createCommand('INSERT INTO foo(id,name) VALUES (1,\'a\')')->execute();
		$tx->commit();

		$tx->beginTransaction();
		$this->_connection->createCommand('INSERT INTO foo(id,name) VALUES (2,\'b\')')->execute();
		$tx->commit();

		$count = (int) $this->_connection->createCommand('SELECT COUNT(*) FROM foo')->queryScalar();
		$this->assertSame(2, $count);
	}

	public function testBeginTransactionPreFlushIssuedForFirebird(): void
	{
		// For Firebird, TDbTransaction::beginTransaction() must issue PDO::commit()
		// (pre-begin flush) before PDO::beginTransaction(), to clear the implicit
		// transaction pdo_firebird keeps alive in autocommit mode.
		$pdo = $this->createMockPdo('firebird');
		$calls = [];
		$pdo->method('commit')->willReturnCallback(function () use (&$calls) {
			$calls[] = 'commit';
			return true;
		});
		$pdo->method('rollBack')->willReturnCallback(function () use (&$calls) {
			$calls[] = 'rollBack';
			return true;
		});
		$pdo->method('beginTransaction')->willReturnCallback(function () use (&$calls) {
			$calls[] = 'beginTransaction';
			return true;
		});

		$conn = $this->createMockConnectionWithPdo($pdo);
		$tx = new TDbTransaction($conn);
		// Tell the mock that $tx is still the connection's last transaction so the
		// supersession guard in TDbTransaction::beginTransaction() does not fire.
		$conn->method('getLastTransaction')->willReturn($tx);
		// First commit deactivates the transaction (also issues the Firebird post-flush commit).
		$tx->commit();
		$calls = []; // reset — focus only on beginTransaction()

		$tx->beginTransaction();

		// Expected: commit (pre-begin flush) followed by beginTransaction.
		$this->assertSame(['commit', 'beginTransaction'], $calls);
		$this->assertTrue($tx->getActive());
	}

	public function testBeginTransactionNoPreFlushForNonFirebird(): void
	{
		// Non-Firebird drivers must not receive a PDO::commit() before beginTransaction().
		$pdo = $this->createMockPdo('mysql');
		$calls = [];
		$pdo->method('commit')->willReturnCallback(function () use (&$calls) {
			$calls[] = 'commit';
			return true;
		});
		$pdo->method('beginTransaction')->willReturnCallback(function () use (&$calls) {
			$calls[] = 'beginTransaction';
			return true;
		});

		$conn = $this->createMockConnectionWithPdo($pdo);
		$tx = new TDbTransaction($conn);
		// Tell the mock that $tx is still the connection's last transaction so the
		// supersession guard in TDbTransaction::beginTransaction() does not fire.
		$conn->method('getLastTransaction')->willReturn($tx);
		$tx->commit(); // deactivate
		$calls = [];

		$tx->beginTransaction();

		// Only beginTransaction should be called; no pre-flush commit.
		$this->assertSame(['beginTransaction'], $calls);
		$this->assertTrue($tx->getActive());
	}
}
