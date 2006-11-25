<?php

require_once(dirname(__FILE__).'/../phpunit2.php');

Prado::using('System.Data.*');

define('TEST_DB_FILE',dirname(__FILE__).'/db/test.db');

/**
 * @package System.Data.PDO
 */
class TDbCommandTest extends PHPUnit2_Framework_TestCase
{
	private $_connection;

	public function setUp()
	{
		@unlink(TEST_DB_FILE);
		$this->_connection=new TDbConnection('sqlite:'.TEST_DB_FILE);
		$this->_connection->Active=true;
		$this->_connection->createCommand('CREATE TABLE foo (id INTEGER NOT NULL PRIMARY KEY, name VARCHAR(8))')->execute();
		$this->_connection->createCommand('INSERT INTO foo (name) VALUES (\'my name\')')->execute();
		$this->_connection->createCommand('INSERT INTO foo (name) VALUES (\'my name 2\')')->execute();
	}

	public function tearDown()
	{
		$this->_connection=null;
	}

	public function testGetText()
	{
		$sql='SELECT * FROM foo';
		$command=$this->_connection->createCommand($sql);
		$this->assertEquals($command->Text,$sql);
	}

	public function testSetText()
	{
		$sql='SELECT name FROM foo';
		$command=$this->_connection->createCommand($sql);
		$row=$command->query()->read();
		$this->assertEquals($row['name'],'my name');

		$newSql='SELECT id FROM foo';
		$command->Text=$newSql;
		$this->assertEquals($command->Text,$newSql);
		$row=$command->query()->read();

		$this->assertEquals($row['id'],'1');
	}

	public function testConnection()
	{
		$sql='SELECT name FROM foo';
		$command=$this->_connection->createCommand($sql);
		$this->assertTrue($command->Connection instanceof TDbConnection);
	}

	public function testPrepare()
	{
		$sql='SELECT name FROM foo';
		$command=$this->_connection->createCommand($sql);
		$this->assertTrue($command->PdoStatement===null);
		$command->prepare();
		$this->assertTrue($command->PdoStatement instanceof PDOStatement);

		try
		{
			$command->Text='Bad SQL';
			$command->prepare();
			$this->fail('Expected exception is not raised');
		}
		catch(TDbException $e)
		{
		}
	}

	public function testCancel()
	{
		$sql='SELECT name FROM foo';
		$command=$this->_connection->createCommand($sql);
		$command->prepare();
		$this->assertTrue($command->PdoStatement instanceof PDOStatement);
		$command->cancel();
		$this->assertEquals($command->PdoStatement,null);
	}

	public function testBindParameter()
	{
		$sql='INSERT INTO foo (id,name) VALUES (3,:name)';
		$command=$this->_connection->createCommand($sql);
		$name='new name';
		$command->bindParameter(':name',$name);
		$command->execute();
		$insertedName=$this->_connection->createCommand('SELECT name FROM foo WHERE id=3')->queryScalar();
		$this->assertEquals($name,$insertedName);
	}

	public function testBindValue()
	{
		$sql='INSERT INTO foo (id,name) VALUES (3,:name)';
		$command=$this->_connection->createCommand($sql);
		$command->bindValue(':name','new name');
		$command->execute();
		$insertedName=$this->_connection->createCommand('SELECT name FROM foo WHERE id=3')->queryScalar();
		$this->assertEquals('new name',$insertedName);
	}

	public function testExecute()
	{
		// test unprepared SQL execution
		$sql='INSERT INTO foo (name) VALUES (\'new name\')';
		$command=$this->_connection->createCommand($sql);
		$command->execute();
		$command->execute();
		$count=$this->_connection->createCommand('SELECT COUNT(id) AS id_count FROM foo')->queryScalar();
		$this->assertEquals('4',$count);

		// test prepared SQL execution
		$sql='INSERT INTO foo (name) VALUES (\'new name\')';
		$command=$this->_connection->createCommand($sql);
		$command->prepare();
		$command->execute();
		$command->execute();
		$count=$this->_connection->createCommand('SELECT COUNT(id) AS id_count FROM foo')->queryScalar();
		$this->assertEquals('6',$count);

		// test exception raising
		try
		{
			$command=$this->_connection->createCommand('bad SQL');
			$command->execute();
			$this->fail('Expected exception is not raised');
		}
		catch(TDbException $e)
		{
		}
	}

	public function testQuery()
	{
		// test unprepared SQL query
		$sql='SELECT * FROM foo';
		$reader=$this->_connection->createCommand($sql)->query();
		$this->assertTrue($reader instanceof TDbDataReader);

		// test unprepared SQL query
		$sql='SELECT * FROM foo';
		$command=$this->_connection->createCommand($sql);
		$command->prepare();
		$reader=$command->query();
		$this->assertTrue($reader instanceof TDbDataReader);

		// test exception raising
		try
		{
			$command=$this->_connection->createCommand('bad SQL');
			$command->query();
			$this->fail('Expected exception is not raised');
		}
		catch(TDbException $e)
		{
		}
	}

	public function testQueryRow()
	{
		// test unprepared SQL query
		$sql='SELECT * FROM foo';
		$row=$this->_connection->createCommand($sql)->queryRow();
		$this->assertTrue($row['id']==='1' && $row['name']==='my name');

		// test unprepared SQL query
		$sql='SELECT * FROM foo';
		$command=$this->_connection->createCommand($sql);
		$command->prepare();
		$row=$command->queryRow();
		$this->assertTrue($row['id']==='1' && $row['name']==='my name');

		// test exception raising
		try
		{
			$command=$this->_connection->createCommand('bad SQL');
			$command->queryRow();
			$this->fail('Expected exception is not raised');
		}
		catch(TDbException $e)
		{
		}
	}

	public function testQueryScalar()
	{
		// test unprepared SQL query
		$sql='SELECT * FROM foo';
		$id=$this->_connection->createCommand($sql)->queryScalar();
		$this->assertTrue($id==='1');

		// test unprepared SQL query
		$sql='SELECT * FROM foo';
		$command=$this->_connection->createCommand($sql);
		$command->prepare();
		$row=$command->queryScalar();
		$this->assertTrue($id==='1');

		// test exception raising
		try
		{
			$command=$this->_connection->createCommand('bad SQL');
			$command->queryScalar();
			$this->fail('Expected exception is not raised');
		}
		catch(TDbException $e)
		{
		}
	}
}

?>