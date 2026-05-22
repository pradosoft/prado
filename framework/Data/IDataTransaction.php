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
 * IDataTransaction interface
 *
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
