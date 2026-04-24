<?php

use Prado\Data\TDbConnection;
use Prado\Data\TDbSerialTransaction;
use Prado\Data\TDbTransaction;
use Prado\TApplication;

if (!defined('TEST_DB_FILE')) {
	define('TEST_DB_FILE', __DIR__ . '/db/test.db');
}

class TDbSerialTransactionTest extends PHPUnit\Framework\TestCase
{
	private $_connection;

	protected function setUp(): void
	{
		new TApplication(__DIR__, false, TApplication::CONFIG_TYPE_PHP);

		$this->_connection = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$this->_connection->Active = true;
		$this->_connection->setTransactionClass(TDbSerialTransaction::class);
		$this->_connection->createCommand('CREATE TABLE foo (id INTEGER NOT NULL PRIMARY KEY, name VARCHAR(8))')->execute();
	}

	protected function tearDown(): void
	{
		$this->_connection = null;
		@unlink(TEST_DB_FILE);
	}

	public function testExtendsTDbTransaction()
	{
		$transaction = new TDbSerialTransaction($this->_connection);
		$this->assertInstanceOf(TDbTransaction::class, $transaction);
	}

	public function testBeginTransactionReturnsSerialTransaction()
	{
		$transaction = $this->_connection->beginTransaction();
		$this->assertInstanceOf(TDbSerialTransaction::class, $transaction);
	}

	public function testSerialTransactionStaysActiveAfterCommit()
	{
		$transaction = $this->_connection->beginTransaction();
		
		$this->_connection->createCommand('INSERT INTO foo(id,name) VALUES (1,\'test\')')->execute();
		$transaction->commit();
		
		$this->assertTrue($transaction->getActive(), 'Serial transaction should stay active after commit when autoCommit not supported');
		
		$results = $this->_connection->createCommand('SELECT * FROM foo')->query()->readAll();
		$this->assertCount(1, $results);
	}

	public function testSerialTransactionStaysActiveAfterRollback()
	{
		$transaction = $this->_connection->beginTransaction();
		
		$this->_connection->createCommand('INSERT INTO foo(id,name) VALUES (1,\'test\')')->execute();
		$transaction->rollBack();
		
		$this->assertTrue($transaction->getActive(), 'Serial transaction should stay active after rollback when autoCommit not supported');
		
		$results = $this->_connection->createCommand('SELECT * FROM foo')->query()->readAll();
		$this->assertCount(0, $results);
	}

	public function testSerialTransactionMultipleCycles()
	{
		$transaction = $this->_connection->beginTransaction();
		
		for ($i = 1; $i <= 3; $i++) {
			$this->_connection->createCommand("INSERT INTO foo(id,name) VALUES ($i,'row$i\')")->execute();
			$transaction->commit();
			$this->assertTrue($transaction->getActive(), "Transaction should stay active after cycle $i");
		}
		
		$results = $this->_connection->createCommand('SELECT * FROM foo')->query()->readAll();
		$this->assertCount(3, $results);
	}

	public function testIsTransactionCompleteWithNoAutoCommit()
	{
		$connection = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$connection->setActive(true);
		
		$serialTxn = new TDbSerialTransaction($connection);
		$this->assertFalse($connection->getHasAutoCommit(), 'SQLite does not support autoCommit');
		
		$method = new \ReflectionMethod(TDbSerialTransaction::class, 'isTransactionComplete');
		$method->setAccessible(true);
		
		$result = $method->invoke($serialTxn);
		$this->assertFalse($result, 'isTransactionComplete should return false when autoCommit not available');
		$this->assertTrue($serialTxn->getActive(), 'Transaction should remain active when autoCommit not available');
	}

	public function testRestartTransactionWithFirebirdDriver()
	{
		$mockPdo = $this->getMockBuilder(\PDO::class)
			->disableOriginalConstructor()
			->getMock();
		
		$mockPdo->method('getAttribute')
			->willReturnMap([
				[\PDO::ATTR_DRIVER_NAME, 'firebird'],
				[\PDO::ATTR_AUTOCOMMIT, false],
			]);
		
		$mockPdo->expects($this->once())
			->method('commit');
		$mockPdo->expects($this->once())
			->method('beginTransaction');
		
		$connection = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$connection->setActive(true);
		
		$ref = new \ReflectionProperty(TDbConnection::class, '_pdo');
		$ref->setAccessible(true);
		$ref->setValue($connection, $mockPdo);
		
		$serialTxn = new TDbSerialTransaction($connection);
		
		$method = new \ReflectionMethod(TDbSerialTransaction::class, 'restartTransaction');
		$method->setAccessible(true);
		$method->invoke($serialTxn);
	}

	public function testRestartTransactionWithNonFirebirdDriver()
	{
		$mockPdo = $this->getMockBuilder(\PDO::class)
			->disableOriginalConstructor()
			->getMock();
		
		$mockPdo->method('getAttribute')
			->willReturnMap([
				[\PDO::ATTR_DRIVER_NAME, 'pgsql'],
				[\PDO::ATTR_AUTOCOMMIT, false],
			]);
		
		$mockPdo->expects($this->never())
			->method('commit');
		$mockPdo->expects($this->once())
			->method('beginTransaction');
		
		$connection = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$connection->setActive(true);
		
		$ref = new \ReflectionProperty(TDbConnection::class, '_pdo');
		$ref->setAccessible(true);
		$ref->setValue($connection, $mockPdo);
		
		$serialTxn = new TDbSerialTransaction($connection);
		
		$method = new \ReflectionMethod(TDbSerialTransaction::class, 'restartTransaction');
		$method->setAccessible(true);
		$method->invoke($serialTxn);
	}

	public function testCommitWithConnectionNotActiveThrowsException()
	{
		$sql = 'INSERT INTO foo(id,name) VALUES (1,\'test\')';
		$transaction = $this->_connection->beginTransaction();
		$this->_connection->createCommand($sql)->execute();
		
		$this->_connection->Active = false;
		
		$this->expectException(\Prado\Exceptions\TDbException::class);
		$transaction->commit();
	}

	public function testRollbackWithTransactionNotActiveThrowsException()
	{
		$transaction = $this->_connection->beginTransaction();
		
		$method = new \ReflectionMethod(TDbTransaction::class, 'setActive');
		$method->invoke($transaction, false);
		
		$this->expectException(\Prado\Exceptions\TDbException::class);
		$transaction->rollBack();
	}

	public function testGetConnection()
	{
		$transaction = new TDbSerialTransaction($this->_connection);
		
		$this->assertSame($this->_connection, $transaction->getConnection());
	}

	public function testTransactionInitiallyActive()
	{
		$transaction = $this->_connection->beginTransaction();
		
		$this->assertTrue($transaction->getActive());
	}

	public function testReuseSameTransactionObjectAcrossMultipleOperations()
	{
		$transaction = $this->_connection->beginTransaction();
		
		$this->_connection->createCommand('INSERT INTO foo(id,name) VALUES (1,\'a\')')->execute();
		$transaction->commit();
		
		$this->_connection->createCommand('INSERT INTO foo(id,name) VALUES (2,\'b\')')->execute();
		$transaction->commit();
		
		$this->_connection->createCommand('INSERT INTO foo(id,name) VALUES (3,\'c\')')->execute();
		$transaction->rollBack();
		
		$results = $this->_connection->createCommand('SELECT * FROM foo ORDER BY id')->query()->readAll();
		$this->assertCount(2, $results);
		$this->assertEquals('a', $results[0]['name']);
		$this->assertEquals('b', $results[1]['name']);
	}
}