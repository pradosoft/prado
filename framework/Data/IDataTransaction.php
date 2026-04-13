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
 * Implementations include {@see TDbTransaction} for SQL/PDO databases.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
interface IDataTransaction
{
	/**
	 * Commits the transaction.
	 */
	public function commit();

	/**
	 * Rolls back (aborts) the transaction.
	 */
	public function rollback();

	/**
	 * @return bool whether the transaction is currently active.
	 */
	public function getActive();

	/**
	 * @return IDataConnection the connection associated with this transaction.
	 */
	public function getConnection();
}
