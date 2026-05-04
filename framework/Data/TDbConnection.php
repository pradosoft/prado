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
 * TDbConnection represents a PHP PDO connection to a database.
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
 * Since 4.3.3, the connection charset can be set (for all PDO drivers except
 * IBM DB2) via the {@see setCharset Charset} property using driver-independent
 * IANA-style names such as 'UTF-8' or 'ISO-8859-1'; the value is translated to
 * the driver-specific format automatically.
 *
 * Firebird (firebird), MSSQL (mssql, sqlsrv, dblib), and Oracle (oci) do not
 * support runtime charset switching via SQL; their charset must be configured
 * before the connection is opened (it is injected into the DSN automatically).
 * IBM DB2 (ibm) has no charset support at all.
 *
 * The driver-specific charset name in use can be retrieved from an active
 * connection via {@see getDatabaseCharset()}.  Live charset discovery (by
 * querying the server) is supported for mysql, pgsql, sqlite, and firebird;
 * for other drivers the resolved charset property value is returned.  These
 * charsets inspect the dns for overriding charset to retrieve it for the
 * property, or sets the charset in the dns from the property.
 *
 * PostgreSQL and SQLite do not support DSN-level charset; PostgreSQL applies it
 * after connect, SQLite applies it via PRAGMA before any tables are created
 * (silently ignored thereafter).
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
 * @author Brad Anderson <belisoful@icloud.com> Charset, TDbDriverCapabilities
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
	 * @var string Fully-qualified class name used to allocate transaction objects.
	 *   Defaults to {@see DEFAULT_TRANSACTION_CLASS} (TDbTransaction).
	 *   Never null: {@see setTransactionClass} resets to the default on empty/null input.
	 * @since 3.1.7
	 */
	private $_transactionClass = self::DEFAULT_TRANSACTION_CLASS;

	/**
	 * Constructor.
	 *
	 * The DB connection is not established until {@see setActive Active} is set
	 * to true.
	 *
	 * @param string $dsn The Data Source Name containing the information required
	 *   to connect to the database.
	 * @param string $username The user name for the DSN string.
	 * @param string $password The password for the DSN string.
	 * @param string $charset Charset for the connection (driver-independent name,
	 *   e.g. 'UTF-8').  Not supported for IBM DB2 (ibm).  For MSSQL and Oracle
	 *   the value is applied at DSN level before the connection opens; for other
	 *   drivers it is applied after connect.  Defaults to empty (server default).
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
	 * Excludes non-serializable and connection-runtime state from serialization.
	 *
	 * `_pdo` is excluded because PDO instances are never serializable.
	 * `_active` is excluded because the connection cannot survive serialization;
	 * it will be `false` (the declared default) after deserialization and the
	 * caller is responsible for reopening it.
	 * `_transaction` is excluded because an in-flight transaction requires a
	 * live PDO; without one it would be inconsistent after deserialization.
	 * `_dbMeta` is excluded when null because it is a lazy-loaded cache that
	 * will be repopulated on first use; a populated instance is worth keeping.
	 *
	 * Note: the connection is intentionally NOT closed during serialization
	 * because serializing does not necessarily mean the connection is no longer
	 * needed in the current process.
	 *
	 * @param array $exprops by reference, list of property names to exclude.
	 * @since 4.3.3
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$exprops[] = "\0" . TDbConnection::class . "\0_pdo";
		$exprops[] = "\0" . TDbConnection::class . "\0_active";
		$exprops[] = "\0" . TDbConnection::class . "\0_transaction";
		if ($this->_dbMeta === null) {
			$exprops[] = "\0" . TDbConnection::class . "\0_dbMeta";
		}
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

		$dsn = $this->getConnectionString();
		$charsetInDsn = $this->extractCharsetFromDsn($dsn);

		try {
			$pdo = $this->_pdo = new PDO(
				$this->applyCharsetToDsn($dsn),
				$this->getUsername(),
				$this->getPassword(),
				$this->_attributes
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

			// If DSN had a charset, it takes precedence -> reset charset property if different
			if ($charsetInDsn !== null) {
				$newPropCharset = TDbDriverCapabilities::unresolveCharset($charsetInDsn, $driver);
				if (TDbDriverCapabilities::canonicalizeCharset($this->_charset) !==
					TDbDriverCapabilities::canonicalizeCharset($newPropCharset)) {
					$this->_charset = $newPropCharset;
				}
			}

			if (TDbDriverCapabilities::requiresPostConnectCharset($driver)) {
				$this->setConnectionCharset($this->getCharset());	// PostgreSQL, sets charset after
			}
		} catch (PDOException $e) {
			throw new TDbException('dbconnection_open_failed', $e->getMessage());
		}
	}

	/**
	 * Extracts the charset value from a DSN string, if present.
	 *
	 * Uses the driver-specific DSN pattern from
	 * {@see TDbDriverCapabilities::getCharsetDsnPattern} to detect a charset
	 * directive in the DSN, and returns the value if found.
	 *
	 * This is used during connection opening to capture any charset that was
	 * embedded in the DSN so it can be unresolved back to the PRADO charset
	 * via {@see TDbDriverCapabilities::unresolveCharset}.
	 *
	 * @param string $dsn the DSN string to inspect
	 * @return null|string the charset value from the DSN, or null if not present
	 * @since 4.3.3
	 */
	protected function extractCharsetFromDsn(string $dsn): ?string
	{
		$driver = $this->extractDriverFromDsn($dsn);
		if ($driver === null) {
			return null;
		}

		$pattern = TDbDriverCapabilities::getCharsetDsnPattern($driver);

		if ($pattern === null) {
			return null;
		}

		$existingPattern = TDbDriverCapabilities::getCharsetDsnPattern($driver);
		if ($existingPattern !== null && preg_match($existingPattern, $dsn, $matches)) {
			return trim($matches[1]);
		}

		return null;
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
			} catch (PDOException $e) {
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
	 *
	 * The concrete {@see TDbCommand} subclass is selected via
	 * {@see TDbDriverCapabilities::getCommandClass()} so that driver-specific
	 * behaviour (e.g. the pdo_oci prepared-statement workaround in
	 * {@see \Prado\Data\Common\Oracle\TOracleDbCommand}) is applied
	 * automatically without any driver checks in calling code.
	 *
	 * @param string $sql SQL statement associated with the new command.
	 * @throws TDbException if the connection is not active
	 * @return TDbCommand the DB command
	 */
	public function createCommand($sql)
	{
		$this->assertActive();
		$class = TDbDriverCapabilities::getCommandClass($this->getDriverName());
		return new $class($this, $sql);
	}

	/**
	 * Returns the currently active transaction, or null if none is open.
	 * Use this to check for an active Transaction.
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
	 * Returns the last {@see TDbTransaction} object associated with this
	 * connection, whether or not it is still active.
	 *
	 * This is the transaction stored internally when {@see beginTransaction()}
	 * was last called.  It differs from {@see getCurrentTransaction()}, which
	 * returns non-null only while the transaction is open.
	 *
	 * The primary use case is inside {@see TDbTransaction::beginTransaction()}:
	 * before reactivating a completed transaction object the method checks that
	 * the object is still the last one associated with this connection.  If a
	 * caller has since invoked {@see beginTransaction()} again, a new
	 * {@see TDbTransaction} is stored here and the old object is considered
	 * superseded — attempting to restart it would silently bypass the new
	 * transaction's lifecycle.
	 *
	 * @return null|TDbTransaction the last transaction object, or null if
	 *   {@see beginTransaction()} has never been called on this connection.
	 * @since 4.3.3
	 */
	public function getLastTransaction(): ?TDbTransaction
	{
		return $this->_transaction;
	}

	/**
	 * Creates a new {@see IDataTransaction} for this connection.
	 *
	 * @return IDataTransaction A new transaction from this connection.
	 * @since 4.3.3
	 */
	protected function createTransaction(): IDataTransaction
	{
		return Prado::createComponent($this->getTransactionClass(), $this);
	}

	/**
	 * Starts a transaction.
	 *
	 * Throws {@see TDbException} if the connection is not active, or if a
	 * transaction is already open (i.e. {@see getCurrentTransaction()} returns
	 * non-null). Commit or roll back the current transaction before starting
	 * a new one.
	 *
	 * Each call allocates a **new** {@see TDbTransaction} object and stores it
	 * as the last transaction via {@see getLastTransaction()}.  Any previously
	 * returned transaction object is superseded: calling
	 * {@see TDbTransaction::beginTransaction()} on it will throw because it is
	 * no longer the connection's current transaction object.
	 *
	 * For pdo_firebird, a pre-begin flush (PDO::commit()) is issued before
	 * PDO::beginTransaction() to clear Firebird's always-running implicit
	 * transaction; without this the driver throws "There is already an active
	 * transaction".
	 *
	 * @throws TDbException if the connection is not active, or if a transaction
	 *   is already open with uncommitted work.
	 * @return TDbTransaction the transaction object for the new work unit.
	 * @see TDbTransaction::beginTransaction
	 */
	public function beginTransaction()
	{
		$this->assertActive();

		if ($this->_transaction !== null && $this->_transaction->getActive()) {
			throw new TDbException('dbconnection_active_transaction');
		}

		$pdo = $this->getPdoInstance();
		if (TDbDriverCapabilities::requiresPreBeginTransactionFlush($this->getDriverName())) {
			// Firebird keeps an implicit transaction alive at all times; commit it
			// before calling PDO::beginTransaction() so the driver does not throw
			// "There is already an active transaction".
			try {
				$pdo->commit();
			} catch (PDOException $e) {
			}
		}
		$pdo->beginTransaction();
		$this->_transaction = $this->createTransaction();
		return $this->_transaction;
	}

	/**
	 * Convenience method: commits the current transaction on this connection.
	 *
	 * Delegates to the active transaction's {@see TDbTransaction::commit()} method.
	 * If no transaction is currently active (i.e. {@see getCurrentTransaction()}
	 * returns null), this method is a safe no-op and returns false.
	 *
	 * @return ?bool true if a transaction was committed, false if none was active,
	 *   null if the connection itself is not active.
	 * @since 4.3.3
	 */
	public function commit(): ?bool
	{
		if (!$this->getActive()) {
			return null;
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
	 * If no transaction is currently active (i.e. {@see getCurrentTransaction()}
	 * returns null), this method is a safe no-op and returns false.
	 *
	 * @return ?bool true if a transaction was rolled back, false if none was active,
	 *   null if the connection itself is not active.
	 * @since 4.3.3
	 */
	public function rollback(): ?bool
	{
		if (!$this->getActive()) {
			return null;
		}
		$txn = $this->getCurrentTransaction();
		if ($txn === null || !$txn->getActive()) {
			return false;
		}
		$txn->rollback();
		return true;
	}

	/**
	 * Returns the fully-qualified class name used to create transaction objects.
	 *
	 * The default is {@see DEFAULT_TRANSACTION_CLASS} (`TDbTransaction`).
	 * The property is never null: passing null or an empty string to
	 * {@see setTransactionClass} resets it to the default.
	 *
	 * @return string fully-qualified transaction class name.
	 * @since 3.1.7
	 */
	public function getTransactionClass(): string
	{
		return $this->_transactionClass;
	}

	/**
	 * Sets the fully-qualified class name used to create transaction objects.
	 *
	 * Pass null or an empty string to reset to {@see DEFAULT_TRANSACTION_CLASS}.
	 * The supplied class must be instantiable with a single {@see TDbConnection}
	 * argument and should implement {@see IDataTransaction}.
	 *
	 * @param ?string $value fully-qualified transaction class name, or null/empty to reset.
	 * @since 3.1.7
	 */
	public function setTransactionClass($value)
	{
		if (empty($value)) {
			$value = self::DEFAULT_TRANSACTION_CLASS;
		} else {
			$value = TPropertyValue::ensureString($value);
		}
		$this->_transactionClass = $value;
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
		return $this->getPdoInstance()->lastInsertId($sequenceName);
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
	 * Returns whether DML statements are automatically committed outside an
	 * explicit transaction.
	 *
	 * Reads the live `PDO::ATTR_AUTOCOMMIT` attribute from the connection.
	 * Returns `false` without querying PDO when the driver does not expose this
	 * attribute (i.e. when {@see getHasAutoCommit()} is false).
	 *
	 * @return bool true if auto-commit is enabled, false otherwise or when the
	 *   driver does not support the `PDO::ATTR_AUTOCOMMIT` attribute.
	 */
	public function getAutoCommit()
	{
		if (!$this->getHasAutoCommit()) {
			return false;
		}
		return (bool) $this->getAttribute(PDO::ATTR_AUTOCOMMIT);
	}

	/**
	 * Enables or disables auto-commit on the connection.
	 *
	 * When the driver does not expose `PDO::ATTR_AUTOCOMMIT` (i.e. when
	 * {@see getHasAutoCommit()} is false) this method is a silent no-op.
	 *
	 * @param bool $value true to enable auto-commit, false to disable it.
	 */
	public function setAutoCommit($value)
	{
		if (!$this->getHasAutoCommit()) {
			return;
		}
		$this->setAttribute(PDO::ATTR_AUTOCOMMIT, TPropertyValue::ensureBoolean($value));
	}

	/**
	 * Returns whether the current driver exposes the `PDO::ATTR_AUTOCOMMIT`
	 * attribute.
	 *
	 * Delegates to {@see TDbDriverCapabilities::hasAutoCommitAttribute}. When
	 * this returns false, {@see getAutoCommit()} always returns false and
	 * {@see setAutoCommit()} is a no-op.  Drivers known to expose the attribute
	 * include mysql, pgsql, oci, sqlsrv, dblib, mssql, and ibm.
	 *
	 * @return bool true if the driver exposes `PDO::ATTR_AUTOCOMMIT`.
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

		$dsn = $this->getConnectionString();
		$driver = $this->extractDriverFromDsn($dsn);
		if ($driver === null) {
			throw new TDbException('dbconnection_connection_inactive');
		}
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
	 * Throws a {@see TDbException} if the connection is not currently active.
	 *
	 * Call this at the top of any method that requires an open connection.
	 *
	 * @throws TDbException if the connection is not active.
	 * @since 4.3.3
	 */
	public function assertActive()
	{
		if (!$this->getActive()) {
			throw new TDbException('dbconnection_connection_inactive');
		}
	}

	/**
	 * @param mixed $dsn
	 * @return ?string Driver name from dsn, or null if invalid or not found.
	 * @since 4.3.3
	 */
	protected function extractDriverFromDsn($dsn): ?string
	{
		if (!is_string($dsn) || strpos($dsn, ':') === false) {
			return null;
		}
		[$driver] = explode(':', $dsn, 2);
		return strtolower($driver);
	}
}
