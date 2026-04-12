<?php

/**
 * IDataReader interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data;

/**
 * IDataReader defines the interface for a forward-only data result reader.
 *
 * Implementations include {@see TDbDataReader} for SQL/PDO result sets.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
interface IDataReader extends \Iterator
{
	/**
	 * Reads the next row and advances the internal cursor.
	 * @return array|false the next row as an associative array, or false if no more rows.
	 */
	public function read();

	/**
	 * Reads all remaining rows into an array.
	 * @return array all remaining rows, each as an associative array.
	 */
	public function readAll();

	/**
	 * Closes the reader and releases any held resources.
	 */
	public function close();

	/**
	 * @return bool whether the reader has been closed.
	 */
	public function getIsClosed();

	/**
	 * Returns the number of rows in the result set.
	 * Note: for some drivers (e.g. most SQL SELECT statements) this value may not
	 * be accurate without fetching all rows first.
	 * @return int the row count.
	 */
	public function getRowCount();
}
