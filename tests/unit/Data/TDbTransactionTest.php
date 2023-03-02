<?php

use Prado\Data\TDbConnection;
use Prado\TApplication;

if (!defined('TEST_DB_FILE')) {
	define('TEST_DB_FILE', __DIR__ . '/db/test.db');
}

class TDbTransactionTest extends PHPUnit\Framework\TestCase
{
	private $_connection;

	protected function setUp(): void
	{
		@unlink(TEST_DB_FILE);

		// create application just to provide application mode
		new TApplication(__DIR__, false, TApplication::CONFIG_TYPE_PHP);

		$this->_connection = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$this->_connection->Active = true;
		$this->_connection->createCommand('CREATE TABLE foo (id INTEGER NOT NULL PRIMARY KEY, name VARCHAR(8))')->execute();
	}

	protected function tearDown(): void
	{
		$this->_connection = null;
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
}
