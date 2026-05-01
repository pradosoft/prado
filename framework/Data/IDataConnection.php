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
	 * Returns the last {@see IDataTransaction} object associated with this
	 * connection, whether or not it is still active.
	 *
	 * Differs from {@see getCurrentTransaction()}, which returns non-null only
	 * while a transaction is open.  This method returns the object stored when
	 * {@see beginTransaction()} was last called, regardless of its state.
	 *
	 * The primary use case is the supersession guard inside
	 * {@see IDataTransaction::beginTransaction()}: before reactivating a
	 * completed transaction object the implementation checks that it is still
	 * the last one on the connection.  If {@see beginTransaction()} has been
	 * called again since, a newer object is stored here and the old one is
	 * considered superseded.
	 *
	 * @return null|IDataTransaction the last transaction object, or null if
	 *   {@see beginTransaction()} has never been called on this connection.
	 */
	public function getLastTransaction(): ?IDataTransaction;

	/**
	 * Commits the currently active transaction on this connection.
	 *
	 * A convenience method for cases where the caller does not hold a reference
	 * to the transaction object. Returns false (and is a no-op) when no
	 * transaction is active.
	 *
	 * @return ?bool true if a transaction was committed, false if none was active.
	 */
	public function commit(): ?bool;

	/**
	 * Rolls back the currently active transaction on this connection.
	 *
	 * A convenience method for cases where the caller does not hold a reference
	 * to the transaction object. Returns false (and is a no-op) when no
	 * transaction is active.
	 *
	 * @return ?bool true if a transaction was rolled back, false if none was active.
	 */
	public function rollback(): ?bool;
}
