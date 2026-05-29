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
	 * Parameter-type tokens for {@see IDataCommand::bindValue} and
	 * {@see IDataCommand::bindParameter}.  Defaults match `PDO::PARAM_*`; drivers
	 * may override.  Reference via `$conn::PARAM_INT` for runtime polymorphism.
	 * @since 4.3.3
	 */
	public const PARAM_NULL = 0;
	public const PARAM_INT = 1;
	public const PARAM_STR = 2;
	public const PARAM_LOB = 3;
	public const PARAM_BOOL = 5;

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
	 * Creates a command for execution.  `$query` is a SQL string for SQL drivers,
	 * a driver-specific query value for non-SQL drivers.
	 * @param mixed $query the query specification.
	 * @return IDataCommand the new command.
	 */
	public function createCommand($query);

	/**
	 * Begins a new transaction.  Each call allocates a new {@see IDataTransaction}
	 * and supersedes any prior one.  Throws if a transaction is already active.
	 * @return IDataTransaction the transaction for the new work unit.
	 */
	public function beginTransaction();

	/**
	 * @return null|IDataTransaction the active transaction, or null if none is open.
	 */
	public function getCurrentTransaction();

	/**
	 * Returns the last transaction allocated by {@see beginTransaction}, active or
	 * not.  Used by reuse-pattern subclasses to detect supersession before
	 * reactivating a completed transaction.
	 * @return null|IDataTransaction the last transaction, or null if none was ever begun.
	 */
	public function getLastTransaction(): ?IDataTransaction;

	/**
	 * Commits the currently active transaction.  No-op (returns false) when no
	 * transaction is active.
	 * @return ?bool true on commit, false if none was active.
	 */
	public function commit(): ?bool;

	/**
	 * Rolls back the currently active transaction.  No-op (returns false) when
	 * no transaction is active.
	 * @return ?bool true on rollback, false if none was active.
	 */
	public function rollback(): ?bool;

	/**
	 * Returns the ID of the last inserted row or sequence value.  Wraps
	 * `PDO::lastInsertId()` for SQL/PDO drivers.
	 * @param string $sequenceName name of the sequence object (required by some DBMS).
	 * @return string the last insert ID, or empty string if the concept does not apply.
	 */
	public function getLastInsertID($sequenceName = '');

	/**
	 * @return IDataMetaData the metadata helper for schema introspection.
	 */
	public function getDbMetaData();

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
