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
 * ({@see TDbConnection} via PDO), allowing application code to work
 * with either store type through a unified API.
 *
 * For SQL drivers the $query argument to {@see createCommand} is a SQL string.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
interface IDataConnection
{
	/**
	 * @return string name of the Data driver
	 */
	public function getDriverName();

	/**
	 * @return bool whether the connection is open.
	 */
	public function getActive();

	/**
	 * Opens or closes the connection.
	 * @param bool $value true to open, false to close.
	 */
	public function setActive($value);

	/**
	 * Creates a command for execution against this connection.
	 *
	 * For SQL connections ({@see TDbConnection}), $query is a SQL string.
	 *
	 * @param mixed $query the query specification (SQL string or collection name).
	 * @return IDataCommand the new command object.
	 */
	public function createCommand($query);

	/**
	 * Begins a transaction.
	 * @return IDataTransaction the transaction object.
	 */
	public function beginTransaction();

	/**
	 * Returns the currently active transaction, if any.
	 * @return null|IDataTransaction the active transaction, or null if none.
	 */
	public function getCurrentTransaction();
}
