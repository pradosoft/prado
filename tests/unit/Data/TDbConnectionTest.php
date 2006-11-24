<?php

require_once(dirname(__FILE__).'/../phpunit2.php');

Prado::using('System.Data.*');

define('TEST_DB_FILE',dirname(__FILE__).'/db/test.db');
define('TEST_DB_FILE2',dirname(__FILE__).'/db/test2.db');

/**
 * @package System.Data.PDO
 */
class TDbConnectionTest extends PHPUnit2_Framework_TestCase
{
	private $_connection1;
	private $_connection2;

	public function setUp()
	{
		@unlink(TEST_DB_FILE);
		@unlink(TEST_DB_FILE2);
		$this->_connection1=new TDbConnection('sqlite:'.TEST_DB_FILE);
		$this->_connection1->Active=true;
		$this->_connection1->createCommand('CREATE TABLE foo (id INTEGER NOT NULL PRIMARY KEY, name VARCHAR(8))')->execute();
		$this->_connection2=new TDbConnection('sqlite:'.TEST_DB_FILE2);
	}

	public function tearDown()
	{
		$this->_connection1=null;
		$this->_connection2=null;
	}

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

		$command=$this->_connection1->createCommand($sql);
		$this->assertTrue($command instanceof TDbCommand);
	}

	public function testBeginTransaction()
	{
		$sql='INSERT INTO foo(id,name) VALUES (1,\'my name\')';
		$transaction=$this->_connection1->beginTransaction();
		try
		{
			$this->_connection1->createCommand($sql)->execute();
			$this->_connection1->createCommand($sql)->execute();
			$this->fail('Expected exception not raised');
			$transaction->commit();
		}
		catch(Exception $e)
		{
			$transaction->rollBack();
			$reader=$this->_connection1->createCommand('SELECT * FROM foo')->query();
			$this->assertFalse($reader->read());
		}
	}

	public function testLastInsertID()
	{
		$sql='INSERT INTO foo(name) VALUES (\'my name\')';
		$this->_connection1->createCommand($sql)->execute();
		$value=$this->_connection1->LastInsertID;
		$this->assertEquals($this->_connection1->LastInsertID,'1');
	}

	public function testQuoteString()
	{
		$str="this is 'my' name";
		$expectedStr="'this is ''my'' name'";
		$this->assertEquals($expectedStr,$this->_connection1->quoteString($str));
	}

	public function testColumnNameCase()
	{
		$this->assertEquals(TDbColumnCaseMode::Preserved,$this->_connection1->ColumnCase);
		$this->_connection1->ColumnCase=TDbColumnCaseMode::LowerCase;
		$this->assertEquals(TDbColumnCaseMode::LowerCase,$this->_connection1->ColumnCase);
	}

	public function testNullConversion()
	{
		$this->assertEquals(TDbNullConversionMode::Preserved,$this->_connection1->NullConversion);
		$this->_connection1->NullConversion=TDbNullConversionMode::NullToEmptyString;
		$this->assertEquals(TDbNullConversionMode::NullToEmptyString,$this->_connection1->NullConversion);
	}
}

?>