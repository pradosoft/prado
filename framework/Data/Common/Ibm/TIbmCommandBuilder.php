<?php

/**
 * TIbmCommandBuilder class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common\Ibm;

use Prado\Data\Common\TDbCommandBuilder;
use Prado\Data\TDbCommand;

/**
 * TIbmCommandBuilder provides DB2-specific LIMIT/OFFSET and last-insert-ID support.
 *
 * DB2 does not support the standard `LIMIT n OFFSET m` syntax. Instead it uses:
 * - `FETCH FIRST n ROWS ONLY` (limit without offset)
 * - `OFFSET m ROWS FETCH FIRST n ROWS ONLY` (DB2 11.1+ LUW)
 * - Subquery with `ROW_NUMBER()` for older servers
 *
 * Identity column last-insert-ID is retrieved via `SELECT IDENTITY_VAL_LOCAL() FROM SYSIBM.SYSDUMMY1`.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TIbmCommandBuilder extends TDbCommandBuilder
{
	/**
	 * Creates a DB2 MERGE ... WHEN NOT MATCHED THEN INSERT command (insertOrIgnore).
	 * Requires an active transaction; throws TDbException otherwise.
	 * Uses DB2 MERGE with USING (SELECT ... FROM SYSIBM.SYSDUMMY1) AS s syntax.
	 * @param array $data name-value pairs of data to be inserted.
	 * @return TDbCommand insert-or-ignore MERGE command.
	 */
	public function createInsertOrIgnoreCommand(array $data): TDbCommand
	{
		$this->requiresActiveTransaction();
		$conflictColumns = $this->resolveConflictColumns(null);
		return $this->buildMergeStatement($data, [], $conflictColumns, 'FROM SYSIBM.SYSDUMMY1', true);
	}

	/**
	 * Creates a DB2 MERGE ... WHEN MATCHED THEN UPDATE WHEN NOT MATCHED THEN INSERT command.
	 * Requires an active transaction; throws TDbException otherwise.
	 * Uses DB2 MERGE with USING (SELECT ... FROM SYSIBM.SYSDUMMY1) AS s syntax.
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
		return $this->buildMergeStatement($data, $updateData, $conflictColumns, 'FROM SYSIBM.SYSDUMMY1', true);
	}

	/**
	 * Overrides parent implementation. Retrieves last identity value via DB2 function.
	 * @return null|int last inserted identity value, null if no identity column.
	 */
	public function getLastInsertID()
	{
		foreach ($this->getTableInfo()->getColumns() as $column) {
			if ($column->hasSequence()) {
				$command = $this->getDbConnection()->createCommand(
					'SELECT IDENTITY_VAL_LOCAL() FROM SYSIBM.SYSDUMMY1'
				);
				$value = $command->queryScalar();
				return $value !== false ? (int) $value : null;
			}
		}
		return null;
	}

	/**
	 * Overrides parent implementation to apply DB2 row-limiting syntax.
	 *
	 * Uses `FETCH FIRST n ROWS ONLY` for limit-only queries, and
	 * `OFFSET m ROWS FETCH FIRST n ROWS ONLY` for limit+offset (requires DB2 11.1+ LUW).
	 * For older DB2 versions that do not support the OFFSET clause, a
	 * ROW_NUMBER() subquery is used as a fallback.
	 *
	 * @param string $sql SQL query string.
	 * @param int $limit maximum number of rows, -1 to ignore.
	 * @param int $offset row offset, -1 to ignore.
	 * @return string SQL with limit and offset applied.
	 */
	public function applyLimitOffset($sql, $limit = -1, $offset = -1)
	{
		$limit = $limit !== null ? (int) $limit : -1;
		$offset = $offset !== null ? (int) $offset : -1;

		if ($limit < 0 && $offset < 0) {
			return $sql;
		}
		if ($limit >= 0 && $offset <= 0) {
			return $sql . ' FETCH FIRST ' . $limit . ' ROWS ONLY';
		}
		// limit + offset: use ROW_NUMBER() subquery for broad DB2 compatibility
		return $this->rewriteLimitOffsetSql($sql, $limit, $offset);
	}

	/**
	 * Rewrites the SQL using a ROW_NUMBER() window function subquery for offset+limit.
	 * Compatible with DB2 LUW 9.x and later.
	 *
	 * @param string $sql original SQL
	 * @param int $limit > 0
	 * @param int $offset >= 0
	 * @return string rewritten SQL.
	 */
	protected function rewriteLimitOffsetSql($sql, $limit, $offset)
	{
		$lower = $offset + 1;
		$upper = $offset + $limit;
		return "SELECT * FROM (SELECT prado_inner.*, ROW_NUMBER() OVER() AS prado_rownum "
			. "FROM ({$sql}) AS prado_inner) AS prado_outer "
			. "WHERE prado_rownum BETWEEN {$lower} AND {$upper}";
	}
}
