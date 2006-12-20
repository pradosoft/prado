<?php

Prado::using('System.Data.TDbConnection');

if(!defined('SQLMAP_TESTS'))
	define('SQLMAP_TESTS', realpath(dirname(__FILE__)));

if(!class_exists('Account', false))
{
	include(SQLMAP_TESTS.'/domain/A.php');
	include(SQLMAP_TESTS.'/domain/Account.php');
	include(SQLMAP_TESTS.'/domain/AccountBis.php');
	include(SQLMAP_TESTS.'/domain/AccountCollection.php');
	include(SQLMAP_TESTS.'/domain/B.php');
	include(SQLMAP_TESTS.'/domain/Document.php');
	include(SQLMAP_TESTS.'/domain/Book.php');
	include(SQLMAP_TESTS.'/domain/C.php');
	include(SQLMAP_TESTS.'/domain/Category.php');
	include(SQLMAP_TESTS.'/domain/Complex.php');
	include(SQLMAP_TESTS.'/domain/D.php');
	include(SQLMAP_TESTS.'/domain/DocumentCollection.php');
	include(SQLMAP_TESTS.'/domain/E.php');
	include(SQLMAP_TESTS.'/domain/F.php');
	include(SQLMAP_TESTS.'/domain/LineItem.php');
	include(SQLMAP_TESTS.'/domain/LineItemCollection.php');
	include(SQLMAP_TESTS.'/domain/Newspaper.php');
	include(SQLMAP_TESTS.'/domain/Order.php');
	include(SQLMAP_TESTS.'/domain/Other.php');
	include(SQLMAP_TESTS.'/domain/Sample.php');
	include(SQLMAP_TESTS.'/domain/Search.php');
	include(SQLMAP_TESTS.'/domain/User.php');
}

class DefaultScriptRunner
{
	function runScript($connection, $script)
	{
		$sql = file_get_contents($script);
		$lines = explode(';', $sql);
		foreach($lines as $line)
		{
			$line = trim($line);
			if(strlen($line) > 0)
				$connection->createCommand($line)->execute();
		}
	}
}

class CopyFileScriptRunner
{
	protected $baseFile;
	protected $targetFile;

	public function __construct($base, $target)
	{
		$this->baseFile = $base;
		$this->targetFile = $target;
	}

	function runScript($connection, $script)
	{
		copy($this->baseFile, $this->targetFile);
	}
}

class SQLiteBaseTestConfig extends BaseTestConfig
{
	protected $baseFile;
	protected $targetFile;

	public function __construct()
	{
		$this->_sqlmapConfigFile = SQLMAP_TESTS.'/sqlite.xml';
		$this->_scriptDir = SQLMAP_TESTS.'/scripts/sqlite/';

		$this->targetFile = realpath(SQLMAP_TESTS.'/sqlite/tests.db');
		$this->baseFile = realpath(SQLMAP_TESTS.'/sqlite/backup.db');
		$file = realpath($this->targetFile);
		$this->_connection = new TDbConnection("sqlite:{$file}");
	}

	public function getScriptRunner()
	{
		return new CopyFileScriptRunner($this->baseFile, $this->targetFile);
	}
}

class MySQLBaseTestConfig extends BaseTestConfig
{
	public function __construct()
	{
		$this->_sqlmapConfigFile = SQLMAP_TESTS.'/mysql.xml';
		$this->_scriptDir = SQLMAP_TESTS.'/scripts/mysql/';
		$this->_features = array('insert_id');
		$dsn = 'mysql:host=localhost;dbname=sqlmap_test';
		$this->_connection = new TDbConnection($dsn, 'test', 'test111');
	}
}

class MSSQLBaseTestConfig extends BaseTestConfig
{
	public function __construct()
	{
		$this->_sqlmap = SQLMAP_TESTS.'/mssql.xml';
		$this->_connectionString = 'odbc_mssql://sqlmap_tests';
		$this->_scriptDir = SQLMAP_TESTS.'/scripts/mssql/';
		$this->_features = array('insert_id');
	}
}

class BaseTestConfig
{
	protected $_scriptDir;
	protected $_connection;
	protected $_sqlmapConfigFile;

	public function hasFeature($type)
	{
		return false;
	}

	public function getScriptDir()
	{
		return $this->_scriptDir;
	}

	public function getConnection()
	{
		return $this->_connection;
	}

	public function getSqlMapConfigFile()
	{
		return $this->_sqlmapConfigFile;
	}

	public function getScriptRunner()
	{
		return new DefaultScriptRunner();
	}

	public static function createConfigInstance()
	{
		//change this to connection to a different database

		return new MySQLBaseTestConfig();

		//return new SQLiteBaseTestConfig();

		//return new MSSQLBaseTestConfig();
	}
}


?>