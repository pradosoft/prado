<?php

/**
 * TFirebirdCommandBuilder class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common\Firebird;

use Prado\Data\Common\TDbCommandBuilder;

/**
 * TFirebirdCommandBuilder provides Firebird-specific LIMIT/OFFSET and last-insert-ID support.
 *
 * Firebird uses `SELECT FIRST n SKIP m FROM ...` syntax (injected after SELECT)
 * rather than a trailing LIMIT/OFFSET clause.
 *
 * Last-insert-ID for identity columns (Firebird 3+) is retrieved via
 * `SELECT RDB$GET_CONTEXT('SYSTEM', 'LAST_INSERT_ID') FROM RDB$DATABASE`.
 * For Firebird 2.x with generator-based sequences, the sequence must be queried
 * directly; this builder only supports the Firebird 3+ IDENTITY approach.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TFirebirdCommandBuilder extends TDbCommandBuilder
{
	/**
	 * Overrides parent implementation. Retrieves last identity value (Firebird 3+).
	 * @return null|int last inserted identity value, null if no identity column.
	 */
	public function getLastInsertID()
	{
		foreach ($this->getTableInfo()->getColumns() as $column) {
			if ($column->hasSequence()) {
				$command = $this->getDbConnection()->createCommand(
					"SELECT RDB\$GET_CONTEXT('SYSTEM', 'LAST_INSERT_ID') FROM RDB\$DATABASE"
				);
				$value = $command->queryScalar();
				return $value !== false && $value !== null ? (int) $value : null;
			}
		}
		return null;
	}

	/**
	 * Overrides parent implementation. Applies Firebird `SELECT FIRST n SKIP m` syntax.
	 *
	 * Firebird does not support trailing LIMIT/OFFSET. Instead, FIRST and SKIP
	 * clauses are inserted immediately after the SELECT keyword:
	 *   `SELECT FIRST 10 SKIP 20 * FROM my_table`
	 *
	 * @param string $sql SQL query string.
	 * @param int $limit maximum number of rows, -1 to ignore.
	 * @param int $offset row offset, -1 to ignore.
	 * @return string SQL with FIRST/SKIP applied.
	 */
	public function applyLimitOffset($sql, $limit = -1, $offset = -1)
	{
		$limit = $limit !== null ? (int) $limit : -1;
		$offset = $offset !== null ? (int) $offset : -1;

		if ($limit < 0 && $offset < 0) {
			return $sql;
		}

		$firstClause = $limit >= 0 ? ' FIRST ' . $limit : '';
		$skipClause = $offset >= 0 ? ' SKIP ' . $offset : '';

		// Insert FIRST/SKIP after SELECT (handles SELECT DISTINCT too)
		return preg_replace('/^(\s*SELECT(\s+DISTINCT)?)/i', '$1' . $firstClause . $skipClause, $sql, 1);
	}
}
