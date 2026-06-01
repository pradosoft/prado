<?php

/**
 * IDataConnection interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data;

use Prado\Data\Common\IDataMetaData;

/**
 * IDataConnection interface
 *
 * IDataConnection defines the interface for a data-store connection.
 *
 * This interface provides a common abstraction over SQL connections
 * ({@see TDbConnection} via PDO), allowing PRADO plugins to supply their own
 * connection implementations through a unified API.
 *
 * For SQL drivers the $query argument to {@see createCommand} is a SQL string.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
interface IDataConnection
{
	/**
	 * @return string the driver name (e.g. 'mysql', 'pgsql', 'sqlite').
	 */
	public function getDriverName();

	/**
	 * @return bool whether the connection is currently open.
	 */
	public function getActive();

	/**
	 * Opens or closes the connection.
	 *
	 * @param bool $value true to open, false to close.
	 */
	public function setActive($value);

	/**
	 * Creates a command for execution against this connection.
	 *
	 * For SQL connections ({@see TDbConnection}), $query is a SQL string.
	 *
	 * @param mixed $query the query specification (SQL string or equivalent).
	 * @return IDataCommand the new command object.
	 */
	public function createCommand($query);

	/**
	 * Begins a new transaction.
	 *
	 * Each call allocates a **new** {@see IDataTransaction} object.  Any
	 * previously returned transaction object is superseded: calling
	 * {@see IDataTransaction::beginTransaction()} on it will throw because it is
	 * no longer the connection's current transaction.
	 *
	 * Throws an exception if a transaction is already active. Commit or roll back
	 * the current transaction before starting a new one.
	 *
	 * To reuse the same transaction object for sequential work units without
	 * allocating a new one, call {@see IDataTransaction::beginTransaction()}
	 * directly on the returned object after commit or rollback.
	 *
	 * @return IDataTransaction the transaction object for the new work unit.
	 */
	public function beginTransaction();

	/**
	 * Returns the currently active transaction, or null if none is open.
	 * If a transaction is not active (as in, the transaction has been completed),
	 * then this returns null.
	 *
	 * @return null|IDataTransaction the active transaction, or null.
	 */
	public function getCurrentTransaction();

	/**
	 * Returns the ID of the last inserted row or sequence value.
	 *
	 * For SQL drivers this wraps {@see \PDO::lastInsertId()}.
	 *
	 * @param string $sequenceName name of the sequence object (required for
	 *   PostgreSQL, IBM DB2, Oracle, and Firebird; ignored for MySQL and SQLite).
	 * @return string the last insert ID as a string.
	 */
	public function getLastInsertID($sequenceName = '');

	/**
	 * Returns the DSN / connection string used to open this connection.
	 *
	 * For SQL/PDO connections this is the PDO DSN. May return an empty string
	 * if the concept does not apply to a given implementation.
	 *
	 * @return string the connection string.
	 */
	public function getConnectionString();

	/**
	 * Quotes a string value for safe embedding in a SQL statement.
	 *
	 * Wraps {@see \PDO::quote()} for SQL drivers.
	 *
	 * @param string $str the string to quote.
	 * @return string the quoted string.
	 */
	public function quoteString($str);

	/**
	 * Returns the current column-case mode for result-set column names.
	 *
	 * Maps to the PDO `ATTR_CASE` attribute for SQL connections.
	 *
	 * @return mixed the column-case mode (typically a {@see TDbColumnCaseMode} value).
	 */
	public function getColumnCase();

	/**
	 * Sets the column-case mode for result-set column names.
	 *
	 * Maps to the PDO `ATTR_CASE` attribute for SQL connections.
	 *
	 * @param mixed $value the column-case mode.
	 */
	public function setColumnCase($value);

	/**
	 * Returns a PDO connection attribute value.
	 *
	 * For SQL/PDO connections this wraps {@see \PDO::getAttribute()}.
	 *
	 * @param int $name PDO attribute constant.
	 * @return mixed the attribute value.
	 */
	public function getAttribute($name);

	/**
	 * Sets a PDO connection attribute.
	 *
	 * For SQL/PDO connections this wraps {@see \PDO::setAttribute()}.
	 *
	 * @param int $name PDO attribute constant.
	 * @param mixed $value the attribute value to set.
	 */
	public function setAttribute($name, $value);
}
