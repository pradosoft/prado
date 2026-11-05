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
 * IDataTableInfo interface
 *
 * IDataTableInfo defines the interface for table (or view) metadata.
 *
 * The interface is shaped after {@see TDbTableInfo}, which is the canonical SQL
 * implementation, but is intentionally decoupled from it so that application
 * code and third-party plugins can supply custom implementations without
 * coupling to the SQL class hierarchy.  Terminology is relational (columns,
 * primary keys, foreign keys) rather than document-store-centric.
 *
 * Concrete implementations: {@see TDbTableInfo} and its driver-specific
 * subclasses ({@see TMysqlTableInfo}, {@see TSqliteTableInfo},
 * {@see TPgsqlTableInfo}, {@see TSqlSrvTableInfo}, {@see TOracleTableInfo},
 * {@see TIbmTableInfo}, {@see TFirebirdTableInfo}).
 *
 * Required for portability — see {@see \Prado\Data\IDataConnection} for the full driver-portability roadmap.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
interface IDataTableInfo
{
	/**
	 * @return string the unqualified table or view name.
	 */
	public function getTableName();

	/**
	 * @return string the fully-qualified table name (schema + table where applicable).
	 */
	public function getTableFullName();

	/**
	 * @return bool whether this metadata describes a view rather than a base table.
	 */
	public function getIsView();

	/**
	 * Returns all column metadata objects for the table or collection, keyed by column name.
	 * The SQL implementation, {@see TDbTableInfo}, returns them in a {@see \Prado\Collections\TMap}.
	 *
	 * @return array<string, IDataColumn>|\Prado\Collections\TMap the column metadata objects.
	 */
	public function getColumns();

	/**
	 * Returns the column metadata for a specific column, or null if not found.
	 *
	 * @param string $name the column name.
	 * @return ?IDataColumn the column metadata, or null.
	 */
	public function getColumn($name);

	/**
	 * Returns the names of all columns defined for the table.
	 *
	 * @return string[] the column names.
	 */
	public function getColumnNames();

	/**
	 * Returns a map of lower-cased column name to actual column name.
	 * Consumed by the driver-agnostic dynamic-finder path
	 * ({@see \Prado\Data\DataGateway\TDataGatewayCommand::extractMatchingConditions()})
	 * to resolve case-insensitive property substrings against known columns.
	 * @return array<string, string> lower-cased column name mapped to actual column name.
	 * @since 4.4.0
	 */
	public function getLowerCaseColumnNames();

	/**
	 * Returns the names of the primary-key columns.
	 *
	 * @return string[] primary-key column names; empty array if none defined.
	 */
	public function getPrimaryKeys();

	/**
	 * Returns the foreign-key descriptors for the table.
	 *
	 * The exact structure of each descriptor is driver-specific, but each entry
	 * describes a foreign-key relationship for one or more columns.
	 *
	 * @return array foreign-key descriptors; empty array if none defined.
	 */
	public function getForeignKeys();

	/**
	 * Creates a command builder for CRUD operations on this table.
	 *
	 * @param IDataConnection $connection the connection to use.
	 * @return IDataCommandBuilder a new command builder for this table.
	 */
	public function createCommandBuilder($connection);
}
