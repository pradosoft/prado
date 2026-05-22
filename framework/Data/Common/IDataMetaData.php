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
 * IDataMetaData interface
 *
 * IDataMetaData defines the interface for retrieving schema metadata from a
 * data store.
 *
 * The interface provides a common abstraction over driver-specific metadata
 * implementations so that application code and PRADO plugins can work with any
 * supported data store through a single, stable API — including future NoSQL or
 * third-party implementations that do not extend {@see TDbMetaData}.
 *
 * The interface covers four areas:
 * - **Table introspection** — {@see getTableInfo()} returns a structured
 *   {@see IDataTableInfo} describing the columns, keys, and constraints of a
 *   named table.
 * - **Command builder factory** — {@see createCommandBuilder()} returns an
 *   {@see IDataCommandBuilder} ready to generate CRUD commands for a table.
 * - **Identifier quoting** — {@see quoteTableName()}, {@see quoteColumnName()},
 *   and {@see quoteColumnAlias()} wrap identifiers in driver-specific delimiters.
 * - **Table discovery** — {@see findTableNames()} enumerates all tables in a
 *   schema.
 *
 * Concrete implementations: {@see TDbMetaData} and its driver-specific
 * subclasses ({@see TMysqlMetaData}, {@see TSqliteMetaData},
 * {@see TPgsqlMetaData}, {@see TMssqlMetaData}, {@see TOracleMetaData},
 * {@see TIbmMetaData}, {@see TFirebirdMetaData}).
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
	 * @param ?string $tableName the table or view name. If null, returns metadata for the current database.
	 * @return IDataTableInfo the table metadata.
	 */
	public function getTableInfo($tableName = null);

	/**
	 * Creates a command builder for performing CRUD operations on a specific table.
	 * @param ?string $tableName the table name.
	 * @return IDataCommandBuilder the command builder instance for the given table.
	 */
	public function createCommandBuilder($tableName = null);

	/**
	 * Returns all table names in the database or schema.
	 * @param string $schema the schema name. Defaults to empty string, meaning the current or default schema.
	 * If not empty, the returned table names will be prefixed with the schema name.
	 * @return array all table names in the database.
	 */
	public function findTableNames($schema = '');
}
