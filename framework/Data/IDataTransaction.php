<?php

/**
 * IDataTransaction interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data;

use Prado\Data\Common\IDataMetaData;

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
	 * @return bool whether the transaction is currently active.
	 */
	public function getActive();

	/**
	 * @return IDataConnection the connection associated with this transaction.
	 */
	public function getConnection();

	/**
	 * Creates a command for execution within this transaction's connection.
	 *
	 * This is a convenience method equivalent to
	 * `$transaction->getConnection()->createCommand($query)`.
	 *
	 * @param mixed $query the query specification (SQL string or equivalent).
	 * @return IDataCommand the new command object.
	 * @since 4.3.3
	 */
	public function createCommand($query);

	/**
	 * Returns the metadata helper for this transaction's connection.
	 *
	 * This is a convenience method equivalent to
	 * `$transaction->getConnection()->getDbMetaData()`.
	 *
	 * @return IDataMetaData the metadata helper.
	 * @since 4.3.3
	 */
	public function getDbMetaData();

	/**
	 * Commits the transaction.
	 *
	 * For serial transactions (e.g. Firebird), commit immediately restarts a new
	 * explicit transaction so the object remains active and ready for re-use.
	 */
	public function commit();

	/**
	 * Rolls back (aborts) the transaction.
	 *
	 * For serial transactions (e.g. Firebird), rollback immediately restarts a
	 * new explicit transaction so the object remains active and ready for re-use.
	 */
	public function rollback();
}
