<?php

namespace Prado\Test\Unit\Data;

use Prado\Data\TDbConnection;
use Prado\Data\TDbCommand;
use Prado\Data\TDbDataReader;
use Prado\Exceptions\TDbException;
use Prado\TApplication;
use Prado\TApplicationMode;

if (!defined('TEST_DB_FILE')) {
	define('TEST_DB_FILE', __DIR__ . '/db/test.db');
}

class TDbCommandTest extends \PHPUnit\Framework\TestCase
{
	private $_connection;

	protected function setUp(): void
	{
		// Defensive unlink
		@unlink(TEST_DB_FILE);

		// create application just to provide application mode
		(new TApplication(__DIR__, false, TApplication::CONFIG_TYPE_PHP))->setMode(TApplicationMode::Debug);

		$this->_connection = new TDbConnection('sqlite:' . TEST_DB_FILE);
		$this->_connection->Active = true;
		$this->_connection->createCommand('CREATE TABLE foo (id INTEGER NOT NULL PRIMARY KEY, name VARCHAR(8))')->execute();
		$this->_connection->createCommand('INSERT INTO foo (name) VALUES (\'my name\')')->execute();
		$this->_connection->createCommand('INSERT INTO foo (name) VALUES (\'my name 2\')')->execute();
	}

	protected function tearDown(): void
	{
		if ($this->_connection !== null) {
			$this->_connection->Active = false;
			$this->_connection = null;
		}
		@unlink(TEST_DB_FILE);
	}

	public function testGetText()
	{
		$sql = 'SELECT * FROM foo';
		$command = $this->_connection->createCommand($sql);
		$this->assertEquals($command->Text, $sql);
	}

	public function testSetText()
	{
		$sql = 'SELECT name FROM foo';
		$command = $this->_connection->createCommand($sql);
		$row = $command->query()->read();
		$this->assertEquals($row['name'], 'my name');

		$newSql = 'SELECT id FROM foo';
		$command->Text = $newSql;
		$this->assertEquals($command->Text, $newSql);
		$row = $command->query()->read();

		$this->assertEquals($row['id'], '1');
	}

	public function testConnection()
	{
		$sql = 'SELECT name FROM foo';
		$command = $this->_connection->createCommand($sql);
		$this->assertTrue($command->Connection instanceof TDbConnection);
	}

	public function testPrepare()
	{
		$sql = 'SELECT name FROM foo';
		$command = $this->_connection->createCommand($sql);
		$this->assertTrue($command->PdoStatement === null);
		$command->prepare();
		$this->assertTrue($command->PdoStatement instanceof \PDOStatement);

		try {
			$command->Text = 'Bad SQL';
			$command->prepare();
			$this->fail('Expected exception is not raised');
		} catch (TDbException $e) {
		}
	}

	public function testCancel()
	{
		$sql = 'SELECT name FROM foo';
		$command = $this->_connection->createCommand($sql);
		$command->prepare();
		$this->assertTrue($command->PdoStatement instanceof \PDOStatement);
		$command->cancel();
		$this->assertEquals($command->PdoStatement, null);
	}

	public function testBindParameter()
	{
		$sql = 'INSERT INTO foo (id,name) VALUES (3,:name)';
		$command = $this->_connection->createCommand($sql);
		$name = 'new name';
		$command->bindParameter(':name', $name);
		$command->execute();
		$insertedName = $this->_connection->createCommand('SELECT name FROM foo WHERE id=3')->queryScalar();
		$this->assertEquals($name, $insertedName);
	}

	public function testBindValue()
	{
		$sql = 'INSERT INTO foo (id,name) VALUES (3,:name)';
		$command = $this->_connection->createCommand($sql);
		$command->bindValue(':name', 'new name');
		$command->execute();
		$insertedName = $this->_connection->createCommand('SELECT name FROM foo WHERE id=3')->queryScalar();
		$this->assertEquals('new name', $insertedName);
	}

	public function testExecute()
	{
		// test unprepared SQL execution
		$sql = 'INSERT INTO foo (name) VALUES (\'new name\')';
		$command = $this->_connection->createCommand($sql);
		$n = $command->execute();
		$this->assertEquals($n, 1);
		$command->execute();
		$count = $this->_connection->createCommand('SELECT COUNT(id) AS id_count FROM foo')->queryScalar();
		$this->assertEquals('4', $count);

		// test prepared SQL execution
		$sql = 'INSERT INTO foo (name) VALUES (\'new name\')';
		$command = $this->_connection->createCommand($sql);
		$command->prepare();
		$command->execute();
		$command->execute();
		$count = $this->_connection->createCommand('SELECT COUNT(id) AS id_count FROM foo')->queryScalar();
		$this->assertEquals('6', $count);

		// test exception raising
		try {
			$command = $this->_connection->createCommand('bad SQL');
			$command->execute();
			$this->fail('Expected exception is not raised');
		} catch (TDbException $e) {
		}
	}

	public function testQuery()
	{
		// test unprepared SQL query
		$sql = 'SELECT * FROM foo';
		$reader = $this->_connection->createCommand($sql)->query();
		$this->assertTrue($reader instanceof TDbDataReader);

		// test unprepared SQL query
		$sql = 'SELECT * FROM foo';
		$command = $this->_connection->createCommand($sql);
		$command->prepare();
		$reader = $command->query();
		$this->assertTrue($reader instanceof TDbDataReader);

		// test exception raising
		try {
			$command = $this->_connection->createCommand('bad SQL');
			$command->query();
			$this->fail('Expected exception is not raised');
		} catch (TDbException $e) {
		}
	}

	public function testQueryRow()
	{
		// test unprepared SQL query
		$sql = 'SELECT * FROM foo';
		$row = $this->_connection->createCommand($sql)->queryRow();
		$this->assertEquals(1, $row['id']);
		$this->assertSame('my name', $row['name']);

		// test unprepared SQL query
		$sql = 'SELECT * FROM foo';
		$command = $this->_connection->createCommand($sql);
		$command->prepare();
		$row = $command->queryRow();
		$this->assertEquals(1, $row['id']);
		$this->assertSame('my name', $row['name']);

		// test exception raising
		try {
			$command = $this->_connection->createCommand('bad SQL');
			$command->queryRow();
			$this->fail('Expected exception is not raised');
		} catch (TDbException $e) {
		}
	}

	public function testQueryScalar()
	{
		// test unprepared SQL query
		$sql = 'SELECT * FROM foo';
		$id = $this->_connection->createCommand($sql)->queryScalar();
		$this->assertEquals(1, $id);

		// test unprepared SQL query
		$sql = 'SELECT * FROM foo';
		$command = $this->_connection->createCommand($sql);
		$command->prepare();
		$row = $command->queryScalar();
		$this->assertEquals(1, $id);

		// test exception raising
		try {
			$command = $this->_connection->createCommand('bad SQL');
			$command->queryScalar();
			$this->fail('Expected exception is not raised');
		} catch (TDbException $e) {
		}
	}

	public function testSerializeDropsThePreparedStatement()
	{
		$command = $this->_connection->createCommand('SELECT name FROM foo WHERE id = 1');
		$command->prepare();
		$this->assertInstanceOf(\PDOStatement::class, $command->getPdoStatement());

		$copy = unserialize(serialize($command));

		$this->assertSame('SELECT name FROM foo WHERE id = 1', $copy->getText());
		$this->assertNull($copy->getPdoStatement());
	}

	public function testBindParameterWithLength()
	{
		$command = $this->_connection->createCommand('INSERT INTO foo (id,name) VALUES (3,:name)');
		$name = 'sized';
		$command->bindParameter(':name', $name, \PDO::PARAM_STR, 8);
		$command->execute();
		$this->assertSame('sized', $this->_connection->createCommand('SELECT name FROM foo WHERE id=3')->queryScalar());
	}

	public function testColumnTypeFromValueWhenTheDriverIsUnknown()
	{
		$command = new TDbCommand(new TDbConnection('no-driver-dsn'), 'SELECT 1');
		$this->assertSame(\PDO::PARAM_INT, $command->getColumnTypeFromValue(5));
		$this->assertSame(\PDO::PARAM_STR, $command->getColumnTypeFromValue('a'));
		$this->assertSame(\PDO::PARAM_BOOL, $command->getColumnTypeFromValue(true));
		$this->assertSame(\PDO::PARAM_NULL, $command->getColumnTypeFromValue(null));
		$this->assertNull($command->getColumnTypeFromValue(1.5));
	}

	public function testColumnTypeFromValueIsNullForUntypedDriver()
	{
		$command = new TDbCommand(new TDbConnection('ibm:DATABASE=sample'), 'SELECT 1');
		$this->assertNull($command->getColumnTypeFromValue(5));
		$this->assertNull($command->getColumnTypeFromValue('a'));
	}

	public function testBindParameterWithTypeOnly()
	{
		$command = $this->_connection->createCommand('INSERT INTO foo (id,name) VALUES (3,:name)');
		$name = 'typed';
		$command->bindParameter(':name', $name, \PDO::PARAM_STR);
		$command->execute();
		$this->assertSame('typed', $this->_connection->createCommand('SELECT name FROM foo WHERE id=3')->queryScalar());
	}

	public function testDebugStatementTextIsTheTextUntilPrepared()
	{
		$command = $this->_connection->createCommand('SELECT * FROM foo');
		$this->assertSame('SELECT * FROM foo', $command->getDebugStatementText());
		$command->prepare();
		$this->assertSame('SELECT * FROM foo', $command->getDebugStatementText());
		$this->assertSame($command->getPdoStatement()->queryString, $command->getDebugStatementText());
	}

	public function testQueryRowAsNumericArray()
	{
		$this->assertSame([1, 'my name'], $this->_connection->createCommand('SELECT id, name FROM foo ORDER BY id')->queryRow(false));
	}
}
