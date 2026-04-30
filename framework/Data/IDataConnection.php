<?php

/**
 * IDataConnection interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data;

/**
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
	 * Begins a transaction.
	 *
	 * For drivers that use serial transactions (e.g. Firebird) where a transaction
	 * is always active, this returns the existing active transaction object without
	 * starting a new one.
	 *
	 * @return IDataTransaction the transaction object.
	 */
	public function beginTransaction();

	/**
	 * Returns the currently active transaction, or null if none is open.
	 *
	 * @return null|IDataTransaction the active transaction, or null.
	 */
	public function getCurrentTransaction();

	/**
	 * Commits the currently active transaction on this connection.
	 *
	 * This is a convenience method for serial-transaction connections (e.g. Firebird)
	 * where the caller may not hold a reference to the transaction object.
	 * Returns false (and is a no-op) when no transaction is active.
	 *
	 * @return bool true if a transaction was committed, false if none was active.
	 */
	public function commit(): bool;

	/**
	 * Rolls back the currently active transaction on this connection.
	 *
	 * This is a convenience method for serial-transaction connections (e.g. Firebird)
	 * where the caller may not hold a reference to the transaction object.
	 * Returns false (and is a no-op) when no transaction is active.
	 *
	 * @return bool true if a transaction was rolled back, false if none was active.
	 */
	public function rollback(): bool;
}
