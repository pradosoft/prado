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
 * {@see IDataCommandBuilder}.  Implemented by {@see TDbCommandBuilder}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
interface IDbCommandBuilder extends IDataCommandBuilder
{
	/**
	 * Appends LIMIT/OFFSET to a SQL string in the driver-specific dialect.
	 * @param string $sql the SQL string.
	 * @param int $limit maximum rows; negative means no limit.
	 * @param int $offset rows to skip; negative means no offset.
	 * @return string the SQL string with LIMIT/OFFSET applied.
	 */
	public function applyLimitOffset($sql, $limit = -1, $offset = -1);

	/**
	 * Appends an ORDER BY clause to a SQL string.
	 * @param string $sql the SQL string.
	 * @param array $ordering column → direction (`'asc'|'desc'`) pairs.
	 * @return string the SQL string with ORDER BY applied.
	 */
	public function applyOrdering($sql, $ordering);

	/**
	 * Builds a SQL WHERE-fragment that searches `$fields` for space-separated keywords.
	 * @param array $fields column IDs to search.
	 * @param string $keywords space-separated search terms.
	 * @return string the SQL condition (empty if no terms or fields).
	 */
	public function getSearchExpression($fields, $keywords);

	/**
	 * Returns the SELECT-list column expressions for this builder's table.
	 * @param mixed $data `'*'`, null (default columns), comma-separated names, or
	 *   an associative array keyed by column name.
	 * @return string[] fully-quoted column expressions.
	 */
	public function getSelectFieldList($data = '*');

	/**
	 * Applies parameter binding + ORDER BY + LIMIT/OFFSET to a SQL string.
	 * @param string $sql the base SQL string.
	 * @param array $parameters name-value pairs to bind.
	 * @param array $ordering column → direction pairs.
	 * @param int $limit maximum rows; negative means no limit.
	 * @param int $offset rows to skip; negative means no offset.
	 * @return IDataCommand the ready-to-execute command.
	 */
	public function applyCriterias($sql, $parameters = [], $ordering = [], $limit = -1, $offset = -1);
}
