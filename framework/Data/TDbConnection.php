<?php

/**
 * TDbConnection class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data;

use PDO;
use PDOException;
use Prado\Data\Common\TDbMetaData;
use Prado\Exceptions\TDbException;
use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TDbConnection class
 *
 * TDbConnection represents a connection to a database.
 *
 * TDbConnection works together with {@see \Prado\Data\TDbCommand},
 * {@see \Prado\Data\TDbDataReader} and {@see \Prado\Data\TDbTransaction} to
 * provide data access to various DBMS in a common set of APIs. They are a
 * thin wrapper of the {@see http://www.php.net/manual/en/ref.pdo.php PDO}
 * PHP extension.
 *
 * To establish a connection, set {@see setActive Active} to true after
 * specifying {@see setConnectionString ConnectionString},
 * {@see setUsername Username} and {@see setPassword Password}.
 *
 * Since 4.3.3, the connection charset could be set (for PDO databases, except
 * IBM) using the {@see setCharset Charset} property. The value of this property
 * was database **independent**.
 *
 * Firebird (firebird), MSSQL (mssql, sqlsrv, dblib), IBM DB2 (ibm), and
 * Oracle (oci) do not support runtime charset switching via SQL; configure
 * their charset at the DSN or with {@see setCharset Charset} property before
 * activating the connection.
 *
 * Most formats of the Charset are supported and translated to the proper
 * database specific charset. The database specific format gat be retrieved
 * on active connections with the method {@see getDatabaseCharset()}.
 * Only mysql, pgsql, sqlite and firebird support discovery of the database
 * charset.
 *
 * Pgsql, sqlite, ibm databases do not support DSN charset.
 * Pgsql must set the charset after the connection is established.
 * sqlite only supports UTF-8 and UTF-16, set before tables are created.
 * When a table is present in sqlite, {@see setCharset()} becomes no-op.
 * Ibm Db2 has no charset support.
 *
 * The following example shows how to create a TDbConnection instance and
 * establish the actual connection:
 * ```php
 * $connection = new TDbConnection($dsn, $username, $password [, $charset]);
 * $connection->Active = true;
 * ```
 *
 * After the DB connection is established, one can execute an SQL statement
 * like the following:
 * ```php
 * $command=$connection->createCommand($sqlStatement);
 * $command->execute();   // a non-query SQL statement execution
 * // or execute an SQL query and fetch the result set
 * $reader=$command->query();
 *
 * // each $row is an array representing a row of data
 * foreach($reader as $row) ...
 * ```
 *
 * One can do prepared SQL execution and bind parameters to the prepared SQL:
 * ```php
 * $command=$connection->createCommand($sqlStatement);
 * $command->bindParameter($name1,$value1);
 * $command->bindParameter($name2,$value2);
 * $command->execute();
 * ```
 *
 * To use transaction, do like the following:
 * ```php
 * $transaction=$connection->beginTransaction();
 * try
 * {
 *    $connection->createCommand($sql1)->execute();
 *    $connection->createCommand($sql2)->execute();
 *    //.... other SQL executions
 *    $transaction->commit();
 * }
 * catch(Exception $e)
 * {
 *    $transaction->rollBack();
 * }
 * ```
 *
 * TDbConnection provides a set of methods to support setting and querying
 * of certain DBMS attributes, such as {@see getNullConversion NullConversion}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Brad Anderson <belisoful@icloud.com> Charset.
 * @since 3.0
 */
class TDbConnection extends \Prado\TComponent implements IDataConnection
{
	public const DRIVER_MYSQL = 'mysql';		// MySQL / MariaDB
	//public const DRIVER_MYSQL = 'mysqli';		// separate extension
	public const DRIVER_PGSQL = 'pgsql';		// PostgreSQL (charset after connection is started)
	public const DRIVER_SQLITE = 'sqlite';		// SQLite 3 (UTF-8, UTF-16, set charset without tables)
	public const DRIVER_SQLITE2 = 'sqlite2';	// SQLite 2
	//public const DRIVER_MSSQL = 'mssql'; 		// separate extension
	public const DRIVER_SQLSRV = 'sqlsrv';		// Microsoft SQL Server
	public const DRIVER_DBLIB = 'dblib';		// SQL Server / Sybase (via FreeTDS)
	public const DRIVER_OCI = 'oci';			// Oracle
	public const DRIVER_IBM = 'ibm';			// IBM DB2 (no charset)
	public const DRIVER_FIREBIRD = 'firebird';	// Firebird
	//public const DRIVER_INTERBASE = 'interbase';

	//
	public const DRIVER_CUBRID = 'cubrid';		// CUBRID database
	public const DRIVER_ODBC = 'odbc';			// Generic ODBC (various databases)

	/**
	 *
	 * @since 3.1.7
	 */
	public const DEFAULT_TRANSACTION_CLASS = \Prado\Data\TDbTransaction::class;

	private $_dsn = '';
	private $_username = '';
	private $_password = '';
	private $_charset = '';
	private $_attributes = [];
	private $_active = false;
	private $_pdo;
	private $_transaction;

	/**
	 * @var TDbMetaData
	 */
	private $_dbMeta;

	/**
	 * @var string
	 * @since 3.1.7
	 */
	private $_transactionClass = self::DEFAULT_TRANSACTION_CLASS;

	/**
	 * Constructor.
	 * Note, the DB connection is not established when this connection
	 * instance is created. Set {@see setActive Active} property to true
	 * to establish the connection.
	 * Since 3.1.2, you can set the charset for MySql connection
	 *
	 * @param string $dsn The Data Source Name, or DSN, contains the information required to connect to the database.
	 * @param string $username The user name for the DSN string.
	 * @param string $password The password for the DSN string.
	 * @param string $charset Charset used for DB Connection; except IBM DB2 (ibm).
	 *   MSSQL (mssql, sqlsrv, dblib), and Oracle (oci) require configuration
	 * 	 of the charset before opening.
	 *   If not set, will use the default charset of your database server.
	 * @see http://www.php.net/manual/en/function.PDO-construct.php
	 */
	public function __construct($dsn = '', $username = '', #[\SensitiveParameter] $password = '', $charset = '')
	{
		$this->_dsn = $dsn;
		$this->_username = $username;
		$this->_password = $password;
		$this->_charset = $charset;
		parent::__construct();
	}

	/**
	 * Close the connection when serializing.
	 */
	public function __sleep()
	{
		/*
		 * $this->close();
		 * DO NOT CLOSE the current connection as serializing doesn't necessarily mean
		 * we don't this connection anymore in the current session
		 */
		return array_diff(parent::__sleep(), ["\0Prado\Data\TDbConnection\0_pdo", "\0Prado\Data\TDbConnection\0_active"]);
	}

	/**
	 * @return array list of available PDO drivers
	 * @see http://www.php.net/manual/en/function.PDO-getAvailableDrivers.php
	 */
	public static function getAvailableDrivers()
	{
		return PDO::getAvailableDrivers();
	}

	/**
	 * @return bool whether the DB connection is established
	 */
	public function getActive()
	{
		return $this->_active;
	}

	/**
	 * Open or close the DB connection.
	 * @param bool $value whether to open or close DB connection
	 * @throws TDbException if connection fails
	 */
	public function setActive($value)
	{
		$value = TPropertyValue::ensureBoolean($value);
		if ($value !== $this->_active) {
			if ($value) {
				$this->open();
			} else {
				$this->close();
			}
		}
	}

	/**
	 * Opens DB connection if it is currently not
	 * @throws TDbException if connection fails
	 */
	protected function open()
	{
		if ($this->_pdo === null) {
			try {
				$this->_pdo = new PDO(
					$this->applyCharsetToDsn($this->getConnectionString()),
					$this->getUsername(),
					$this->getPassword(),
					$this->_attributes
				);
				// This attribute is only useful for PDO::MySql driver.
				// Ignore the warning if a driver doesn't understand this.
				@$this->_pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
				// This attribute is only useful for PDO::MySql driver since PHP 8.1
				// This ensures integers are returned as strings (needed eg. for ZEROFILL columns)
				@$this->_pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, true);
				$this->_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$this->_active = true;
				if ($this->getCanCharsetChange()) {
					$this->setConnectionCharset();
				}
			} catch (PDOException $e) {
				throw new TDbException('dbconnection_open_failed', $e->getMessage());
			}
		}
	}

	/**
	 * Closes the currently active DB connection.
	 * It does nothing if the connection is already closed.
	 */
	protected function close()
	{
		$this->_pdo = null;
		$this->_active = false;
	}

	/*
	 * Apply the connection charset via a driver-appropriate SQL command.
	 *
	 * MySQL uses  SET NAMES <charset>.
	 * PostgreSQL uses  SET client_encoding TO <charset>.
	 * SQLite uses  PRAGMA encoding = <charset>  which can only take effect
	 * before any tables are created; errors are silently ignored so the method
	 * is safe to call on any SQLite connection regardless of state.
	 * Firebird, Oracle (oci), MSSQL (mssql, sqlsrv, dblib), and IBM DB2 (ibm) do not
	 * support runtime charset switching via SQL; their charset is injected into
	 * the DSN before the connection opens by {@see applyCharsetToDsn}.
	 * Changing Charset after the connection is already active has no effect for
	 * those drivers.
	 *
	 * All charset values are resolved through {@see resolveCharsetForDriver}
	 * before being sent to the database, so universal names like 'UTF-8' or
	 * 'ISO-8859-1' work across all supported drivers without any
	 * driver-specific knowledge from the caller.
	 * @since 3.1.2
	 */
	protected function setConnectionCharset()
	{
		if ($this->_charset === '' || $this->_active === false) {
			return;
		}
		$driver = $this->_pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
		$charset = $this->resolveCharsetForDriver($this->_charset, $driver);
		switch ($driver) {
			case self::DRIVER_MYSQL:
				$stmt = $this->_pdo->prepare('SET NAMES ?');
				break;
			case self::DRIVER_PGSQL:
				$stmt = $this->_pdo->prepare('SET client_encoding TO ?');
				break;
			case self::DRIVER_SQLITE:
				// PRAGMA encoding sets the internal storage encoding, but only takes
				// effect before any tables are created.  PRAGMA does not support
				// parameterised values, so PDO::quote is used to safely embed the
				// resolved charset name.
				try {
					$this->_pdo->exec('PRAGMA encoding = ' . $this->_pdo->quote($charset));
				} catch (\Exception $e) {
					// Silently ignored.
				}
				return;
			case self::DRIVER_FIREBIRD:
			case self::DRIVER_SQLSRV:
			case self::DRIVER_DBLIB:
			case self::DRIVER_IBM:
			case self::DRIVER_OCI:
				// These drivers do not support runtime charset switching via SQL.
				return;
			default:
				throw new TDbException('dbconnection_unsupported_driver_charset', $driver);
		}
		$stmt->execute([$charset]);
	}

	/**
	 * Resolves a charset name to its driver-specific equivalent, allowing callers to
	 * use universal IANA-style names like 'UTF-8' or 'ISO-8859-1' regardless of the
	 * underlying database driver.
	 *
	 * The lookup key is derived by lowercasing $charset and stripping all hyphens,
	 * underscores, and spaces, so 'UTF-8', 'utf8', 'UTF_8', and 'Utf 8' all resolve
	 * to the same entry.  If no mapping is found the original $charset string is
	 * returned unchanged, preserving backward compatibility with driver-specific names.
	 *
	 * The same table is used by both {@see setConnectionCharset} (SQL-level charset
	 * commands) and {@see applyCharsetToDsn} (DSN parameter injection), so driver
	 * columns for oci, sqlsrv, mssql, and dblib resolve to their DSN charset values.
	 *
	 * Override this method to add or change mappings for custom database configurations.
	 *
	 * @param string $charset the charset name as supplied by the caller (e.g. 'UTF-8')
	 * @param string $driver  PDO driver name (e.g. 'mysql', 'pgsql', 'firebird', self::DRIVER_OCI)
	 * @return string the charset name appropriate for $driver
	 * @since 4.3.3
	 */
	protected function resolveCharsetForDriver(string $charset, string $driver): string
	{
		static $aliases = [
			// canonical_key => [driver => resolved_name, ...]
			// Key = charset lowercased with hyphens, underscores, and spaces removed.
			// Drivers mysql/pgsql/firebird: SQL-level charset names.
			// Drivers sqlite: PRAGMA encoding values (only UTF-8 and UTF-16 variants
			//   are valid; unsupported values are passed through and silently ignored).
			// Drivers oci/sqlsrv/mssql/dblib: DSN-parameter charset names.
			'utf8' => [
				self::DRIVER_MYSQL => 'utf8mb4',
				self::DRIVER_SQLITE => 'UTF-8',
				self::DRIVER_PGSQL => 'UTF8',
				self::DRIVER_FIREBIRD => 'UTF8',
				self::DRIVER_OCI => 'AL32UTF8',
				self::DRIVER_SQLSRV => 'UTF-8',
				self::DRIVER_DBLIB => 'UTF-8',
			],
			'utf8mb4' => [
				self::DRIVER_MYSQL => 'utf8mb4',
				self::DRIVER_SQLITE => 'UTF-8',
				self::DRIVER_PGSQL => 'UTF8',
				self::DRIVER_FIREBIRD => 'UTF8',
				self::DRIVER_OCI => 'AL32UTF8',
				self::DRIVER_SQLSRV => 'UTF-8',
				self::DRIVER_DBLIB => 'UTF-8',
			],
			'utf16' => [
				self::DRIVER_MYSQL => 'utf16',
				self::DRIVER_SQLITE => 'UTF-16',
				self::DRIVER_FIREBIRD => 'UTF16BE',
				self::DRIVER_OCI => 'AL16UTF16',
			],
			'latin1' => [
				self::DRIVER_MYSQL => 'latin1',
				// sqlite: no PRAGMA encoding support for latin1 — pass-through and
				// silently ignored; SQLite stores all text internally as UTF-8/UTF-16.
				self::DRIVER_PGSQL => 'LATIN1',
				self::DRIVER_FIREBIRD => 'ISO8859_1',
				self::DRIVER_OCI => 'WE8ISO8859P1',
				self::DRIVER_DBLIB => 'ISO-8859-1',
			],
			'iso88591' => 'latin1',
			'latin2' => [
				self::DRIVER_MYSQL => 'latin2',
				self::DRIVER_PGSQL => 'LATIN2',
				self::DRIVER_FIREBIRD => 'ISO8859_2',
				self::DRIVER_OCI => 'EE8ISO8859P2',
				self::DRIVER_DBLIB => 'ISO-8859-2',
			],
			'iso88592' => 'latin2',
			'ascii' => [
				self::DRIVER_MYSQL => 'ascii',
				self::DRIVER_PGSQL => 'SQL_ASCII',
				self::DRIVER_FIREBIRD => 'ASCII',
				self::DRIVER_OCI => 'US7ASCII',
				self::DRIVER_DBLIB => 'ASCII',
			],
			'win1250' => [
				self::DRIVER_MYSQL => 'cp1250',
				self::DRIVER_PGSQL => 'WIN1250',
				self::DRIVER_FIREBIRD => 'WIN1250',
				self::DRIVER_OCI => 'EE8MSWIN1250',
				self::DRIVER_DBLIB => 'CP1250',
			],
			'windows1250' => 'win1250',
			'cp1250' => 'win1250',
			'win1251' => [
				self::DRIVER_MYSQL => 'cp1251',
				self::DRIVER_PGSQL => 'WIN1251',
				self::DRIVER_FIREBIRD => 'WIN1251',
				self::DRIVER_OCI => 'CL8MSWIN1251',
				self::DRIVER_DBLIB => 'CP1251',
			],
			'windows1251' => 'win1251',
			'cp1251' => 'win1251',
			'win1252' => [
				self::DRIVER_MYSQL => 'cp1252',
				self::DRIVER_PGSQL => 'WIN1252',
				self::DRIVER_FIREBIRD => 'WIN1252',
				self::DRIVER_OCI => 'WE8MSWIN1252',
				self::DRIVER_DBLIB => 'CP1252',
			],
			'windows1252' => 'win1252',
			'cp1252' => 'win1252',
			'koi8r' => [
				self::DRIVER_MYSQL => 'koi8r',
				self::DRIVER_PGSQL => 'KOI8R',
				self::DRIVER_FIREBIRD => 'KOI8R',
				self::DRIVER_OCI => 'CL8KOI8R',
				self::DRIVER_DBLIB => 'KOI8-R',
			],
			'koi8u' => [
				self::DRIVER_MYSQL => 'koi8u',
				self::DRIVER_PGSQL => 'KOI8U',
				self::DRIVER_FIREBIRD => 'KOI8U',
				self::DRIVER_OCI => 'CL8KOI8U',
				self::DRIVER_DBLIB => 'KOI8-U',
			],
		];

		$key = strtolower(preg_replace('/[-_ ]+/', '', $charset));

		if (isset($aliases[$key]) && is_string($aliases[$key])) {
			$key = $aliases[$key];
		}

		return $aliases[$key][$driver] ?? $charset;
	}

	/**
	 * Returns the DSN string with a charset parameter appended for the current
	 * driver, if {@see $_charset} is set and the DSN does not already contain
	 * a charset directive.
	 *
	 * This method is called by {@see open} before the PDO instance is created so
	 * that drivers which only support charset configuration at connection time
	 * (Oracle, MSSQL family) receive the correct encoding without requiring the
	 * caller to embed a driver-specific parameter in the DSN manually.
	 *
	 * The internal {@see $_dsn} field is never mutated; the method returns a
	 * (potentially modified) copy.  DSN charset takes priority: if the caller
	 * already included a charset directive in the DSN it is left unchanged.
	 *
	 * Drivers handled (DSN parameter name):
	 *   mysql, firebird → charset=
	 *   oci             → charset=
	 *   sqlsrv          → CharacterSet=
	 *   mssql, dblib    → charset=
	 *
	 * PostgreSQL has no standard DSN charset parameter (charset is applied via
	 * {@see setConnectionCharset} after the connection opens).  SQLite is always
	 * UTF-8.  IBM DB2 (ibm) has no reliable DSN charset parameter.  These drivers
	 * are returned unchanged.
	 *
	 * @param string $dsn the raw DSN string as set by the caller
	 * @return string the DSN, with a charset parameter appended if required
	 * @since 4.3.3
	 */
	protected function applyCharsetToDsn(string $dsn): string
	{
		if ($this->_charset === '' || $dsn === '') {
			return $dsn;
		}

		$driver = $this->getDriverName();

		// Maps each supported driver to [dsn_param_name, regex_detecting_existing_param].
		// Drivers absent from this table (pgsql, sqlite, ibm) are returned unchanged.
		$dsnCharsetParams = [
			self::DRIVER_MYSQL => ['charset',      '/[;?]charset\s*=/i'],
			self::DRIVER_FIREBIRD => ['charset',      '/[;?]charset\s*=/i'],
			self::DRIVER_OCI => ['charset',      '/[;?]charset\s*=/i'],
			self::DRIVER_SQLSRV => ['CharacterSet', '/[;?]CharacterSet\s*=/i'],
			self::DRIVER_DBLIB => ['charset',      '/[;?]charset\s*=/i'],
		];

		if (!isset($dsnCharsetParams[$driver])) {
			// Driver does not use a DSN charset parameter (pgsql, sqlite, ibm, …).
			return $dsn;
		}

		[$paramName, $existingPattern] = $dsnCharsetParams[$driver];

		// If the caller already embedded a charset directive, honour it (DSN wins).
		if (preg_match($existingPattern, $dsn)) {
			return $dsn;
		}

		$resolved = $this->resolveCharsetForDriver($this->_charset, $driver);

		return $dsn . ';' . $paramName . '=' . $resolved;
	}

	/**
	 * @return string The Data Source Name, or DSN, contains the information required to connect to the database.
	 */
	public function getConnectionString()
	{
		return $this->_dsn;
	}

	/**
	 * @param string $value The Data Source Name, or DSN, contains the information required to connect to the database.
	 * @see http://www.php.net/manual/en/function.PDO-construct.php
	 */
	public function setConnectionString($value)
	{
		$this->_dsn = $value;
	}

	/**
	 * @return string the username for establishing DB connection. Defaults to empty string.
	 */
	public function getUsername()
	{
		return $this->_username;
	}

	/**
	 * @param string $value the username for establishing DB connection
	 */
	public function setUsername($value)
	{
		$this->_username = $value;
	}

	/**
	 * @return string the password for establishing DB connection. Defaults to empty string.
	 */
	public function getPassword()
	{
		return $this->_password;
	}

	/**
	 * @param string $value the password for establishing DB connection
	 */
	public function setPassword(#[\SensitiveParameter] $value)
	{
		$this->_password = $value;
	}

	/**
	 * @return string the charset used for database connection. Defaults to empty string.
	 * @see getDatabaseCharset to read the charset actually reported by the active connection.
	 */
	public function getCharset()
	{
		return $this->_charset;
	}

	/**
	 * @param string $value the charset used for database connection
	 */
	public function setCharset($value)
	{
		$driver = $this->getDriverName();
		if (!$this->getCanCharsetChange()) {
			throw new TDbException('dbconnection_charset_unchangeable', $driver);
		}
		$this->_charset = $value;
		$this->setConnectionCharset();
	}

	/**
	 * If the connection is not active or in the Databases that can change their
	 * charset within the connection.
	 * @return bool if the charset can change
	 * @since 4.3.3
	 */
	public function getCanCharsetChange(): bool
	{
		$driver = $this->getDriverName();
		return !$this->getActive() || in_array($driver, [self::DRIVER_MYSQL, self::DRIVER_PGSQL, self::DRIVER_SQLITE]);
	}

	/**
	 * Returns the charset currently reported by the active database connection.
	 *
	 * Unlike {@see getCharset}, which returns the value stored in the Charset
	 * property, this method queries the database directly so the result always
	 * reflects the real connection state — useful for verifying that a charset
	 * was applied correctly or for discovering the server default when no Charset
	 * was configured.
	 *
	 * Driver query used:
	 *   mysql    — SELECT @@character_set_client
	 *   pgsql    — SELECT pg_client_encoding()
	 *   sqlite   — PRAGMA encoding
	 *   firebird — MON$ATTACHMENTS ⋈ RDB$CHARACTER_SETS; falls back to the
	 *              resolved Charset property value if the MONITOR privilege is
	 *              absent
	 *   oci, mssql, sqlsrv, dblib, ibm — charset is configured at the DSN
	 *              level and cannot be queried cheaply; returns the charset
	 *              name as resolved for the driver from the Charset property
	 *
	 * When the connection is not active, returns the raw Charset property value
	 * (same as {@see getCharset}).  On query failure, falls back to the stored
	 * Charset property value.
	 *
	 * @return string the charset in use, or empty string if none was configured
	 * @since 4.3.3
	 */
	public function getDatabaseCharset()
	{
		if (!$this->_active || $this->_pdo === null) {
			return $this->_charset;
		}
		$driver = $this->getDriverName();
		try {
			switch ($driver) {
				case self::DRIVER_MYSQL:
					return (string) $this->createCommand('SELECT @@character_set_connection')->queryScalar();
				case self::DRIVER_PGSQL:
					return (string) $this->createCommand('SELECT pg_client_encoding()')->queryScalar();
				case self::DRIVER_SQLITE:
					return (string) $this->createCommand('PRAGMA encoding')->queryScalar();
				case self::DRIVER_FIREBIRD:
					$result = $this->createCommand(
						'SELECT TRIM(c.RDB$CHARACTER_SET_NAME)' .
						'  FROM MON$ATTACHMENTS a' .
						'  JOIN RDB$CHARACTER_SETS c' .
						'    ON c.RDB$CHARACTER_SET_ID = a.MON$CHARACTER_SET_ID' .
						' WHERE a.MON$ATTACHMENT_ID = CURRENT_CONNECTION'
					)->queryScalar();
					return ($result !== false && $result !== null)
						? (string) $result
						: $this->resolveCharsetForDriver($this->_charset, $driver);
				default:
					// Drivers that configure charset via DSN (oci, mssql, sqlsrv, dblib, ibm):
					// return the charset name as it was resolved for this driver so the caller
					// can confirm what was injected into the connection string.
					return $this->resolveCharsetForDriver($this->_charset, $driver);
			}
		} catch (\Throwable $e) {
			return $this->_charset;
		}
	}

	/**
	 * @return PDO the PDO instance, null if the connection is not established yet
	 */
	public function getPdoInstance()
	{
		return $this->_pdo;
	}

	/**
	 * Creates a command for execution.
	 * @param string $sql SQL statement associated with the new command.
	 * @throws TDbException if the connection is not active
	 * @return TDbCommand the DB command
	 */
	public function createCommand($sql)
	{
		if ($this->getActive()) {
			return new TDbCommand($this, $sql);
		} else {
			throw new TDbException('dbconnection_connection_inactive');
		}
	}

	/**
	 * @return null|TDbTransaction the currently active transaction. Null if no active transaction.
	 */
	public function getCurrentTransaction()
	{
		if ($this->_transaction !== null) {
			if ($this->_transaction->getActive()) {
				return $this->_transaction;
			}
		}
		return null;
	}

	/**
	 * Starts a transaction.
	 *
	 * For Firebird connections, `pdo_firebird` always keeps an implicit transaction
	 * open in autocommit mode. Calling `beginTransaction()` while that implicit
	 * transaction is active raises "There is already an active transaction". This
	 * method commits the implicit transaction before starting the explicit one so
	 * that callers do not need to be aware of this driver quirk.
	 *
	 * @throws TDbException if the connection is not active
	 * @return TDbTransaction the transaction initiated
	 */
	public function beginTransaction()
	{
		if ($this->getActive()) {
			// pdo_firebird in autocommit mode always keeps an implicit transaction
			// open. Commit it before starting an explicit one, otherwise PDO raises
			// "There is already an active transaction".
			if ($this->getDriverName() === self::DRIVER_FIREBIRD && $this->getAutoCommit()) {
				try {
					$this->_pdo->commit();
				} catch (\Exception $e) {
					// No implicit transaction was active — safe to ignore.
				}
			}
			$this->_pdo->beginTransaction();
			return $this->_transaction = Prado::createComponent($this->getTransactionClass(), $this);
		} else {
			throw new TDbException('dbconnection_connection_inactive');
		}
	}

	/**
	 * @return string Transaction class name to be created by calling {@see \Prado\Data\TDbConnection::beginTransaction}. Defaults to '\Prado\Data\TDbTransaction'.
	 * @since 3.1.7
	 */
	public function getTransactionClass()
	{
		return $this->_transactionClass;
	}


	/**
	 * @param string $value Transaction class name to be created by calling {@see \Prado\Data\TDbConnection::beginTransaction}.
	 * @since 3.1.7
	 */
	public function setTransactionClass($value)
	{
		$this->_transactionClass = (string) $value;
	}

	/**
	 * Returns the ID of the last inserted row or sequence value.
	 * @param string $sequenceName name of the sequence object (required by some DBMS)
	 * @return string the row ID of the last row inserted, or the last value retrieved from the sequence object
	 * @see http://www.php.net/manual/en/function.PDO-lastInsertId.php
	 */
	public function getLastInsertID($sequenceName = '')
	{
		if ($this->getActive()) {
			return $this->_pdo->lastInsertId($sequenceName);
		} else {
			throw new TDbException('dbconnection_connection_inactive');
		}
	}

	/**
	 * Quotes a string for use in a query.
	 * @param string $str string to be quoted
	 * @return string the properly quoted string
	 * @see http://www.php.net/manual/en/function.PDO-quote.php
	 */
	public function quoteString($str)
	{
		if ($this->getActive()) {
			return $this->_pdo->quote($str);
		} else {
			throw new TDbException('dbconnection_connection_inactive');
		}
	}

	/**
	 * Quotes a table name for use in a query.
	 * @param string $name $name table name
	 * @return string the properly quoted table name
	 */
	public function quoteTableName($name)
	{
		return $this->getDbMetaData()->quoteTableName($name);
	}

	/**
	 * Quotes a column name for use in a query.
	 * @param string $name $name column name
	 * @return string the properly quoted column name
	 */
	public function quoteColumnName($name)
	{
		return $this->getDbMetaData()->quoteColumnName($name);
	}

	/**
	 * Quotes a column alias for use in a query.
	 * @param string $name $name column name
	 * @return string the properly quoted column alias
	 */
	public function quoteColumnAlias($name)
	{
		return $this->getDbMetaData()->quoteColumnAlias($name);
	}

	/**
	 * @return TDbMetaData
	 */
	public function getDbMetaData()
	{
		if ($this->_dbMeta === null) {
			$this->_dbMeta = TDbMetaData::getInstance($this);
		}
		return $this->_dbMeta;
	}

	/**
	 * @return TDbColumnCaseMode the case of the column names
	 */
	public function getColumnCase()
	{
		switch ($this->getAttribute(PDO::ATTR_CASE)) {
			case PDO::CASE_LOWER:
				return TDbColumnCaseMode::LowerCase;
			case PDO::CASE_UPPER:
				return TDbColumnCaseMode::UpperCase;
			case PDO::CASE_NATURAL:
			default:
				return TDbColumnCaseMode::Preserved;
		}
	}

	/**
	 * @param TDbColumnCaseMode $value the case of the column names
	 */
	public function setColumnCase($value)
	{
		switch (TPropertyValue::ensureEnum($value, TDbColumnCaseMode::class)) {
			case TDbColumnCaseMode::Preserved:
				$value = PDO::CASE_NATURAL;
				break;
			case TDbColumnCaseMode::LowerCase:
				$value = PDO::CASE_LOWER;
				break;
			case TDbColumnCaseMode::UpperCase:
				$value = PDO::CASE_UPPER;
				break;
		}
		$this->setAttribute(PDO::ATTR_CASE, $value);
	}

	/**
	 * @return TDbNullConversionMode how the null and empty strings are converted
	 */
	public function getNullConversion()
	{
		switch ($this->getAttribute(PDO::ATTR_ORACLE_NULLS)) {
			case PDO::NULL_EMPTY_STRING:
				return TDbNullConversionMode::EmptyStringToNull;
			case PDO::NULL_TO_STRING:
				return TDbNullConversionMode::NullToEmptyString;
			case PDO::NULL_NATURAL:
			default:
				return TDbNullConversionMode::Preserved;
		}
	}

	/**
	 * @param TDbNullConversionMode $value how the null and empty strings are converted
	 */
	public function setNullConversion($value)
	{
		switch (TPropertyValue::ensureEnum($value, TDbNullConversionMode::class)) {
			case TDbNullConversionMode::Preserved:
				$value = PDO::NULL_NATURAL;
				break;
			case TDbNullConversionMode::EmptyStringToNull:
				$value = PDO::NULL_EMPTY_STRING;
				break;
			case TDbNullConversionMode::NullToEmptyString:
				$value = PDO::NULL_TO_STRING;
				break;
		}
		$this->setAttribute(PDO::ATTR_ORACLE_NULLS, $value);
	}

	/**
	 * @return bool whether creating or updating a DB record will be automatically committed.
	 * Some DBMS (such as sqlite) may not support this feature.
	 */
	public function getAutoCommit()
	{
		if (!$this->getHasAutoCommit()) {
			return false;
		}
		return $this->getAttribute(PDO::ATTR_AUTOCOMMIT);
	}

	/**
	 * @param bool $value whether creating or updating a DB record will be automatically committed.
	 * Some DBMS (such as sqlite) may not support this feature.
	 */
	public function setAutoCommit($value)
	{
		if (!$this->getHasAutoCommit()) {
			return;
		}
		$this->setAttribute(PDO::ATTR_AUTOCOMMIT, TPropertyValue::ensureBoolean($value));
	}

	public function getHasAutoCommit()
	{
		return $this->getDriverName() !== self::DRIVER_SQLITE;
	}

	/**
	 * @return bool whether the connection is persistent or not
	 * Some DBMS (such as sqlite) may not support this feature.
	 */
	public function getPersistent()
	{
		return $this->getAttribute(PDO::ATTR_PERSISTENT);
	}

	/**
	 * @param bool $value whether the connection is persistent or not
	 * Some DBMS (such as sqlite) may not support this feature.
	 */
	public function setPersistent($value)
	{
		return $this->setAttribute(PDO::ATTR_PERSISTENT, TPropertyValue::ensureBoolean($value));
	}

	/**
	 * @return string name of the DB driver
	 */
	public function getDriverName()
	{
		if ($this->getActive()) {
			return $this->getAttribute(PDO::ATTR_DRIVER_NAME);
		}

		$connection = $this->getConnectionString();

		if (is_string($connection) && strpos($connection, ':') !== false) {
			[$driver] = explode(':', $connection, 2);
			return $driver;
		}

		throw new TDbException('dbconnection_connection_inactive');
	}

	/**
	 * @return string the version information of the DB driver
	 */
	public function getClientVersion()
	{
		return $this->getAttribute(PDO::ATTR_CLIENT_VERSION);
	}

	/**
	 * @return string the status of the connection
	 * Some DBMS (such as sqlite) may not support this feature.
	 */
	public function getConnectionStatus()
	{
		return $this->getAttribute(PDO::ATTR_CONNECTION_STATUS);
	}

	/**
	 * @return bool whether the connection performs data prefetching
	 */
	public function getPrefetch()
	{
		return $this->getAttribute(PDO::ATTR_PREFETCH);
	}

	/**
	 * @return string the information of DBMS server
	 */
	public function getServerInfo()
	{
		return $this->getAttribute(PDO::ATTR_SERVER_INFO);
	}

	/**
	 * @return string the version information of DBMS server
	 */
	public function getServerVersion()
	{
		return $this->getAttribute(PDO::ATTR_SERVER_VERSION);
	}

	/**
	 * @return int timeout settings for the connection
	 */
	public function getTimeout()
	{
		return $this->getAttribute(PDO::ATTR_TIMEOUT);
	}

	/**
	 * Obtains a specific DB connection attribute information.
	 * @param int $name the attribute to be queried
	 * @return mixed the corresponding attribute information
	 * @see http://www.php.net/manual/en/function.PDO-getAttribute.php
	 */
	public function getAttribute($name)
	{
		if ($this->_pdo instanceof PDO) {
			if ($this->getActive()) {
				return $this->_pdo->getAttribute($name);
			} else {
				throw new TDbException('dbconnection_connection_inactive');
			}
		} else {
			return $this->_attributes[$name] ?? null;
		}
	}

	/**
	 * Sets an attribute on the database connection.
	 * @param int $name the attribute to be set
	 * @param mixed $value the attribute value
	 * @see http://www.php.net/manual/en/function.PDO-setAttribute.php
	 */
	public function setAttribute($name, $value)
	{
		if ($this->_pdo instanceof PDO) {
			$this->_pdo->setAttribute($name, $value);
		} else {
			$this->_attributes[$name] = $value;
		}
	}
}
