<?php
/**
 * TDbConnection class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data
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
 * TDbConnection works together with {@link TDbCommand}, {@link TDbDataReader}
 * and {@link TDbTransaction} to provide data access to various DBMS
 * in a common set of APIs. They are a thin wrapper of the {@link http://www.php.net/manual/en/ref.pdo.php PDO}
 * PHP extension.
 *
 * To establish a connection, set {@link setActive Active} to true after
 * specifying {@link setConnectionString ConnectionString}, {@link setUsername Username}
 * and {@link setPassword Password}.
 *
 * Since 3.1.2, the connection charset can be set (for MySQL and PostgreSQL databases only)
 * using the {@link setCharset Charset} property. The value of this property is database dependant.
 * e.g. for mysql, you can use 'latin1' for cp1252 West European, 'utf8' for unicode, ...
 *
 * The following example shows how to create a TDbConnection instance and establish
 * the actual connection:
 * <code>
 * $connection=new TDbConnection($dsn,$username,$password);
 * $connection->Active=true;
 * </code>
 *
 * After the DB connection is established, one can execute an SQL statement like the following:
 * <code>
 * $command=$connection->createCommand($sqlStatement);
 * $command->execute();   // a non-query SQL statement execution
 * // or execute an SQL query and fetch the result set
 * $reader=$command->query();
 *
 * // each $row is an array representing a row of data
 * foreach($reader as $row) ...
 * </code>
 *
 * One can do prepared SQL execution and bind parameters to the prepared SQL:
 * <code>
 * $command=$connection->createCommand($sqlStatement);
 * $command->bindParameter($name1,$value1);
 * $command->bindParameter($name2,$value2);
 * $command->execute();
 * </code>
 *
 * To use transaction, do like the following:
 * <code>
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
 * </code>
 *
 * TDbConnection provides a set of methods to support setting and querying
 * of certain DBMS attributes, such as {@link getNullConversion NullConversion}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Data
 * @since 3.0
 */
class TDbConnection extends \Prado\TComponent
{
	/**
	 *
	 * @since 3.1.7
	 */
	const DEFAULT_TRANSACTION_CLASS = '\Prado\Data\TDbTransaction';

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
	 * instance is created. Set {@link setActive Active} property to true
	 * to establish the connection.
	 * Since 3.1.2, you can set the charset for MySql connection
	 *
	 * @param string $dsn The Data Source Name, or DSN, contains the information required to connect to the database.
	 * @param string $username The user name for the DSN string.
	 * @param string $password The password for the DSN string.
	 * @param string $charset Charset used for DB Connection (MySql & pgsql only). If not set, will use the default charset of your database server
	 * @see http://www.php.net/manual/en/function.PDO-construct.php
	 */
	public function __construct($dsn = '', $username = '', $password = '', $charset = '')
	{
		$this->_dsn = $dsn;
		$this->_username = $username;
		$this->_password = $password;
		$this->_charset = $charset;
	}

	/**
	 * Close the connection when serializing.
	 */
	public function __sleep()
	{
//		$this->close(); - DO NOT CLOSE the current connection as serializing doesn't neccessarily mean we don't this connection anymore in the current session
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
					$this->getConnectionString(),
					$this->getUsername(),
					$this->getPassword(),
					$this->_attributes
				);
				// This attribute is only useful for PDO::MySql driver.
				// Ignore the warning if a driver doesn't understand this.
				@$this->_pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
				$this->_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$this->_active = true;
				$this->setConnectionCharset();
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
	 * Set the database connection charset.
	 * Only MySql databases are supported for now.
	 * @since 3.1.2
	 */
	protected function setConnectionCharset()
	{
		if ($this->_charset === '' || $this->_active === false) {
			return;
		}
		switch ($this->_pdo->getAttribute(PDO::ATTR_DRIVER_NAME)) {
			case 'mysql':
			case 'sqlite':
				$stmt = $this->_pdo->prepare('SET NAMES ?');
			break;
			case 'pgsql':
				$stmt = $this->_pdo->prepare('SET client_encoding TO ?');
			break;
			default:
				throw new TDbException('dbconnection_unsupported_driver_charset', $driver);
		}
		$stmt->execute([$this->_charset]);
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
	public function setPassword($value)
	{
		$this->_password = $value;
	}

	/**
	 * @return string the charset used for database connection. Defaults to emtpy string.
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
		$this->_charset = $value;
		$this->setConnectionCharset();
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
	 * @return TDbTransaction the currently active transaction. Null if no active transaction.
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
	 * @throws TDbException if the connection is not active
	 * @return TDbTransaction the transaction initiated
	 */
	public function beginTransaction()
	{
		if ($this->getActive()) {
			$this->_pdo->beginTransaction();
			return $this->_transaction = Prado::createComponent($this->getTransactionClass(), $this);
		} else {
			throw new TDbException('dbconnection_connection_inactive');
		}
	}

	/**
	 * @return string Transaction class name to be created by calling {@link TDbConnection::beginTransaction}. Defaults to '\Prado\Data\TDbTransaction'.
	 * @since 3.1.7
	 */
	public function getTransactionClass()
	{
		return $this->_transactionClass;
	}


	/**
	 * @param string $value Transaction class name to be created by calling {@link TDbConnection::beginTransaction}.
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
			case PDO::CASE_NATURAL:
				return TDbColumnCaseMode::Preserved;
			case PDO::CASE_LOWER:
				return TDbColumnCaseMode::LowerCase;
			case PDO::CASE_UPPER:
				return TDbColumnCaseMode::UpperCase;
		}
	}

	/**
	 * @param TDbColumnCaseMode $value the case of the column names
	 */
	public function setColumnCase($value)
	{
		switch (TPropertyValue::ensureEnum($value, 'Prado\\Data\\TDbColumnCaseMode')) {
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
			case PDO::NULL_NATURAL:
				return TDbNullConversionMode::Preserved;
			case PDO::NULL_EMPTY_STRING:
				return TDbNullConversionMode::EmptyStringToNull;
			case PDO::NULL_TO_STRING:
				return TDbNullConversionMode::NullToEmptyString;
		}
	}

	/**
	 * @param TDbNullConversionMode $value how the null and empty strings are converted
	 */
	public function setNullConversion($value)
	{
		switch (TPropertyValue::ensureEnum($value, 'Prado\\Data\\TDbNullConversionMode')) {
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
		return $this->getAttribute(PDO::ATTR_AUTOCOMMIT);
	}

	/**
	 * @param bool $value whether creating or updating a DB record will be automatically committed.
	 * Some DBMS (such as sqlite) may not support this feature.
	 */
	public function setAutoCommit($value)
	{
		$this->setAttribute(PDO::ATTR_AUTOCOMMIT, TPropertyValue::ensureBoolean($value));
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
		return $this->getAttribute(PDO::ATTR_DRIVER_NAME);
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
		if ($this->getActive()) {
			return $this->_pdo->getAttribute($name);
		} else {
			throw new TDbException('dbconnection_connection_inactive');
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
