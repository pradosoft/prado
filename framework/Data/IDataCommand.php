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
	 * Returns the driver-specific type token for a given PHP value, inferred from
	 * the value's runtime type.
	 *
	 * The return value is driver-defined.  For PDO-backed commands
	 * ({@see TDbCommand}) this is a `PDO::PARAM_*` integer constant.
	 * Non-SQL driver implementations may return any type representation
	 * meaningful to their binding layer.
	 *
	 * This is the abstract successor to the deprecated static
	 * {@see \Prado\Data\Common\TDbCommandBuilder::getPdoType()}.
	 *
	 * @param mixed $value the PHP value to inspect.
	 * @return mixed the driver-native type token, or null if the PHP type has no
	 *   direct mapping in this driver.
	 */
	public function getColumnTypeFromValue($value);

	// -------------------------------------------------------------------------
	// SQL/PDO-oriented methods.
	// SQL drivers implement these fully.  Non-SQL drivers should provide no-op
	// stubs (return null or a sensible default) for any method that does not
	// apply to their underlying store.
	// -------------------------------------------------------------------------

	/**
	 * Returns the query text of this command.
	 *
	 * For SQL drivers this is the SQL statement string.  Non-SQL drivers may
	 * return a serialised query representation or an empty string.
	 *
	 * @return string the query text.
	 */
	public function getText();

	/**
	 * Sets the query text of this command.
	 *
	 * For SQL drivers, setting the text cancels any active prepared statement.
	 * Non-SQL drivers may no-op this method or use it to update the internal
	 * query representation.
	 *
	 * @param string $value the query text.
	 */
	public function setText($value);

	/**
	 * Prepares the command for repeated execution.
	 *
	 * For SQL/PDO drivers this compiles the statement and caches the result
	 * until {@see cancel()} or {@see setText()} is called.  Calling this
	 * explicitly is optional; parameter binding triggers it automatically.
	 * Non-SQL drivers may no-op this method.
	 */
	public function prepare();

	/**
	 * Cancels the prepared statement, releasing its resources.
	 *
	 * The next call to {@see execute()} or {@see query()} will re-prepare.
	 * Non-SQL drivers may no-op this method.
	 */
	public function cancel();

	/**
	 * Binds a value to a named or positional parameter.
	 *
	 * The statement is prepared automatically on the first bind call for SQL
	 * drivers.  Non-SQL drivers should map this to the equivalent binding
	 * operation for their store, or no-op if binding is not applicable.
	 *
	 * @param mixed $name parameter identifier — `:name` string for named
	 *   placeholders, or a 1-based integer for positional (`?`) placeholders.
	 * @param mixed $value the value to bind.
	 * @param ?int $dataType a type hint for the driver (e.g. a PDO::PARAM_*
	 *   constant for SQL drivers); null lets the driver infer the type.
	 */
	public function bindValue($name, $value, $dataType = null);

	/**
	 * Binds a PHP variable to a named or positional parameter by reference.
	 *
	 * Unlike {@see bindValue()}, the variable is evaluated at execution time,
	 * not at bind time.  Non-SQL drivers should map this to the equivalent
	 * late-binding operation, or no-op if not applicable.
	 *
	 * @param mixed $name parameter identifier — `:name` string or 1-based integer.
	 * @param mixed $value the variable to bind by reference.
	 * @param ?int $dataType a type hint for the driver; null lets it infer.
	 * @param ?int $length maximum length hint for output parameters.
	 */
	public function bindParameter($name, &$value, $dataType = null, $length = null);
}
