<?php


if(!defined('SQLMAP_DIR'))
	define('SQLMAP_DIR', Prado::getFrameworkPath().'/DataAccess/SQLMap/');

require_once(SQLMAP_DIR.'/TMapper.php');

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

error_reporting(E_ALL);
restore_error_handler();

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
				$connection->execute($line);
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
		$this->_sqlmap = SQLMAP_TESTS.'/sqlite.xml';
		$this->targetFile = realpath(SQLMAP_TESTS.'/sqlite/tests.db');
		$this->baseFile = realpath(SQLMAP_TESTS.'/sqlite/backup.db');
		$file = urlencode($this->targetFile);
		$this->_connectionString = "sqlite://{$file}/";
		$this->_scriptDir = SQLMAP_TESTS.'/scripts/sqlite/';
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
		$this->_sqlmap = SQLMAP_TESTS.'/mysql.xml';
		$this->_connectionString = 'mysql://root:weizhuo01@localhost/IBatisNet';
		$this->_scriptDir = SQLMAP_TESTS.'/scripts/mysql/';
		$this->_features = array('insert_id');
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
	protected $_connectionString;
	protected $_sqlmap;
	protected $_features = array();

	public function getScriptDir() { return $this->_scriptDir; }
	public function getConnectionString() { return $this->_connectionString; }
	public function getSqlMapConfigFile(){ return $this->_sqlmap; }
	
	public function hasFeature($feature)
	{
		return in_array($feature, $this->_features);
	}

	public function getScriptRunner()
	{
		return new DefaultScriptRunner();	
	}


	public static function createConfigInstance()
	{
		//change this to connection to a different database

		//return new MySQLBaseTestConfig();

		return new SQLiteBaseTestConfig();

		//return new MSSQLBaseTestConfig();
	}

}


?>