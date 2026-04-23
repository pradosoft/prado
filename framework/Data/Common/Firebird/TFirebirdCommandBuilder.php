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
use Prado\Data\TDbCommand;

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
	 * Creates a Firebird MERGE ... WHEN NOT MATCHED THEN INSERT command (insertOrIgnore).
	 * Requires an active transaction; throws TDbException otherwise.
	 * Uses Firebird MERGE with USING (SELECT ... FROM RDB$DATABASE) and no AS keyword for aliases.
	 * @param array $data name-value pairs of data to be inserted.
	 * @return TDbCommand insert-or-ignore MERGE command.
	 */
	public function createInsertOrIgnoreCommand(array $data): TDbCommand
	{
		$this->requiresActiveTransaction();
		$conflictColumns = $this->resolveConflictColumns(null);
		return $this->buildMergeStatement($data, [], $conflictColumns, 'FROM RDB$DATABASE', false);
	}

	/**
	 * Creates a Firebird MERGE ... WHEN MATCHED THEN UPDATE WHEN NOT MATCHED THEN INSERT command.
	 * Requires an active transaction; throws TDbException otherwise.
	 * Uses Firebird MERGE with USING (SELECT ... FROM RDB$DATABASE) and no AS keyword for aliases.
	 * @param array $data name-value pairs of data to insert.
	 * @param null|array $updateData column=>value pairs to update on conflict; null = all non-PK columns from $data.
	 * @param null|array $conflictColumns conflict target columns; null = primary key columns.
	 * @return TDbCommand upsert MERGE command.
	 */
	public function createUpsertCommand(array $data, ?array $updateData = null, ?array $conflictColumns = null): TDbCommand
	{
		$this->requiresActiveTransaction();
		$conflictColumns = $this->resolveConflictColumns($conflictColumns);
		$updateData = $this->resolveUpdateData($data, $updateData, $conflictColumns);
		return $this->buildMergeStatement($data, $updateData, $conflictColumns, 'FROM RDB$DATABASE', false);
	}

	/**
	 * Children override this if there is something specific about the column Name.
	 * @param string $columnName The name of the column to place in the sql.
	 * @return string null if no change, or a string if there is a change.
	 * @since 4.3.3
	 */
	protected function processMergeColumn(string $columnName): string
	{
		$castType = $this->getFirebirdCastType($columnName);
		return 'CAST(:' . $columnName . ' AS ' . $castType . ') AS ' . $columnName;
	}

	/**
	 * Builds a Firebird-compatible CAST type string for the named column.
	 *
	 * Firebird requires explicit type annotations in CAST() expressions. This helper
	 * maps the column's DbType (and ColumnSize / NumericPrecision / NumericScale where
	 * applicable) to the correct SQL type string.
	 *
	 * @param string $name logical column name (PHP array key from $data).
	 * @return string SQL type string suitable for use in CAST(:name AS <type>).
	 * @since 4.3.3
	 */
	private function getFirebirdCastType(string $name): string
	{
		$column = $this->getTableInfo()->getColumn($name);
		if ($column === null) {
			return 'VARCHAR(255)';
		}

		$dbType = strtoupper(trim($column->getDbType()));
		$size = (int) $column->getColumnSize();
		$prec = (int) $column->getNumericPrecision();
		$scale = (int) $column->getNumericScale();

		if (in_array($dbType, ['VARCHAR', 'CHAR'], true) && $size > 0) {
			return $dbType . '(' . $size . ')';
		}
		if (in_array($dbType, ['DECIMAL', 'NUMERIC'], true) && $prec > 0) {
			return $dbType . '(' . $prec . ($scale > 0 ? ',' . $scale : '') . ')';
		}
		// Fixed-length types and all others: return as-is.
		return $dbType;
	}

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
