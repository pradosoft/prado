<?php

/**
 * IDataMetaData interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common;

use Prado\Data\IDataConnection;

/**
 * IDataMetaData defines the interface for retrieving metadata information from a data store.
 *
 * This interface provides a common abstraction over database-specific metadata implementations,
 * allowing application code to work with metadata from different database systems through a unified API.
 *
 * Implementations include:
 * - {@see TDbMetaData} subclasses for SQL databases (TMysqlMetaData, TSqliteMetaData, TPgsqlMetaData, etc.)
 * - 3rd Party Implementations, like Mongo.
 * - Future implementations for NoSQL databases and other data stores
 *
 * The interface covers core metadata operations:
 * - Table metadata retrieval (column information, constraints, etc.)
 * - Command builder creation for CRUD operations
 * - Identifier quoting for SQL statements
 * - Table discovery
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
interface IDataMetaData
{
	/**
	 * Returns the database connection associated with this metadata instance.
	 * @return IDataConnection the database connection.
	 */
	public function getDbConnection();

	/**
	 * Retrieves metadata for a specific table or view.
	 * @param null|string $tableName the table or view name. If null, returns metadata for the current database.
	 * @return IDataTableInfo the table metadata.
	 */
	public function getTableInfo($tableName = null);

	/**
	 * Creates a command builder for performing CRUD operations on a specific table.
	 * @param null|string $tableName the table name.
	 * @return IDataCommandBuilder the command builder instance for the given table.
	 */
	public function createCommandBuilder($tableName = null);

	/**
	 * Quotes a table name for use in SQL queries.
	 * @param string $name the table name to quote.
	 * @return string the properly quoted table name.
	 */
	public function quoteTableName($name);

	/**
	 * Quotes a column name for use in SQL queries.
	 * @param string $name the column name to quote.
	 * @return string the properly quoted column name.
	 */
	public function quoteColumnName($name);

	/**
	 * Quotes a column alias for use in SQL queries.
	 * @param string $name the column alias to quote.
	 * @return string the properly quoted column alias.
	 */
	public function quoteColumnAlias($name);

	/**
	 * Returns all table names in the database or schema.
	 * @param string $schema the schema name. Defaults to empty string, meaning the current or default schema.
	 * If not empty, the returned table names will be prefixed with the schema name.
	 * @return array all table names in the database.
	 */
	public function findTableNames($schema = '');
}
