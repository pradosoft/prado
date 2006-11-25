<?php

require_once(dirname(__FILE__).'/../phpunit2.php');

Prado::using('System.Data.*');

define('TEST_DB_FILE',dirname(__FILE__).'/db/test.db');

class FooRecord extends TComponent
{
	public $id;
	private $_name;
	public $param;

	public function __construct($param)
	{
		$this->param=$param;
	}

	public function getName()
	{
		return $this->_name;
	}

	public function setName($value)
	{
		$this->_name=$value;
	}
}

/**
 * @package System.Data.PDO
 */
class TDbDataReaderTest extends PHPUnit2_Framework_TestCase
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

	public function testRead()
	{
		$reader=$this->_connection->createCommand('SELECT * FROM foo')->query();
		$row=$reader->read();
		$this->assertTrue($row['id']==='1' && $row['name']==='my name');
		$row=$reader->read();
		$this->assertTrue($row['id']==='2' && $row['name']==='my name 2');
		$row=$reader->read();
		$this->assertFalse($row);
	}

	public function testReadColumn()
	{
		$reader=$this->_connection->createCommand('SELECT * FROM foo')->query();
		$this->assertEquals($reader->readColumn(0),'1');
		$this->assertEquals($reader->readColumn(1),'my name 2');
		$this->assertFalse($reader->readColumn(0));
	}

	public function testReadObject()
	{
		$reader=$this->_connection->createCommand('SELECT * FROM foo')->query();
		$object=$reader->readObject('FooRecord',array('object'));
		$this->assertEquals($object->id,'1');
		$this->assertEquals($object->Name,'my name');
		$this->assertEquals($object->param,'object');
	}

	public function testReadAll()
	{
		$reader=$this->_connection->createCommand('SELECT * FROM foo')->query();
		$rows=$reader->readAll();
		$this->assertEquals(count($rows),2);
		$row=$rows[0];
		$this->assertTrue($row['id']==='1' && $row['name']==='my name');
		$row=$rows[1];
		$this->assertTrue($row['id']==='2' && $row['name']==='my name 2');

		$reader=$this->_connection->createCommand('SELECT * FROM foo WHERE id=3')->query();
		$rows=$reader->readAll();
		$this->assertEquals($rows,array());
	}

	public function testClose()
	{
		$reader=$this->_connection->createCommand('SELECT * FROM foo')->query();
		$row=$reader->read();
		$this->assertFalse($reader->IsClosed);
		$reader->close();
		$this->assertTrue($reader->IsClosed);
		try
		{
			$reader->read();
			$this->fail('Expected exception is not raised');
		}
		catch(Exception $e)
		{
		}
	}

	public function testRowCount()
	{
		// unable to test because SQLite doesn't support row count
	}

	public function testColumnCount()
	{
		$reader=$this->_connection->createCommand('SELECT * FROM foo')->query();
		$this->assertEquals($reader->ColumnCount,2);

		$reader=$this->_connection->createCommand('SELECT * FROM foo WHERE id=11')->query();
		$this->assertEquals($reader->ColumnCount,2);
	}

	public function testForeach()
	{
		$ids=array();
		$reader=$this->_connection->createCommand('SELECT * FROM foo')->query();
		foreach($reader as $row)
			$ids[]=$row['id'];
		$this->assertEquals(count($ids),2);
		$this->assertTrue($ids[0]==='1' && $ids[1]==='2');

		try
		{
			foreach($reader as $row)
				$ids[]=$row['id'];
			$this->fail('Expected exception is not raised');
		}
		catch(TDbException $e)
		{
		}
	}

	public function testFetchMode()
	{
		$reader=$this->_connection->createCommand('SELECT * FROM foo')->query();

		$reader->FetchMode=PDO::FETCH_NUM;
		$row=$reader->read();
		$this->assertFalse(isset($row['id']));
		$this->assertTrue(isset($row[0]));

		$reader->FetchMode=PDO::FETCH_ASSOC;
		$row=$reader->read();
		$this->assertTrue(isset($row['id']));
		$this->assertFalse(isset($row[0]));
	}

	public function testBindColumn()
	{
		$reader=$this->_connection->createCommand('SELECT * FROM foo')->query();
		$reader->bindColumn(1,$id);
		$reader->bindColumn(2,$name);
		$reader->read();
		$this->assertEquals($id,'1');
		$this->assertEquals($name,'my name');
		$reader->read();
		$this->assertEquals($id,'2');
		$this->assertEquals($name,'my name 2');
		$reader->read();
		$this->assertEquals($id,'2');
		$this->assertEquals($name,'my name 2');
	}
}

?>