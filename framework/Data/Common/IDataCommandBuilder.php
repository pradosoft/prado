<?php

/**
 * IDataCommandBuilder interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common;

use Prado\Data\IDataConnection;

/**
 * IDataCommandBuilder interface.
 *
 * IDataCommandBuilder defines the contract for building CRUD commands against a
 * specific table. Implementations are returned by
 * {@see IDataMetaData::createCommandBuilder()} and used throughout the Active
 * Record, SqlMap, and DataGateway layers.
 *
 * The concrete implementation for SQL/PDO databases is {@see TDbCommandBuilder}.
 * Driver-specific subclasses override {@see applyLimitOffset()} and other
 * dialect-sensitive helpers.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
interface IDataCommandBuilder
{
	/**
	 * @return IDataConnection the connection this builder operates against.
	 */
	public function getDbConnection();

	/**
	 * @return IDataTableInfo the table metadata this builder is bound to.
	 */
	public function getTableInfo();

	/**
	 * Applies LIMIT and OFFSET clauses to a SQL string in the dialect appropriate
	 * for the underlying driver.
	 * @param string $sql the base SQL statement.
	 * @param int $limit maximum number of rows; -1 means no limit.
	 * @param int $offset number of rows to skip; -1 means no offset.
	 * @return string the modified SQL statement.
	 */
	public function applyLimitOffset($sql, $limit = -1, $offset = -1);

	/**
	 * Creates a SELECT command for this table.
	 * @param mixed $where WHERE clause or condition array.
	 * @param array $parameters bound parameter values.
	 * @param array $ordering ORDER BY column list.
	 * @param int $limit row limit; -1 for none.
	 * @param int $offset row offset; -1 for none.
	 * @return \Prado\Data\IDataCommand the SELECT command.
	 */
	public function createFindCommand($where = '1=1', $parameters = [], $ordering = [], $limit = -1, $offset = -1);

	/**
	 * Creates a SELECT COUNT(*) command for this table.
	 * @param mixed $where WHERE clause or condition array.
	 * @param array $parameters bound parameter values.
	 * @return \Prado\Data\IDataCommand the COUNT command.
	 */
	public function createCountCommand($where = '1=1', $parameters = []);

	/**
	 * Creates an INSERT command for this table.
	 * @param array $data column name → value map.
	 * @return \Prado\Data\IDataCommand the INSERT command.
	 */
	public function createInsertCommand($data);

	/**
	 * Creates an UPDATE command for this table.
	 * @param array $data column name → new value map.
	 * @param string $where WHERE clause.
	 * @param array $parameters bound parameter values.
	 * @return \Prado\Data\IDataCommand the UPDATE command.
	 */
	public function createUpdateCommand($data, $where, $parameters = []);

	/**
	 * Creates a DELETE command for this table.
	 * @param string $where WHERE clause.
	 * @param array $parameters bound parameter values.
	 * @return \Prado\Data\IDataCommand the DELETE command.
	 */
	public function createDeleteCommand($where, $parameters = []);
}
