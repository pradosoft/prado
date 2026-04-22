<?php

/**
 * PradoUnit class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */
 
 // No Namespace for unit tests, separate from the system
 require_once('PradoUnitDataConnectionTrait.php');
 
 /**
  * PradoUnit class
  *
  * This class has the common features of unit tests.
  *
  * For the Data classes, this accumulates different exceptions that can be grouped.
  *
  * The environment variable `PRADO_UNITTEST_SKIP_DB=1` will bypass the Mysql and Pgsql
  * Database connection errors, database existence errors, and table existence errors.
  *
  * @todo generalize Exception groups.
  * @author Brad Anderson <belisoful@icloud.com>
  * @since 4.3.3
  */

class PradoUnit {

	public static $dbConnectionException = [];
	public static $dbDatabaseException = [];
	public static $dbTableException = [];

	public static function skipDatabaseTests(): bool
	{
		return getenv('PRADO_UNITTEST_SKIP_DB') === '1';
	}
	
	public static function setupMysqlMetaData($database = '')
	{
		$conn = static::setupMysqlConnection($database);
		return new TMysqlMetaData($conn);
	}
	
	public static function setupMysqlConnection($database = '', $isActiveRecord = false)
	{
		if (!empty($database)) {
			$database = ';dbname=' . $database;
		}
		if (!extension_loaded('pdo_mysql')) {
			return 'The pdo_mysql extension is not available.';
		}
		$conn = new TDbConnection('mysql:host=localhost'. $database, 'prado_unitest', 'prado_unitest');
		try {
			$conn->setActive(true);
			if ($isActiveRecord) {
				TActiveRecordManager::getInstance()->setDbConnection($conn);
			}
		} catch(\Exception $e) {
			return static::processException($e, $conn);
		}
		return $conn;
	}
	
	
	public static function setupPgsqlConnection($database = '', $isActiveRecord = false)
	{
		if (!empty($database)) {
			$database = ';dbname=' . $database;
		}
		if (!extension_loaded('pdo_pgsql')) {
			return 'The pdo_pgsql extension is not available.';
		}
		$cred = getenv('SCRUTINIZER') ? 'scrutinizer' : 'prado_unitest';
		$conn = new TDbConnection('pgsql:host=localhost'. $database, $cred, $cred);
		try {
			$conn->setActive(true);
			if ($isActiveRecord) {
				TActiveRecordManager::getInstance()->setDbConnection($conn);
			}
		} catch(\Exception $e) {
			return static::processException($e, $conn);
		}
		return $conn;
	}


	public static function setupFirebirdConnection($database = '', $isActiveRecord = false)
	{
		if (!extension_loaded('pdo_firebird')) {
			return 'The pdo_firebird extension is not available.';
		}
		$dbPath = !empty($database) ? $database : (getenv('FIREBIRD_DB_PATH') ?: '/var/lib/firebird/data/prado_unitest.fdb');
		$conn = new TDbConnection(
			'firebird:dbname=localhost:' . $dbPath . ';charset=UTF8',
			'SYSDBA',
			'masterkey'
		);
		try {
			$conn->setActive(true);
			if ($isActiveRecord) {
				TActiveRecordManager::getInstance()->setDbConnection($conn);
			}
		} catch(\Exception $e) {
			return static::processException($e, $conn);
		}
		return $conn;
	}


	public static function setupSqliteConnection($database = '', $isActiveRecord = false)
	{
		if (!extension_loaded('pdo_sqlite')) {
			return 'The pdo_sqlite extension is not available.';
		}
		$dsn = !empty($database) ? 'sqlite:' . $database : 'sqlite::memory:';
		$conn = new TDbConnection($dsn, '', '');
		try {
			$conn->setActive(true);
			if ($isActiveRecord) {
				TActiveRecordManager::getInstance()->setDbConnection($conn);
			}
		} catch(\Exception $e) {
			return static::processException($e, $conn);
		}
		return $conn;
	}


	public static function setupMssqlConnection($database = '', $isActiveRecord = false)
	{
		if (!extension_loaded('pdo_sqlsrv')) {
			return 'The pdo_sqlsrv extension is not available.';
		}
		$dbParam = !empty($database) ? ';Database=' . $database : '';
		$conn = new TDbConnection(
			'sqlsrv:Server=localhost,1433' . $dbParam . ';TrustServerCertificate=yes',
			'prado_unitest',
			'prado_unitest'
		);
		try {
			$conn->setActive(true);
			if ($isActiveRecord) {
				TActiveRecordManager::getInstance()->setDbConnection($conn);
			}
		} catch(\Exception $e) {
			return static::processException($e, $conn);
		}
		return $conn;
	}


	public static function setupOciConnection($database = '', $isActiveRecord = false)
	{
		if (!extension_loaded('pdo_oci')) {
			return 'The pdo_oci extension is not available.';
		}
		$serviceName = !empty($database) ? $database : (getenv('ORACLE_SERVICE_NAME') ?: 'FREEPDB1');
		$conn = new TDbConnection(
			'oci:dbname=//localhost:1521/' . $serviceName,
			'prado_unitest',
			'prado_unitest'
		);
		try {
			$conn->setActive(true);
			if ($isActiveRecord) {
				TActiveRecordManager::getInstance()->setDbConnection($conn);
			}
		} catch(\Exception $e) {
			return static::processException($e, $conn);
		}
		return $conn;
	}


	public static function setupIbmConnection($database = '', $isActiveRecord = false)
	{
		if (!extension_loaded('pdo_ibm')) {
			return 'The pdo_ibm extension is not available.';
		}
		$user     = getenv('DB2_USER')     ?: 'db2inst1';
		$password = getenv('DB2_PASSWORD') ?: 'Prado_Unitest1';
		$dbname   = !empty($database) ? $database : (getenv('DB2_DATABASE') ?: 'pradount');
		$conn = new TDbConnection(
			'ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE=' . $dbname . ';HOSTNAME=localhost;PORT=50000;PROTOCOL=TCPIP',
			$user,
			$password
		);
		try {
			$conn->setActive(true);
			if ($isActiveRecord) {
				TActiveRecordManager::getInstance()->setDbConnection($conn);
			}
		} catch(\Exception $e) {
			return static::processException($e, $conn);
		}
		return $conn;
	}


	public static function checkForTable($conn, $tableName): mixed
	{
		$sql = 'SELECT * FROM ' . $tableName . ' WHERE 0=1';
		try {
			$conn->createCommand($sql)->query()->close();
		} catch (Exception $e) {
			return static::processException($e, $conn);
		}
		return null;
	}
	
	
	public static function processException($e, &$connection)
	{
		$driver = $connection->getDriverName();
		if (static::isNoConnection($e)) {
			if (isset(static::$dbConnectionException[$driver])) {
				$e = strtr("Duplicated Database Driver '{0}' Unavailable Error", ['{0}' => $driver]);
			} else {
				$msg = strtr("Database Driver '{0}' Unavailable Error:\n-----\n{1}", ['{0}' => $driver, '{1}' => $e->getMessage()]);
				if (static::skipDatabaseTests()) {
					$msg .= "\n(PRADO_UNITTEST_SKIP_DB=1)";
				}
				$e = $msg;
				static::$dbConnectionException[$driver] = true;
			}
		} elseif (static::isNoDatabase($e)) {
			//TDbConnection failed to establish DB connection: SQLSTATE[HY000] [1049] Unknown database 'prado_unitest'
			if (isset(static::$dbDatabaseException[$driver])) {
				$e = strtr("Duplicated Database '{0}' Not Found Error (Connection OK)", ['{0}' => $driver]);
			} else {
				$msg = strtr("Database '{0}' Not Found Error (Connection OK):\n-----\n{1}", ['{0}' => $driver, '{1}' => $e->getMessage()]);
				if (static::skipDatabaseTests()) {
					$msg .= "\n(PRADO_UNITTEST_SKIP_DB=1)";
				}
				$e = $msg;
				static::$dbDatabaseException[$driver] = true;
			}
		} elseif (static::isNoTable($e)) {
			if (isset(static::$dbTableException[$driver])) {
				$e = strtr("Duplicated Table Not Found Error (driver: '{0}')", ['{0}' => $driver]);
			} else {
				$msg = strtr("Table Not Found Error (driver: '{0}'):\n-----\n{1}", ['{0}' => $driver, '{1}' => $e->getMessage()]);
				if (static::skipDatabaseTests()) {
					$msg .= "\n(PRADO_UNITTEST_SKIP_DB=1)";
				}
				$e = $msg;
				static::$dbTableException[$driver] = true;
			}
		}
		return $e;
	}
	
	public static function isNoConnection($e): bool
	{
		return is_int(stripos((string) $e, 'No such file')) || is_int(stripos((string) $e, 'Connection refused')) || is_int(stripos((string) $e, 'failed to establish'));
	}
	
	public static function isNoDatabase($e): bool
	{
		return is_int(stripos((string) $e, 'Unknown database'));
	}

	public static function isNoTable($e): bool
	{
		return is_int(stripos((string) $e, 'Base table or view not found'));
	}
}


