<?php

/**
 * IDataTableInfo interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common;

use Prado\Data\IDataConnection;

/**
 * IDataTableInfo interface.
 *
 * IDataTableInfo defines the contract for structured schema metadata about a
 * single database table or view. Implementations are returned by
 * {@see IDataMetaData::getTableInfo()} and consumed by command builders,
 * scaffold generators, and the Active Record layer.
 *
 * The concrete implementation for SQL/PDO databases is {@see TDbTableInfo}.
 * Driver-specific subclasses (e.g. {@see TMysqlTableInfo}) extend it with
 * driver-specific column and key handling.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
interface IDataTableInfo
{
	/**
	 * @return string the unquoted table name.
	 */
	public function getTableName();

	/**
	 * Returns all columns defined on this table, keyed by column name.
	 * Each column object implements {@see IDataColumn} (SQL drivers return
	 * {@see TDbTableColumn} subclasses, which additionally implement
	 * {@see IDbColumn}).
	 * @return \Prado\Collections\TMap map of column name to {@see IDataColumn}.
	 */
	public function getColumns();

	/**
	 * Returns the names of all primary-key columns.
	 * @return string[] primary-key column names.
	 */
	public function getPrimaryKeys();

	/**
	 * Returns foreign-key information for this table.
	 * @return array foreign-key descriptors.
	 */
	public function getForeignKeys();

	/**
	 * Creates a command builder bound to this table for the given connection.
	 * @param IDataConnection $connection the database connection.
	 * @return IDataCommandBuilder the command builder for this table.
	 */
	public function createCommandBuilder($connection);
}
