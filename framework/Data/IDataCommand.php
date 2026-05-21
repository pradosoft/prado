<?php

/**
 * IDataCommand interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data;

/**
 * IDataCommand interface
 *
 * IDataCommand defines the interface for a data-store command.
 *
 * Implementations include {@see TDbCommand} for SQL/PDO databases.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
interface IDataCommand
{
	/**
	 * @return IDataConnection the connection associated with this command.
	 */
	public function getConnection();

	/**
	 * Executes a non-query operation.
	 *
	 * For SQL this executes INSERT/UPDATE/DELETE statements.
	 *
	 * @return int number of rows or documents affected.
	 */
	public function execute();

	/**
	 * Executes a query and returns a data reader for the results.
	 * @return IDataReader the reader for the query results.
	 */
	public function query();

	/**
	 * Executes a query and returns the first row.
	 * @param bool $fetchAssociative whether to return an associative array (true) or numeric-indexed (false).
	 * @return array|false the first row, or false if no result.
	 */
	public function queryRow($fetchAssociative = true);

	/**
	 * Executes a query and returns the scalar value of the first column in the first row.
	 * @return mixed the scalar value, or false if no result.
	 */
	public function queryScalar();

	/**
	 * Executes a query and returns the values of the first column as an array.
	 * @return array the first-column values of all rows.
	 */
	public function queryColumn();

	/**
	 * Executes a query and returns all rows as an array.
	 * @return array all result rows.
	 */
	public function queryAll();

	/**
	 * Binds a value to a named or positional parameter in the command.
	 *
	 * For SQL commands this maps to {@see \PDOStatement::bindValue()}.
	 * Non-SQL implementations may store the value for later substitution.
	 *
	 * @param int|string $name parameter name (`:name`) or 1-based positional index.
	 * @param mixed $value the value to bind.
	 * @param null|int $dataType PDO data-type constant; null to infer from value.
	 * @return void
	 */
	public function bindValue($name, $value, $dataType = null);

	/**
	 * Binds a PHP variable to a named or positional parameter by reference.
	 *
	 * The variable is read at execute time, so changes made after this call are
	 * reflected when the command runs.
	 *
	 * @param int|string $name parameter name (`:name`) or 1-based positional index.
	 * @param mixed &$value the variable to bind by reference.
	 * @param null|int $dataType PDO data-type constant; null to infer from value.
	 * @param null|int $length maximum expected length in bytes; required by some
	 *   drivers for OUTPUT parameters.
	 * @return void
	 */
	public function bindParameter($name, &$value, $dataType = null, $length = null);
}
