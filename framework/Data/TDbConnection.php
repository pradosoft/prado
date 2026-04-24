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
	/**
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
	 * @var null|string null means auto-detect from the driver name.
	 * @since 3.1.7
	 */
	private ?string $_transactionClass = null;

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
		$pdo = $this->getPdoInstance();
		
		if ($pdo !== null) {
			return;
		}
		
		try {
			$pdo = $this->_pdo = new PDO(
				$this->applyCharsetToDsn($this->getConnectionString()),
				$this->getUsername(), $this->getPassword(), $this->_attributes
			);
			
			{	// For Mysql, ignore otherwise
				@$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true); 
				// This attribute is only useful for PDO::MySql driver since PHP 8.1
				// This ensures integers are returned as strings (needed eg. for ZEROFILL columns)
				@$pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, true);
			}
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->_active = true;
			$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

			if (TDbDriverCapabilities::requiresPostConnectCharset($driver)) {
				$this->setConnectionCharset($this->getCharset());	// PostgreSQL, sets after
			}
			if (TDbDriverCapabilities::usesSerialTransaction($driver)) {
				$this->_transaction = Prado::createComponent($this->getTransactionClass(), $this);
			}
		} catch (PDOException $e) {
			throw new TDbException('dbconnection_open_failed', $e->getMessage());
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

	/**
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
	 * All charset values are resolved through {@see TDbDriverCapabilities::resolveCharset}
	 * before being sent to the database, so universal names like 'UTF-8' or
	 * 'ISO-8859-1' work across all supported drivers without any
	 * driver-specific knowledge from the caller.
	 * @since 3.1.2
	 * @param null|mixed $charset
	 */
	protected function setConnectionCharset($charset = null)
	{
		if ($charset === null) {
			$charset = $this->getCharset();
		}

		if ($charset === '' || $this->getActive() === false) {
			return;
		}
		$pdo = $this->getPdoInstance();
		$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
		$charset = TDbDriverCapabilities::resolveCharset($charset, $driver);

		if (($pragmaSql = TDbDriverCapabilities::getCharsetPragmaSql($driver)) !== null) {
			try {	// SQLite, and only before tables are created.
				$pdo->exec(sprintf($pragmaSql, $pdo->quote($charset)));
			} catch (\Exception $e) {
				// Silently ignored.
			}
			return;
		}

		if (($sql = TDbDriverCapabilities::getCharsetSetSql($driver)) !== null) {
			$pdo->prepare($sql)->execute([$charset]);
			return;
		}

		if (TDbDriverCapabilities::getCharsetDsnParam($driver) !== null) {
			// Driver configures charset via DSN (Firebird, Oracle, MSSQL);
			// runtime switching via SQL is not supported.
			return;
		}

		if (!TDbDriverCapabilities::supportsCharset($driver)) {
			// Driver has no charset support at all (IBM DB2); silently ignore.
			return;
		}

		throw new TDbException('dbconnection_unsupported_driver_charset', $driver);
	}

	/**
	 * Resolves a charset name to its driver-specific equivalent, allowing callers to
	 * use universal IANA-style names like 'UTF-8' or 'ISO-8859-1' regardless of the
	 * underlying database driver.
	 *
	 * Delegates to {@see TDbDriverCapabilities::resolveCharset}. Override this method
	 * to add or change mappings for custom database configurations.
	 *
	 * @param string $charset the charset name as supplied by the caller (e.g. 'UTF-8')
	 * @param string $driver  PDO driver name (e.g. 'mysql', 'pgsql', 'firebird', 'oci')
	 * @return string the charset name appropriate for $driver
	 * @since 4.3.3
	 */
	protected function resolveCharsetForDriver(string $charset, string $driver): string
	{
		return TDbDriverCapabilities::resolveCharset($charset, $driver);
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
	 * Driver capabilities (parameter name, detection pattern) are provided by
	 * {@see TDbDriverCapabilities::getCharsetDsnParam} and
	 * {@see TDbDriverCapabilities::getCharsetDsnPattern}.
	 * PostgreSQL, SQLite, and IBM DB2 have no DSN charset parameter and are
	 * returned unchanged.
	 *
	 * @param string $dsn the raw DSN string as set by the caller
	 * @return string the DSN, with a charset parameter appended if required
	 * @since 4.3.3
	 */
	protected function applyCharsetToDsn(string $dsn): string
	{
		$charset = $this->getCharset();
		if ($charset === '' || $dsn === '') {
			return $dsn;
		}

		$driver = $this->getDriverName();
		$paramName = TDbDriverCapabilities::getCharsetDsnParam($driver);

		if ($paramName === null) {
			// Driver does not use a DSN charset parameter (pgsql, sqlite, ibm, …).
			return $dsn;
		}

		// If the caller already embedded a charset directive, honour it (DSN wins).
		$existingPattern = TDbDriverCapabilities::getCharsetDsnPattern($driver);
		if ($existingPattern !== null && preg_match($existingPattern, $dsn)) {
			return $dsn;
		}

		$resolved = $this->resolveCharsetForDriver($charset, $driver);

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
		$this->_dsn = TPropertyValue::ensureString($value);
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
		$this->_username = TPropertyValue::ensureString($value);
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
		$this->_password = (string) $value; //Sensitive
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
		if ($this->getActive() && !TDbDriverCapabilities::supportsRuntimeCharsetSet($driver)) {
			throw new TDbException('dbconnection_charset_unchangeable', $driver);
		}
		$value = TPropertyValue::ensureString($value);
		$this->_charset = $value;
		$this->setConnectionCharset($value);
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
	 *   oci, sqlsrv, dblib, ibm — charset is configured at the DSN
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
		if (!$this->getActive() || $this->getPdoInstance() === null) {
			return $this->getCharset();
		}
		$driver = $this->getDriverName();
		try {
			$sql = TDbDriverCapabilities::getCharsetQuerySql($driver);
			if ($sql !== null) {
				$result = $this->createCommand($sql)->queryScalar();
				if ($result !== false && $result !== null) {
					return (string) $result;
				}
				// Firebird: MON$ATTACHMENTS query succeeded but returned nothing
				// (MONITOR privilege absent) — fall back to the resolved charset.
				return $this->resolveCharsetForDriver($this->getCharset(), $driver);
			}
			// Drivers that configure charset via DSN (oci, mssql, sqlsrv, dblib, ibm):
			// return the charset name as it was resolved for this driver so the caller
			// can confirm what was injected into the connection string.
			return $this->resolveCharsetForDriver($this->getCharset(), $driver);
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
		$this->assertActive();
		return new TDbCommand($this, $sql);
	}

	/**
	 * Returns the currently active transaction, or null if none is open.
	 *
	 * For drivers that use serial transactions (e.g. Firebird), the transaction
	 * is always active — PDO::beginTransaction() is called in its constructor and
	 * restarted after every commit/rollback, so there is always an explicit
	 * transaction in progress for the lifetime of the connection.
	 *
	 * @return null|TDbTransaction the active transaction, or null.
	 */
	public function getCurrentTransaction()
	{
		if ($this->_transaction !== null && $this->_transaction->getActive()) {
			return $this->_transaction;
		}
		return null;
	}

	/**
	 * @return TDbTransaction A new transaction from this connection.
	 * @since 4.3.3
	 */
	protected function createTransaction(): TDbTransaction
	{
		return Prado::createComponent($this->getTransactionClass(), $this);
	}

	/**
	 * Starts a transaction.
	 *
	 * For drivers that use serial transactions (e.g. Firebird), the transaction
	 * is always in an explicit PDO transaction — started in its constructor
	 * and immediately restarted after every commit or rollback. In that case
	 * the existing TDbTransaction with Serial=true is returned directly;
	 * no PDO calls are made by this method.
	 *
	 * For all other drivers a new {@see TDbTransaction} is created. If the
	 * driver requires it (Firebird without a serial transaction would never
	 * reach this path, but the guard is kept for correctness), any implicit
	 * connection-time transaction is flushed before calling
	 * PDO::beginTransaction().
	 *
	 * @throws TDbException if the connection is not active
	 * @return TDbTransaction the transaction initiated
	 */
	public function beginTransaction()
	{
		$this->assertActive();
		if (TDbDriverCapabilities::requiresPreBeginTransactionFlush($this->getDriverName())) {
			try {
				// Commit any implicit connection-time transaction before starting
				// an explicit one; otherwise PDO raises "There is already an
				// active transaction".
				$this->getPdoInstance()->commit();
			} catch (\Exception $e) {
			}
		}
		
		$this->getPdoInstance()->beginTransaction();
		if ($this->_transaction && $this->_transaction->getActive()) {
			return $this->_transaction;
		}

		return ($this->_transaction = $this->createTransaction());
	}

	/**
	 * Convenience method: commits the current transaction on this connection.
	 *
	 * Delegates to the active transaction's {@see TDbTransaction::commit()} method.
	 * Particularly useful for serial transaction connections (Firebird),
	 * where the transaction object is long-lived and not always held by the caller.
	 *
	 * If no transaction is currently active (i.e. {@see getCurrentTransaction()}
	 * returns null), this method is a safe no-op.
	 *
	 * @since 4.3.3
	 */
	public function commit(): bool
	{
		if (!$this->getActive()) {
			return false;
		}
		$txn = $this->getCurrentTransaction();
		if ($txn === null || !$txn->getActive()) {
			return false;
		}
		$txn->commit();
		return true;
	}

	/**
	 * Convenience method: rolls back the current transaction on this connection.
	 *
	 * Delegates to the active transaction's {@see TDbTransaction::rollback()} method.
	 * Particularly useful for serial transaction connections (Firebird),
	 * where the transaction object is long-lived and not always held by the caller.
	 *
	 * If no transaction is currently active (i.e. {@see getCurrentTransaction()}
	 * returns null), this method is a safe no-op.
	 *
	 * @since 4.3.3
	 */
	public function rollback(): bool
	{
		if (!$this->getActive()) {
			return false;
		}
		$txn = $this->getCurrentTransaction();
		if ($txn === null || !$txn->getActive()) {
			return false;
		}
		$txn->rollback();
		return true;
	}

/**
	 * Returns the transaction class name to use when creating transaction objects.
	 *
	 * When the property has been set explicitly via {@see setTransactionClass},
	 * that value is returned unchanged.
	 *
	 * When the property is null (the default), the class is auto-detected:
	 * - All drivers use {@see TDbTransaction}, which now supports serial
	 *   transaction mode for drivers that keep an implicit transaction
	 *   alive (e.g. Firebird).
	 *
	 * @return string fully-qualified transaction class name.
	 * @since 3.1.7
	 */
	public function getTransactionClass(): string
	{
		if ($this->_transactionClass !== null) {
			return $this->_transactionClass;
		}
		return self::DEFAULT_TRANSACTION_CLASS;
	}

	/**
	 * @param ?string $value fully-qualified transaction class name.
	 * @since 3.1.7
	 */
	public function setTransactionClass($value)
	{
		if ($value !== null) {
			$this->_transactionClass = TPropertyValue::ensureString($value);
		} else {
			$this->_transactionClass = null;
		}
	}

	/**
	 * Returns the ID of the last inserted row or sequence value.
	 * @param string $sequenceName name of the sequence object (required by some DBMS)
	 * @return string the row ID of the last row inserted, or the last value retrieved from the sequence object
	 * @see http://www.php.net/manual/en/function.PDO-lastInsertId.php
	 */
	public function getLastInsertID($sequenceName = '')
	{
		$this->assertActive();
		if ($this->getActive()) {
			return $this->getPdoInstance()->lastInsertId($sequenceName);
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
		$this->assertActive();
		return $this->getPdoInstance()->quote($str);
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

	/**
	 * Tells if the Driver has the AutoCommit attribute
	 * @since 4.3.3
	 */
	public function getHasAutoCommit(): bool
	{
		return TDbDriverCapabilities::hasAutoCommitAttribute($this->getDriverName());
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
		if (!is_string($connection) || strpos($connection, ':') === false) {
			throw new TDbException('dbconnection_connection_inactive');
		}

		[$driver] = explode(':', $connection, 2);
		return $driver;
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
		$pdo = $this->getPdoInstance();
		if ($pdo instanceof PDO) {
			$this->assertActive();
			return $pdo->getAttribute($name);
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
		$pdo = $this->getPdoInstance();
		if ($pdo instanceof PDO) {
			$pdo->setAttribute($name, $value);
		} else {
			$this->_attributes[$name] = $value;
		}
	}

	/**
	 * Sets an attribute on the database connection.
	 * @throws TDbException
	 */
	protected function assertActive()
	{
		if (!$this->getActive()) {
			throw new TDbException('dbconnection_connection_inactive');
		}
	}
}
