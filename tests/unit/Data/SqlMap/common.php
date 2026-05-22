<?php

use Prado\Data\TDbConnection;

if (!defined('SQLMAP_TESTS')) {
	define('SQLMAP_TESTS', realpath(__DIR__));
}

if (!class_exists('Account', false)) {
	include(SQLMAP_TESTS . '/domain/A.php');
	include(SQLMAP_TESTS . '/domain/Account.php');
	include(SQLMAP_TESTS . '/domain/Enumeration.php');
	include(SQLMAP_TESTS . '/domain/AccountBis.php');
	include(SQLMAP_TESTS . '/domain/AccountCollection.php');
	include(SQLMAP_TESTS . '/domain/B.php');
	include(SQLMAP_TESTS . '/domain/Document.php');
	include(SQLMAP_TESTS . '/domain/Book.php');
	include(SQLMAP_TESTS . '/domain/C.php');
	include(SQLMAP_TESTS . '/domain/Category.php');
	include(SQLMAP_TESTS . '/domain/Complex.php');
	include(SQLMAP_TESTS . '/domain/D.php');
	include(SQLMAP_TESTS . '/domain/DocumentCollection.php');
	include(SQLMAP_TESTS . '/domain/E.php');
	include(SQLMAP_TESTS . '/domain/F.php');
	include(SQLMAP_TESTS . '/domain/LineItem.php');
	include(SQLMAP_TESTS . '/domain/LineItemCollection.php');
	include(SQLMAP_TESTS . '/domain/Newspaper.php');
	include(SQLMAP_TESTS . '/domain/Order.php');
	include(SQLMAP_TESTS . '/domain/Other.php');
	include(SQLMAP_TESTS . '/domain/Sample.php');
	include(SQLMAP_TESTS . '/domain/Search.php');
	include(SQLMAP_TESTS . '/domain/User.php');
}

class DefaultScriptRunner
{
	public function runScript($connection, $script)
	{
		$sql = file_get_contents($script);
		$lines = explode(';', $sql);
		foreach ($lines as $line) {
			$line = trim($line);
			if (strlen($line) > 0) {
				$connection->createCommand($line)->execute();
			}
		}
	}
}

/**
 * Script runner for IBM DB2. Silently ignores SQLSTATE 42704 ("undefined name")
 * errors so that DROP TABLE/SEQUENCE statements do not abort on a fresh database
 * where the objects do not yet exist.
 */
class IbmScriptRunner extends DefaultScriptRunner
{
	public function runScript($connection, $script)
	{
		$sql = file_get_contents($script);
		$lines = explode(';', $sql);
		foreach ($lines as $line) {
			$line = trim($line);
			if (strlen($line) === 0) {
				continue;
			}
			try {
				$connection->createCommand($line)->execute();
			} catch (\Exception $e) {
				// SQLSTATE 42704: undefined name — object does not exist, safe to ignore for DROPs.
				if (stripos($e->getMessage(), '42704') === false) {
					throw $e;
				}
			}
		}
	}
}

/**
 * Script runner for Firebird. Silently ignores "object unknown" errors (SQLCODE -204)
 * so that DROP TABLE/SEQUENCE statements do not abort on a fresh database where the
 * objects do not yet exist.
 */
class FirebirdScriptRunner extends DefaultScriptRunner
{
	public function runScript($connection, $script)
	{
		$sql = file_get_contents($script);
		$lines = explode(';', $sql);
		foreach ($lines as $line) {
			$line = trim($line);
			if (strlen($line) === 0) {
				continue;
			}
			try {
				$connection->createCommand($line)->execute();
			} catch (\Exception $e) {
				// Firebird SQLCODE -204: object unknown (table/sequence does not exist).
				$msg = $e->getMessage();
				$isObjectUnknown = strpos($msg, '-204') !== false
					|| stripos($msg, 'does not exist') !== false
					|| stripos($msg, 'Table unknown') !== false
					|| stripos($msg, 'Sequence unknown') !== false
					|| stripos($msg, 'object unknown') !== false;
				if (!$isObjectUnknown) {
					throw $e;
				}
			}
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

	public function runScript($connection, $script)
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
		$this->_sqlmapConfigFile = SQLMAP_TESTS . '/sqlite.xml';
		$this->_scriptDir = SQLMAP_TESTS . '/scripts/sqlite/';
		$this->_features = ['insert_id'];

		$this->targetFile = realpath(SQLMAP_TESTS . '/sqlite/tests.db');
		$this->baseFile = realpath(SQLMAP_TESTS . '/sqlite/backup.db');
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
		$this->_sqlmapConfigFile = SQLMAP_TESTS . '/mysql.xml';
		$this->_scriptDir = SQLMAP_TESTS . '/scripts/mysql/';
		$this->_features = ['insert_id'];
		$dsn = 'mysql:host=localhost;dbname=prado_unitest;port=3306';
		$this->_connection = new TDbConnection($dsn, 'prado_unitest', 'prado_unitest');
	}
}

class MSSQLBaseTestConfig extends BaseTestConfig
{
	public function __construct()
	{
		$this->_sqlmap = SQLMAP_TESTS . '/mssql.xml';
		$this->_connectionString = 'odbc_mssql://sqlmap_tests';
		$this->_scriptDir = SQLMAP_TESTS . '/scripts/mssql/';
		$this->_features = ['insert_id'];
	}
}

class PgsqlBaseTestConfig extends BaseTestConfig
{
	public function __construct()
	{
		$this->_sqlmapConfigFile = SQLMAP_TESTS . '/pgsql.xml';
		$this->_scriptDir = SQLMAP_TESTS . '/scripts/pgsql/';
		$this->_features = ['insert_id'];
		$dsn = 'pgsql:host=localhost;dbname=prado_unitest;port=5432';
		$this->_connection = new TDbConnection($dsn, 'prado_unitest', 'prado_unitest');
	}
}

class SqlSrvBaseTestConfig extends BaseTestConfig
{
	public function __construct()
	{
		$this->_sqlmapConfigFile = SQLMAP_TESTS . '/sqlsrv.xml';
		$this->_scriptDir = SQLMAP_TESTS . '/scripts/sqlsrv/';
		$this->_features = ['insert_id'];
		$dsn = 'sqlsrv:Server=localhost,1433;Database=prado_unitest;TrustServerCertificate=yes';
		$this->_connection = new TDbConnection($dsn, 'prado_unitest', 'prado_unitest');
	}
}

/**
 * TDbConnection subclass for Oracle that sets ISO date/timestamp NLS formats
 * immediately after the connection opens.  Without this, pdo_oci uses the
 * session NLS_DATE_FORMAT / NLS_TIMESTAMP_FORMAT which defaults to
 * 'DD-MON-RR' style, causing ORA-01843 when PHP date strings like
 * '2005-05-20' are bound to DATE/TIMESTAMP columns.
 */
class OracleNLSConnection extends TDbConnection
{
	public function setActive($value)
	{
		$wasActive = $this->getActive();
		parent::setActive($value);
		if (!$wasActive && $this->getActive()) {
			$this->createCommand("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD'")->execute();
			$this->createCommand("ALTER SESSION SET NLS_TIMESTAMP_FORMAT = 'YYYY-MM-DD HH24:MI:SS'")->execute();
		}
	}
}

class OracleBaseTestConfig extends BaseTestConfig
{
	public function __construct()
	{
		$this->_sqlmapConfigFile = SQLMAP_TESTS . '/oracle.xml';
		$this->_scriptDir = SQLMAP_TESTS . '/scripts/oracle/';
		$dsn = 'oci:dbname=//localhost:1521/FREEPDB1';
		$this->_connection = new OracleNLSConnection($dsn, 'prado_unitest', 'prado_unitest');
	}
}

class IbmBaseTestConfig extends BaseTestConfig
{
	public function __construct()
	{
		$this->_sqlmapConfigFile = SQLMAP_TESTS . '/ibm.xml';
		$this->_scriptDir = SQLMAP_TESTS . '/scripts/ibm/';
		$user     = getenv('DB2_USER')     ?: 'db2inst1';
		$password = getenv('DB2_PASSWORD') ?: 'Prado_Unitest1';
		$dbname   = getenv('DB2_DATABASE') ?: 'pradount';
		$dsn = 'ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE=' . $dbname . ';HOSTNAME=localhost;PORT=50000;PROTOCOL=TCPIP';
		$this->_connection = new TDbConnection($dsn, $user, $password);
	}

	public function getScriptRunner()
	{
		return new IbmScriptRunner();
	}
}

class FirebirdBaseTestConfig extends BaseTestConfig
{
	public function __construct()
	{
		$this->_sqlmapConfigFile = SQLMAP_TESTS . '/firebird.xml';
		$this->_scriptDir = SQLMAP_TESTS . '/scripts/firebird/';
		$dbPath = getenv('FIREBIRD_DB_PATH') ?: '/var/lib/firebird/data/prado_unitest.fdb';
		$dsn = 'firebird:dbname=localhost:' . $dbPath . ';charset=UTF8';
		$this->_connection = new TDbConnection($dsn, 'SYSDBA', 'masterkey');
	}

	public function getScriptRunner()
	{
		return new FirebirdScriptRunner();
	}
}

class BaseTestConfig
{
	protected $_scriptDir;
	protected $_connection;
	protected $_sqlmapConfigFile;
	protected $_features = [];

	public function hasFeature($type)
	{
		return in_array($type, $this->_features, true);
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

		//return new MySQLBaseTestConfig();

		return new SQLiteBaseTestConfig();

		//return new MSSQLBaseTestConfig();
	}
}
