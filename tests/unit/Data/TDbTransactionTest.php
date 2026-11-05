<?php

namespace Prado\Test\Unit\Data;

use PDO;
use Prado\Data\TDbConnection;
use Prado\Data\IDataConnection;
use Prado\Data\TDbTransaction;
use Prado\Exceptions\TDbException;
use ReflectionClass;
use Prado\TApplication;
use Prado\Test\Unit\PradoUnit;
use Prado\TApplicationMode;

if (!defined('TEST_DB_FILE')) {
	define('TEST_DB_FILE', __DIR__ . '/db/test.db');
}

/**
 * Hand-rolled PDO stub that pretends to be a Firebird connection.
 *
 * PHPUnit mocks of native C-extension classes (like PDO) are unreliable because
 * PHP requires the native constructor to have run before any C-level method can
 * be invoked — even on a stub object.  Overriding __construct() in a subclass
 * prevents the C-level initialisation and keeps all method calls at the PHP level.
 */
class FirebirdPdoStub extends PDO
{
	/** @var string[] SQL statements passed to exec() */
	public array $execCalls = [];
	public bool $rollBackCalled = false;

	/** Suppress native PDO constructor so no C-level state is required. */
	public function __construct() {}

	public function getAttribute(int $attribute): mixed
	{
		return $attribute === PDO::ATTR_DRIVER_NAME ? 'firebird' : null;
	}

	public function exec(string $statement): int|false
	{
		$this->execCalls[] = $statement;
		return 0;
	}

	public function rollBack(): bool
	{
		$this->rollBackCalled = true;
		return true;
	}

	public bool $commitCalled = false;

	public function commit(): bool
	{
		$this->commitCalled = true;
		return true;
	}
}

/**
 * PDO stub with a configurable driver name and failure points, for the
 * connection's transaction paths that tolerate driver exceptions.
 */
class TDbTransactionTestPdoStub extends PDO
{
	public string $driver = 'firebird';
	public bool $attributeThrows = false;
	public bool $attributeErrors = false;
	public bool $commitThrows = false;
	public bool $commitErrors = false;
	public bool $rollBackThrows = false;
	public bool $inTx = true;
	/** @var string[] PDO transaction calls in order */
	public array $calls = [];

	public function __construct()
	{
	}

	public function getAttribute(int $attribute): mixed
	{
		if ($this->attributeThrows) {
			throw new \PDOException('attribute unavailable');
		}
		if ($this->attributeErrors) {
			throw new \Error('attribute handle released');
		}
		return $attribute === PDO::ATTR_DRIVER_NAME ? $this->driver : null;
	}

	public function beginTransaction(): bool
	{
		$this->calls[] = 'begin';
		return true;
	}

	public function commit(): bool
	{
		$this->calls[] = 'commit';
		if ($this->commitThrows) {
			throw new \PDOException('no transaction to commit');
		}
		if ($this->commitErrors) {
			throw new \Error('commit handle released');
		}
		return true;
	}

	public function rollBack(): bool
	{
		$this->calls[] = 'rollBack';
		if ($this->rollBackThrows) {
			throw new \PDOException('connection lost');
		}
		return true;
	}

	public function inTransaction(): bool
	{
		return $this->inTx;
	}
}

class TDbTransactionTest extends \PHPUnit\Framework\TestCase
{
	private $_connection;

	protected function setUp(): void
	{
		// Remove any stale DB file from a previous test run (guards against Windows
		// file-lock failures leaving the file behind after tearDown).
		@unlink(TEST_DB_FILE);

		// create application just to provide application mode
		(new TApplication(__DIR__, false, TApplication::CONFIG_TYPE_PHP))->setMode(TApplicationMode::Debug);

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
		} catch (\Exception $e) {
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
		} catch (\Exception $e) {
			$transaction->rollBack();
			$this->fail('Unexpected exception');
		}
		$result = $this->_connection->createCommand('SELECT * FROM foo')->query()->readAll();
		$this->assertEquals(count($result), 2);
	}

	public function testRollBackFlushesImplicitTransactionForFirebird()
	{
		// Use FirebirdPdoStub (defined above) rather than a PHPUnit mock of PDO.
		// PHPUnit mocks of native extension classes fail with "PDO object is not
		// initialized, constructor was not called" when C-level methods are invoked,
		// because the C-level constructor was suppressed.  The stub subclass
		// overrides __construct() and the relevant methods at the PHP level,
		// so no C-level state is needed.
		//
		// The connection is the source of truth for transaction control:
		// TDbConnection::rollback() runs the PDO call and invokes
		// completeTransaction(), which performs the Firebird-specific post-flush.
		// TDbTransaction::rollback() simply delegates here, so we test the
		// flush behaviour against the connection directly.
		$pdo = new FirebirdPdoStub();

		$conn = new TDbConnection();
		$ref = new ReflectionClass($conn);
		$pdoProp = $ref->getProperty('_pdo');
		$pdoProp->setValue($conn, $pdo);
		$activeProp = $ref->getProperty('_active');
		$activeProp->setValue($conn, true);

		$transaction = new TDbTransaction($conn);
		$txnProp = $ref->getProperty('_transaction');
		$txnProp->setValue($conn, $transaction);

		$transaction->rollBack();

		self::assertTrue(
			$pdo->rollBackCalled,
			'PDO::rollBack() must be called to roll back the Firebird transaction.'
		);
		self::assertTrue(
			$pdo->commitCalled,
			'PDO::commit() must be called after rollback to flush the implicit transaction ' .
			'that pdo_firebird opens automatically (requiresPostTransactionFlush).'
		);
		self::assertFalse($transaction->Active, 'Transaction must be inactive after rollback.');
	}

	public function testCommitWithoutHandlers()
	{
		$transaction = $this->_connection->beginTransaction();
		$this->assertSame($transaction, $this->_connection->getCurrentTransaction());
		$this->_connection->createCommand('INSERT INTO foo(id,name) VALUES (1,\'a\')')->execute();
		$this->assertNull($transaction->commit());
		$this->assertFalse($transaction->getActive());
		$this->assertNull($this->_connection->getCurrentTransaction());
		$this->assertEquals(1, $this->countRowsOnOtherConnection());
	}

	public function testOnCommitRaisedOnceAfterDataIsVisible()
	{
		$calls = [];
		$transaction = $this->_connection->beginTransaction();
		$transaction->attachEventHandler('OnCommit', function ($sender, $param) use (&$calls) {
			$calls[] = [
				'sender' => $sender,
				'param' => $param,
				'active' => $sender->getActive(),
				'current' => $sender->getConnection()->getCurrentTransaction(),
				'rows' => $this->countRowsOnOtherConnection(),
			];
		});
		$transaction->attachEventHandler('OnRollback', function ($sender, $param) {
			$this->fail('OnRollback raised by a commit');
		});
		$this->_connection->createCommand('INSERT INTO foo(id,name) VALUES (1,\'a\')')->execute();
		$this->assertEquals(0, $this->countRowsOnOtherConnection());

		$transaction->commit();

		$this->assertCount(1, $calls);
		$this->assertSame($transaction, $calls[0]['sender']);
		$this->assertNull($calls[0]['param']);
		$this->assertFalse($calls[0]['active']);
		$this->assertNull($calls[0]['current']);
		$this->assertEquals(1, $calls[0]['rows']);

		try {
			$transaction->commit();
			$this->fail('Expected TDbException for a second commit');
		} catch (TDbException $e) {
		}
		$this->assertCount(1, $calls);
	}

	public function testOnRollbackRaisedAndOnCommitNot()
	{
		$rollbacks = [];
		$transaction = $this->_connection->beginTransaction();
		$transaction->attachEventHandler('OnCommit', function ($sender, $param) {
			$this->fail('OnCommit raised by a rollback');
		});
		$transaction->attachEventHandler('OnRollback', function ($sender, $param) use (&$rollbacks) {
			$rollbacks[] = [$sender, $param, $sender->getActive()];
		});
		$this->_connection->createCommand('INSERT INTO foo(id,name) VALUES (1,\'a\')')->execute();

		$transaction->rollback();

		$this->assertCount(1, $rollbacks);
		$this->assertSame($transaction, $rollbacks[0][0]);
		$this->assertNull($rollbacks[0][1]);
		$this->assertFalse($rollbacks[0][2]);
		$this->assertEquals(0, $this->countRowsOnOtherConnection());
	}

	public function testFailingCommitRaisesNoEvent()
	{
		$this->_connection->createCommand('PRAGMA foreign_keys = ON')->execute();
		$this->_connection->createCommand('CREATE TABLE parent (id INTEGER NOT NULL PRIMARY KEY)')->execute();
		$this->_connection->createCommand('CREATE TABLE child (id INTEGER NOT NULL PRIMARY KEY, parent_id INTEGER REFERENCES parent(id) DEFERRABLE INITIALLY DEFERRED)')->execute();

		$events = [];
		$transaction = $this->_connection->beginTransaction();
		$transaction->attachEventHandler('OnCommit', function ($sender, $param) use (&$events) {
			$events[] = 'commit';
		});
		$transaction->attachEventHandler('OnRollback', function ($sender, $param) use (&$events) {
			$events[] = 'rollback';
		});
		$this->_connection->createCommand('INSERT INTO child(id,parent_id) VALUES (1,99)')->execute();

		try {
			$transaction->commit();
			$this->fail('Expected the deferred foreign key to fail the commit');
		} catch (\PDOException $e) {
		}
		$this->assertSame([], $events);
		$this->assertTrue($transaction->getActive());

		$transaction->rollback();
		$this->assertSame(['rollback'], $events);
	}

	public function testClosingTheConnectionRollsBackAnOpenTransaction()
	{
		$rollbacks = [];
		$transaction = $this->_connection->beginTransaction();
		$transaction->attachEventHandler('OnCommit', function ($sender, $param) {
			$this->fail('OnCommit raised by closing the connection');
		});
		$transaction->attachEventHandler('OnRollback', function ($sender, $param) use (&$rollbacks) {
			$rollbacks[] = [$sender, $sender->getActive(), $sender->getConnection()->getActive()];
		});
		$this->_connection->createCommand('INSERT INTO foo(id,name) VALUES (1,\'a\')')->execute();

		$this->_connection->setActive(false);

		$this->assertCount(1, $rollbacks);
		$this->assertSame($transaction, $rollbacks[0][0]);
		$this->assertFalse($rollbacks[0][1], 'The transaction is inactive in the handler.');
		$this->assertFalse($rollbacks[0][2], 'OnRollback is raised after the connection closes.');
		$this->assertFalse($transaction->getActive());
		$this->assertEquals(0, $this->countRowsOnOtherConnection());

		$this->_connection->setActive(true);
		$this->_connection->setActive(false);
		$this->assertCount(1, $rollbacks, 'An ended transaction raises nothing on a later close.');
	}

	public function testClosingAfterAFailedCommitRollsBack()
	{
		$this->_connection->createCommand('PRAGMA foreign_keys = ON')->execute();
		$this->_connection->createCommand('CREATE TABLE parent (id INTEGER NOT NULL PRIMARY KEY)')->execute();
		$this->_connection->createCommand('CREATE TABLE child (id INTEGER NOT NULL PRIMARY KEY, parent_id INTEGER REFERENCES parent(id) DEFERRABLE INITIALLY DEFERRED)')->execute();
		$events = [];
		$transaction = $this->_connection->beginTransaction();
		$transaction->attachEventHandler('OnCommit', function ($sender, $param) use (&$events) {
			$events[] = 'commit';
		});
		$transaction->attachEventHandler('OnRollback', function ($sender, $param) use (&$events) {
			$events[] = 'rollback';
		});
		$this->_connection->createCommand('INSERT INTO child(id,parent_id) VALUES (1,99)')->execute();
		try {
			$transaction->commit();
			$this->fail('Expected the deferred foreign key to fail the commit');
		} catch (\PDOException $e) {
		}
		$this->assertSame([], $events);

		$this->_connection->setActive(false);

		$this->assertSame(['rollback'], $events, 'The database still held the transaction, so the close rolled it back.');
		$this->assertFalse($transaction->getActive());
	}

	public function testClosingAfterACommitOnThePdoRaisesNothing()
	{
		$events = [];
		$transaction = $this->_connection->beginTransaction();
		$transaction->attachEventHandler('OnCommit', function ($sender, $param) use (&$events) {
			$events[] = 'commit';
		});
		$transaction->attachEventHandler('OnRollback', function ($sender, $param) use (&$events) {
			$events[] = 'rollback';
		});
		$this->_connection->createCommand('INSERT INTO foo(id,name) VALUES (1,\'a\')')->execute();
		$this->_connection->getPdoInstance()->commit();

		$this->_connection->setActive(false);

		$this->assertSame([], $events, 'The outcome is unknown to the transaction, so it raises nothing.');
		$this->assertFalse($transaction->getActive());
		$this->assertEquals(1, $this->countRowsOnOtherConnection());
	}

	public function testClosingAfterACommitRaisesOnlyOnCommit()
	{
		$events = [];
		$transaction = $this->_connection->beginTransaction();
		$transaction->attachEventHandler('OnCommit', function ($sender, $param) use (&$events) {
			$events[] = 'commit';
		});
		$transaction->attachEventHandler('OnRollback', function ($sender, $param) use (&$events) {
			$events[] = 'rollback';
		});
		$transaction->commit();
		$this->_connection->setActive(false);
		$this->assertSame(['commit'], $events);
	}

	public function testEndOnCloseOfAnInactiveTransactionDoesNothing()
	{
		$transaction = $this->_connection->beginTransaction();
		$transaction->rollback();
		$this->assertFalse($transaction->endOnClose());
	}

	public function testFailingRollbackRaisesNoEvent()
	{
		$raised = false;
		$transaction = $this->_connection->beginTransaction();
		$transaction->attachEventHandler('OnRollback', function ($sender, $param) use (&$raised) {
			$raised = true;
		});
		$this->_connection->getPdoInstance()->rollBack();

		try {
			$transaction->rollback();
			$this->fail('Expected PDOException for a transaction ended on the PDO');
		} catch (\PDOException $e) {
		}
		$this->assertFalse($raised);
	}

	public function testInactiveTransactionRaisesNoEvent()
	{
		$raised = false;
		$transaction = $this->_connection->beginTransaction();
		$transaction->commit();
		$transaction->attachEventHandler('OnCommit', function ($sender, $param) use (&$raised) {
			$raised = true;
		});
		$transaction->attachEventHandler('OnRollback', function ($sender, $param) use (&$raised) {
			$raised = true;
		});

		foreach (['commit', 'rollback'] as $method) {
			try {
				$transaction->$method();
				$this->fail('Expected TDbException for an inactive transaction');
			} catch (TDbException $e) {
			}
		}
		$this->assertFalse($raised);
	}

	public function testThrowingOnCommitHandlerPropagatesAfterCommit()
	{
		$secondRan = false;
		$transaction = $this->_connection->beginTransaction();
		$transaction->attachEventHandler('OnCommit', function ($sender, $param) {
			throw new \RuntimeException('handler failed');
		});
		$transaction->attachEventHandler('OnCommit', function ($sender, $param) use (&$secondRan) {
			$secondRan = true;
		});
		$this->_connection->createCommand('INSERT INTO foo(id,name) VALUES (1,\'a\')')->execute();

		try {
			$transaction->commit();
			$this->fail('Expected the handler exception');
		} catch (\RuntimeException $e) {
			$this->assertEquals('handler failed', $e->getMessage());
		}
		$this->assertFalse($secondRan);
		$this->assertFalse($transaction->getActive());
		$this->assertNull($this->_connection->getCurrentTransaction());
		$this->assertEquals(1, $this->countRowsOnOtherConnection());

		$next = $this->_connection->beginTransaction();
		$this->assertNotSame($transaction, $next);
		$this->assertFalse($next->hasEventHandler('OnCommit'));
		$next->rollback();
	}

	public function testThrowingOnRollbackHandlerPropagatesAfterRollback()
	{
		$transaction = $this->_connection->beginTransaction();
		$transaction->attachEventHandler('OnRollback', function ($sender, $param) {
			throw new \RuntimeException('handler failed');
		});
		$this->_connection->createCommand('INSERT INTO foo(id,name) VALUES (1,\'a\')')->execute();

		try {
			$transaction->rollback();
			$this->fail('Expected the handler exception');
		} catch (\RuntimeException $e) {
			$this->assertEquals('handler failed', $e->getMessage());
		}
		$this->assertFalse($transaction->getActive());
		$this->assertEquals(0, $this->countRowsOnOtherConnection());
	}

	public function testHandlerOnCurrentTransaction()
	{
		$deferred = [];
		$this->_connection->beginTransaction();
		$this->_connection->createCommand('INSERT INTO foo(id,name) VALUES (1,\'a\')')->execute();

		$current = $this->_connection->getCurrentTransaction();
		$this->assertInstanceOf(TDbTransaction::class, $current);
		$current->attachEventHandler('OnCommit', function ($sender, $param) use (&$deferred) {
			$deferred[] = 'announced';
		});
		$this->assertSame([], $deferred);

		$current->commit();
		$this->assertSame(['announced'], $deferred);
	}

	public function testHandlerMayBeginNewTransaction()
	{
		$inner = null;
		$transaction = $this->_connection->beginTransaction();
		$transaction->attachEventHandler('OnCommit', function ($sender, $param) use (&$inner) {
			$inner = $sender->getConnection()->beginTransaction();
			$sender->getConnection()->createCommand('INSERT INTO foo(id,name) VALUES (2,\'b\')')->execute();
			$inner->commit();
		});
		$this->_connection->createCommand('INSERT INTO foo(id,name) VALUES (1,\'a\')')->execute();
		$transaction->commit();

		$this->assertInstanceOf(TDbTransaction::class, $inner);
		$this->assertFalse($inner->getActive());
		$this->assertEquals(2, $this->countRowsOnOtherConnection());
	}

	public function testNestedBeginTransactionIsNotSupported()
	{
		$transaction = $this->_connection->beginTransaction();
		try {
			$this->_connection->beginTransaction();
			$this->fail('Expected TDbException for a nested transaction');
		} catch (TDbException $e) {
		}
		$this->assertSame($transaction, $this->_connection->getCurrentTransaction());
		$transaction->rollback();
	}

	/**
	 * Counts the committed rows of table foo through a second connection.
	 */
	private function countRowsOnOtherConnection(): int
	{
		$other = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$other->Active = true;
		$count = (int) $other->createCommand('SELECT COUNT(*) FROM foo')->queryScalar();
		$other->Active = false;
		return $count;
	}

	private function stubbedConnection(TDbTransactionTestPdoStub $pdo): TDbConnection
	{
		$conn = new TDbConnection();
		PradoUnit::setProp($conn, '_pdo', $pdo);
		PradoUnit::setProp($conn, '_active', true);
		return $conn;
	}

	public function testBeginTransactionIgnoresAFailedImplicitCommitBeforeBegin()
	{
		$pdo = new TDbTransactionTestPdoStub();
		$pdo->commitThrows = true;
		$conn = $this->stubbedConnection($pdo);

		$transaction = $conn->beginTransaction();

		$this->assertSame(['commit', 'begin'], $pdo->calls);
		$this->assertTrue($transaction->getActive());
		$this->assertSame($transaction, $conn->getCurrentTransaction());
	}

	public function testRollbackIgnoresAFailedPostTransactionFlush()
	{
		$pdo = new TDbTransactionTestPdoStub();
		$conn = $this->stubbedConnection($pdo);
		$transaction = $conn->beginTransaction();
		$rolledBack = 0;
		$transaction->attachEventHandler('OnRollback', function () use (&$rolledBack) {
			$rolledBack++;
		});
		$pdo->commitThrows = true;

		$this->assertTrue($conn->rollback());

		$this->assertSame(['commit', 'begin', 'rollBack', 'commit'], $pdo->calls);
		$this->assertFalse($transaction->getActive());
		$this->assertSame(1, $rolledBack);
	}

	public function testCommitCompletesWhenTheDriverNameCannotBeRead()
	{
		$pdo = new TDbTransactionTestPdoStub();
		$pdo->driver = 'sqlite';
		$conn = $this->stubbedConnection($pdo);
		$transaction = $conn->beginTransaction();
		$committed = 0;
		$transaction->attachEventHandler('OnCommit', function () use (&$committed) {
			$committed++;
		});
		$pdo->attributeThrows = true;

		$this->assertTrue($conn->commit());

		$this->assertSame(['begin', 'commit'], $pdo->calls);
		$this->assertFalse($transaction->getActive());
		$this->assertSame(1, $committed);
	}

	public function testEndOnCloseReportsARollbackThatTheDriverCannotSend()
	{
		$pdo = new TDbTransactionTestPdoStub();
		$pdo->rollBackThrows = true;
		$transaction = new TDbTransaction($this->stubbedConnection($pdo));

		$this->assertTrue($transaction->endOnClose());
		$this->assertSame(['rollBack'], $pdo->calls);
		$this->assertFalse($transaction->getActive());
	}

	public function testEndOnCloseIsFalseWhenTheDatabaseHoldsNoTransaction()
	{
		$pdo = new TDbTransactionTestPdoStub();
		$pdo->inTx = false;
		$transaction = new TDbTransaction($this->stubbedConnection($pdo));

		$this->assertFalse($transaction->endOnClose());
		$this->assertSame([], $pdo->calls);
		$this->assertFalse($transaction->getActive());
	}

	public function testActivateMarksADeactivatedTransactionActive()
	{
		$transaction = $this->_connection->beginTransaction();
		$transaction->deactivate();
		$this->assertFalse($transaction->getActive());

		PradoUnit::invoke($transaction, 'activate');

		$this->assertTrue($transaction->getActive());
		$transaction->rollback();
		$this->assertFalse($transaction->getActive());
	}

	public function testEndOnCloseWithoutAPdoConnectionIsFalse()
	{
		$transaction = new TDbTransaction($this->createMock(IDataConnection::class));

		$this->assertFalse($transaction->endOnClose());
		$this->assertFalse($transaction->getActive());
	}

	public function testRollbackCompletesWhenTheDriverRaisesErrors()
	{
		$pdo = new TDbTransactionTestPdoStub();
		$conn = $this->stubbedConnection($pdo);
		$first = $conn->beginTransaction();
		$pdo->commitErrors = true;
		$this->assertTrue($conn->rollback());
		$this->assertSame(['commit', 'begin', 'rollBack', 'commit'], $pdo->calls);
		$this->assertFalse($first->getActive());

		$pdo->commitErrors = false;
		$second = $conn->beginTransaction();
		$pdo->attributeErrors = true;
		$this->assertTrue($conn->rollback());
		$this->assertSame(['commit', 'begin', 'rollBack', 'commit', 'commit', 'begin', 'rollBack'], $pdo->calls);
		$this->assertFalse($second->getActive());
	}
}
