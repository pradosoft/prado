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
/*
	public function testActive()
	{
	    $this->assertFalse($this->_connection2->Active);

	    $this->_connection2->Active=true;
	    $this->assertTrue($this->_connection2->Active);
	    $pdo=$this->_connection2->PdoInstance;
	    // test setting Active repeatedly doesn't re-connect DB
	    $this->_connection2->Active=true;
	    $this->assertTrue($pdo===$this->_connection2->PdoInstance);

		$this->_connection2->Active=false;
	    $this->assertFalse($this->_connection2->Active);
	}

	public function testCreateCommand()
	{
		$sql='CREATE TABLE foo (id INTEGER NOT NULL PRIMARY KEY, name VARCHAR(8))';
		try
		{
			$this->_connection2->createCommand($sql);
			$this->fail('Expected exception is not raised');
		}
		catch(TDbException $e)
		{
		}

		$command=$this->_connection->createCommand($sql);
		$this->assertTrue($command instanceof TDbCommand);
	}

	public function testBeginTransaction()
	{
		$sql='INSERT INTO foo(id,name) VALUES (1,\'my name\')';
		$transaction=$this->_connection->beginTransaction();
		try
		{
			$this->_connection->createCommand($sql)->execute();
			$this->_connection->createCommand($sql)->execute();
			$this->fail('Expected exception not raised');
			$transaction->commit();
		}
		catch(Exception $e)
		{
			$transaction->rollBack();
			$reader=$this->_connection->createCommand('SELECT * FROM foo')->query();
			$this->assertFalse($reader->read());
		}
	}

	public function testLastInsertID()
	{
		$sql='INSERT INTO foo(name) VALUES (\'my name\')';
		$this->_connection->createCommand($sql)->execute();
		$value=$this->_connection->LastInsertID;
		$this->assertEquals($this->_connection->LastInsertID,'1');
	}

	public function testQuoteString()
	{
		$str="this is 'my' name";
		$expectedStr="'this is ''my'' name'";
		$this->assertEquals($expectedStr,$this->_connection->quoteString($str));
	}

	public function testColumnNameCase()
	{
		$this->assertEquals(TDbColumnCaseMode::Preserved,$this->_connection->ColumnCase);
		$this->_connection->ColumnCase=TDbColumnCaseMode::LowerCase;
		$this->assertEquals(TDbColumnCaseMode::LowerCase,$this->_connection->ColumnCase);
	}

	public function testNullConversion()
	{
		$this->assertEquals(TDbNullConversionMode::Preserved,$this->_connection->NullConversion);
		$this->_connection->NullConversion=TDbNullConversionMode::NullToEmptyString;
		$this->assertEquals(TDbNullConversionMode::NullToEmptyString,$this->_connection->NullConversion);
	}
	*/
}

?>