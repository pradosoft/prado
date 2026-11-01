<?php

namespace Prado\Test\Unit\Data;

use Prado\Data\TDbConnection;
use Prado\Data\TDbTransaction;
use Prado\Exceptions\TDbException;
use Prado\TApplication;
use Prado\TApplicationMode;

if (!defined('TEST_DB_FILE')) {
	define('TEST_DB_FILE', __DIR__ . '/db/test.db');
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
			$this->fail('Expected PDOException for a nested transaction');
		} catch (\PDOException $e) {
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
}
