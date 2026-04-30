<?php

/**
 * IDataCommandBuilder interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common;

use Prado\Data\IDataCommand;
use Prado\Data\IDataConnection;

/**
 * IDataCommandBuilder defines the interface for creating SQL command objects for
 * CRUD operations on a single table.
 *
 * This interface provides a common abstraction over database-specific command
 * builder implementations, allowing application code and PRADO plugins to supply
 * their own {@see TDbCommandBuilder} subclasses (or entirely custom builders)
 * without coupling to a concrete class.
 *
 * The interface is shaped after {@see TDbCommandBuilder}, which is the canonical
 * SQL implementation. The method signatures — WHERE clauses, parameter arrays,
 * ordering arrays, limit/offset integers, and a column-select string — reflect
 * relational database conventions and are intentionally SQL-centric.
 *
 * Implementations include:
 * - {@see TDbCommandBuilder} and its driver-specific subclasses (MySQL, PostgreSQL,
 *   SQLite, Firebird, MSSQL, Oracle, IBM DB2).
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
interface IDataCommandBuilder
{
	// -----------------------------------------------------------------------
	// Accessors
	// -----------------------------------------------------------------------

	/**
	 * @return IDataConnection the connection this builder operates on.
	 */
	public function getDbConnection();

	/**
	 * @return IDataTableInfo the table metadata this builder targets.
	 */
	public function getTableInfo();

	/**
	 * Returns the last inserted ID for the table, using any sequence column
	 * defined in the table metadata.
	 *
	 * @return mixed the last inserted ID or sequence value, or null if the table
	 *   has no sequence column.
	 */
	public function getLastInsertID();

	// -----------------------------------------------------------------------
	// Query-building helpers
	// -----------------------------------------------------------------------

	/**
	 * Appends LIMIT and OFFSET clauses to a SQL string.
	 *
	 * @param string $sql the SQL string to modify.
	 * @param int $limit maximum rows to return; negative means no limit.
	 * @param int $offset number of rows to skip; negative means no offset.
	 * @return string the SQL string with LIMIT/OFFSET applied.
	 */
	public function applyLimitOffset($sql, $limit = -1, $offset = -1);

	/**
	 * Appends an ORDER BY clause to a SQL string.
	 *
	 * @param string $sql the SQL string to modify.
	 * @param array $ordering column-name → direction ('asc'|'desc') pairs.
	 * @return string the SQL string with ORDER BY applied.
	 */
	public function applyOrdering($sql, $ordering);

	/**
	 * Builds a SQL WHERE expression that searches a set of columns for keywords.
	 *
	 * @param array $fields column IDs to search.
	 * @param string $keywords space-separated search terms.
	 * @return string a SQL condition string (may be empty if no terms or fields given).
	 */
	public function getSearchExpression($fields, $keywords);

	/**
	 * Returns the list of column expressions to use in a SELECT clause.
	 *
	 * @param mixed $data '*' for all columns, null for the table's default column
	 *   list, a comma-separated column name string, or an associative data array
	 *   whose keys are column names.
	 * @return string[] fully-quoted column expressions suitable for SELECT.
	 */
	public function getSelectFieldList($data = '*');

	/**
	 * Applies ordering, limit, offset, and bound parameters to a SQL string and
	 * returns the resulting command.
	 *
	 * @param string $sql the base SQL string.
	 * @param array $parameters name-value pairs (or positional values) to bind.
	 * @param array $ordering column → direction pairs.
	 * @param int $limit maximum rows; negative means no limit.
	 * @param int $offset rows to skip; negative means no offset.
	 * @return IDataCommand the command ready for execution.
	 */
	public function applyCriterias($sql, $parameters = [], $ordering = [], $limit = -1, $offset = -1);

	// -----------------------------------------------------------------------
	// Command factories
	// -----------------------------------------------------------------------

	/**
	 * Creates a SELECT command for the table.
	 *
	 * @param string $where WHERE clause (without the keyword); defaults to '1=1'.
	 * @param array $parameters name-value pairs to bind.
	 * @param array $ordering column → direction pairs.
	 * @param int $limit maximum rows; negative means no limit.
	 * @param int $offset rows to skip; negative means no offset.
	 * @param string $select columns to select; '*' means all columns.
	 * @return IDataCommand the SELECT command.
	 */
	public function createFindCommand($where = '1=1', $parameters = [], $ordering = [], $limit = -1, $offset = -1, $select = '*');

	/**
	 * Creates a COUNT(*) command for the table.
	 *
	 * @param string $where WHERE clause; defaults to '1=1'.
	 * @param array $parameters name-value pairs to bind.
	 * @param array $ordering column → direction pairs.
	 * @param int $limit maximum rows; negative means no limit.
	 * @param int $offset rows to skip; negative means no offset.
	 * @return IDataCommand the COUNT command.
	 */
	public function createCountCommand($where = '1=1', $parameters = [], $ordering = [], $limit = -1, $offset = -1);

	/**
	 * Creates an INSERT command for the table.
	 *
	 * @param array $data column-name → value pairs to insert.
	 * @return IDataCommand the INSERT command.
	 */
	public function createInsertCommand($data);

	/**
	 * Creates an INSERT OR IGNORE command for the table.
	 *
	 * The base implementation throws {@see TDbException}; driver-specific
	 * subclasses that support this operation must override this method.
	 *
	 * @param array $data column-name → value pairs to insert.
	 * @return IDataCommand the INSERT OR IGNORE command.
	 */
	public function createInsertOrIgnoreCommand(array $data): IDataCommand;

	/**
	 * Creates an UPSERT (INSERT … ON CONFLICT … UPDATE) command for the table.
	 *
	 * The base implementation throws {@see TDbException}; driver-specific
	 * subclasses that support this operation must override this method.
	 *
	 * @param array $data column-name → value pairs to insert.
	 * @param null|array $updateData column → value pairs to use on conflict; null
	 *   means all non-primary-key columns from $data.
	 * @param null|array $conflictColumns columns that define the conflict target;
	 *   null means the table's primary key columns.
	 * @return IDataCommand the UPSERT command.
	 */
	public function createUpsertCommand(array $data, ?array $updateData = null, ?array $conflictColumns = null): IDataCommand;

	/**
	 * Creates an UPDATE command for the table.
	 *
	 * @param array $data column-name → value pairs to set.
	 * @param string $where WHERE clause identifying rows to update.
	 * @param array $parameters additional name-value pairs to bind for the WHERE clause.
	 * @return IDataCommand the UPDATE command.
	 */
	public function createUpdateCommand($data, $where, $parameters = []);

	/**
	 * Creates a DELETE command for the table.
	 *
	 * @param string $where WHERE clause identifying rows to delete.
	 * @param array $parameters name-value pairs to bind.
	 * @return IDataCommand the DELETE command.
	 */
	public function createDeleteCommand($where, $parameters = []);

	// -----------------------------------------------------------------------
	// Utilities
	// -----------------------------------------------------------------------

	/**
	 * Creates a raw SQL command on the underlying connection.
	 *
	 * @param string $sql the SQL statement.
	 * @return IDataCommand the new command.
	 */
	public function createCommand($sql);

	/**
	 * Binds column-name → value pairs to a command, using each column's PDO type.
	 *
	 * @param IDataCommand $command the command to bind into.
	 * @param array $values column-name → value pairs.
	 */
	public function bindColumnValues($command, $values);

	/**
	 * Binds an array of values (positional or named) to a command.
	 *
	 * @param IDataCommand $command the command to bind into.
	 * @param array $values positional values or name → value pairs.
	 */
	public function bindArrayValues($command, $values);
}
