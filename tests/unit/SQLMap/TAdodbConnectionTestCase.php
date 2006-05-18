<?php

require_once(SQLMAP_DIR.'/TMapper.php');

/**
 * @package System.DataAccess
 */
class TAdodbConnectionTestCase extends UnitTestCase
{
	protected $db_file;

	function setup()
	{
		$file = dirname(__FILE__).'/resources/data.db';
		$this->db_file = dirname(__FILE__).'/resources/test.db';
		copy($file,$this->db_file);
		$provider = new TAdodb();
		$provider->importAdodbLibrary();
	}

	function getDsn()
	{
		return 'sqlite://'.urlencode(realpath($this->db_file));
	}

	function testProviderCreation()
	{
		$provider = new TAdodb();
		$connection = $provider->getConnection();
		$this->assertTrue($connection instanceof TAdodbConnection);
		try
		{
			$connection->open();
			$this->fail();
		}
		catch (TDbConnectionException $e)
		{
			$this->pass();
		}
	}



	function testAdodbSqliteConnection()
	{
		$connection = new TAdodbConnection($this->getDsn());
		$this->assertTrue($connection->open());
	
		$statement = "insert into person(per_id, per_first_name,
			per_last_name, per_birth_date, per_weight_kg, per_height_m)
			values(?, ?, ?, ?, ?, ?)";
		$sql = $connection->prepare($statement);
		$connection->execute($sql, 
					array(2,'mini','me','2000-01-01', 50.5, 145.5));

		$statement = "select * from person";
		$results = $connection->execute($statement);
		$this->assertEquals($results->RecordCount(), 2);
	
	}
}

?>