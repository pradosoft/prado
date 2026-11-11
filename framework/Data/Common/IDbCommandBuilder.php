<?php

/**
 * IDbCommandBuilder interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common;

use Prado\Data\IDataCommand;

/**
 * IDbCommandBuilder interface
 *
 * Discovery marker for command builders that compose SQL text.  The
 * driver-agnostic CRUD factory surface lives on the parent
 * {@see IDataCommandBuilder}; this sub-interface adds SQL-string composition
 * helpers (LIMIT/OFFSET, ORDER BY, search expressions, SELECT field lists,
 * combined criteria application) that non-SQL drivers have no analog for.
 *
 * Implemented by {@see TDbCommandBuilder}.  Mirrors the
 * {@see \Prado\Data\IDataConnection} / {@see \Prado\Data\IDbConnection}
 * discovery-marker pattern.
 *
 * Optional SQL/PDO discovery marker — see {@see \Prado\Data\IDataConnection} for the full driver-portability roadmap.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
interface IDbCommandBuilder extends IDataCommandBuilder
{
	/**
	 * Appends LIMIT and OFFSET clauses to a SQL string in the driver-specific
	 * dialect.
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
	 * Builds a SQL search expression (WHERE clause fragment) for the given
	 * fields and space-separated keywords.
	 *
	 * @param array $fields column IDs to search.
	 * @param string $keywords space-separated search terms.
	 * @return string a SQL condition string (may be empty if no terms or fields given).
	 */
	public function getSearchExpression($fields, $keywords);

	/**
	 * Returns the SELECT-list column expressions for this builder's table.
	 *
	 * @param mixed $data '*' for all columns, null for the table's default column
	 *   list, a comma-separated column-name string, or an associative data array
	 *   whose keys are column names.
	 * @return string[] fully-quoted column expressions suitable for SELECT.
	 */
	public function getSelectFieldList($data = '*');

	/**
	 * Applies WHERE parameters, ORDER BY, and LIMIT/OFFSET to a SQL string and
	 * returns a ready-to-execute command.
	 *
	 * @param string $sql the base SQL string.
	 * @param array $parameters name-value pairs to bind.
	 * @param array $ordering column → direction pairs.
	 * @param int $limit maximum rows; negative means no limit.
	 * @param int $offset rows to skip; negative means no offset.
	 * @return IDataCommand the command ready for execution.
	 */
	public function applyCriterias($sql, $parameters = [], $ordering = [], $limit = -1, $offset = -1);

	/**
	 * Builds the SQL condition that matches the index column values of a table:
	 * an `IN` list for one column, an `OR` of `AND`-joined equalities for several,
	 * and a false predicate for no values.  {@see \Prado\Data\DataGateway\TDataGatewayCommand::getIndexKeyCondition()}
	 * has called this on the gateway's builder.  The portable equivalent for a
	 * non-SQL driver is {@see IDataCriteriaBuilder::createIndexCriteria()}.
	 *
	 * @param array $fields index column ids.
	 * @param array $values index values, one value or one value array per row.
	 * @param ?IDataTableInfo $table the table of the columns; null for this builder's table.
	 * @return string the SQL condition.
	 * @since 4.4.0
	 */
	public function getIndexCondition(array $fields, array $values, ?IDataTableInfo $table = null): string;
}
