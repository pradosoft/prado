<?php


Prado::using('Application.App_Code.Dao.*');

class BaseTestCase extends UnitTestCase
{
	protected $sqlmap;
	
	function setup()
	{
		$app = Prado::getApplication();
		$this->sqlmap = $app->getModule('daos')->getConnection();
	}
	
	
	function flushDatabase()
	{
		$conn = $this->sqlmap->openConnection();
		switch(strtolower($conn->getProvider()->getDriver()))
		{
			case 'mysql':
			return $this->flushMySQLDatabase();
			case 'sqlite':
			return $this->flushSQLiteDatabase(); 
		}		
	}
	
	function flushSQLiteDatabase()
	{
		$conn = $this->sqlmap->openConnection();
		$file = $conn->getProvider()->getHost();
		$backup = $file.'.bak';
		copy($backup, $file);
	}
	
	function flushMySQLDatabase()
	{
		$conn = $this->sqlmap->openConnection();
		$file = Prado::getPathOfNamespace('Application.App_Data.mysql-reset','.sql');
		if(is_file($file))
			$this->runScript($conn, $file);
		else
			throw new Exception('unable to find script file '.$file);
	}
	
	protected function runScript($connection, $script)
	{
		$sql = file_get_contents($script);
		$lines = explode(';', $sql);
		foreach($lines as $line)
		{
			$line = trim($line);
			if(strlen($line) > 0)
				$connection->execute($line);
		}
	}
}
?>