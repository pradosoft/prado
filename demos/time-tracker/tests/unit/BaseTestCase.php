<?php

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