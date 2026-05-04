<?php

/**
 * IDataTransaction interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data;

/**
 * IDataTransaction defines the interface for a data-store transaction.
 *
 * This interface provides a common abstraction over database-specific transaction
 * implementations, allowing PRADO plugins to supply their own implementations
 * without coupling to a concrete class.
 *
 * Implementations include {@see TDbTransaction} for SQL/PDO databases.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
interface IDataTransaction
{
	/**
	 * @return IDataConnection the connection associated with this transaction.
	 */
	public function getConnection();

	/**
	 * @return bool whether the transaction is currently active.
	 */
	public function getActive();

	/**
	 * Creates a command for execution within this transaction's connection.
	 *
	 * This is a convenience method equivalent to
	 * `$transaction->getConnection()->createCommand($query)`.
	 *
	 * @param mixed $query the query specification (SQL string or equivalent).
	 * @return IDataCommand the new command object.
	 */
	public function createCommand($query);

	/**
	 * Starts a new transaction on this transaction's connection, reactivating
	 * this transaction object for a new work unit.
	 *
	 * This is the reuse-pattern counterpart to
	 * {@see IDataConnection::beginTransaction()}: it reactivates the existing
	 * object rather than allocating a new one, which avoids unnecessary
	 * object allocation for sequential work units.
	 *
	 * Implementations must guard against supersession: if
	 * {@see IDataConnection::beginTransaction()} was called after this
	 * transaction completed, this object has been superseded and restarting
	 * it must throw an exception rather than silently bypassing the newer
	 * transaction's lifecycle.
	 *
	 * @return static
	 */
	public function beginTransaction(): static;

	/**
	 * Commits the transaction.
	 *
	 * The transaction becomes inactive after commit completes. To start another
	 * work unit, call {@see beginTransaction()} on this object (reuse pattern)
	 * or call {@see IDataConnection::beginTransaction()} for a fresh object.
	 */
	public function commit();

	/**
	 * Rolls back (aborts) the transaction.
	 *
	 * The transaction becomes inactive after rollback completes. To start another
	 * work unit, call {@see beginTransaction()} on this object (reuse pattern)
	 * or call {@see IDataConnection::beginTransaction()} for a fresh object.
	 */
	public function rollback();
}
