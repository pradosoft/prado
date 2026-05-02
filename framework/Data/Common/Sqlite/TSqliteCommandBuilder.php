<?php

/**
 * TSqliteCommandBuilder class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common\Sqlite;

use Prado\Data\Common\TDbCommandBuilder;
use Prado\Data\TDbCommand;

/**
 * TSqliteCommandBuilder provides specifics methods to create limit/offset query commands
 * for Sqlite database.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @since 3.1
 */
class TSqliteCommandBuilder extends TDbCommandBuilder
{
	/**
	 * Creates a SELECT command for the table.
	 *
	 * Overrides the base implementation to always expand the wildcard selector
	 * to an explicit column list.  PHP's pdo_sqlite has a known bug
	 * (SQLITE_RANGE, error 25) where {@see SELECT *} combined with
	 * {@see ORDER BY "quoted_column"} causes a column-index out-of-range
	 * error.  Passing {@see null} instead of {@see '*'} to the parent triggers
	 * {@see getSelectFieldList()} to return the explicit column name list,
	 * avoiding the bug entirely.
	 *
	 * @param string $where query condition.
	 * @param array $parameters condition parameters.
	 * @param array $ordering ORDER BY clause.
	 * @param int $limit maximum rows.
	 * @param int $offset row offset.
	 * @param string $select columns to select.
	 * @return \Prado\Data\TDbCommand query command.
	 * @since 4.3.3
	 */
	public function createFindCommand($where = '1=1', $parameters = [], $ordering = [], $limit = -1, $offset = -1, $select = '*')
	{
		if ($select === '*') {
			$select = null;
		}
		return parent::createFindCommand($where, $parameters, $ordering, $limit, $offset, $select);
	}

	/**
	 * Creates a SQLite INSERT OR IGNORE command.
	 * Silently skips the insert when a unique/PK constraint is violated.
	 * @param array $data name-value pairs of data to be inserted.
	 * @return TDbCommand insert-or-ignore command.
	 * @since 4.3.3
	 */
	public function createInsertOrIgnoreCommand(array $data): TDbCommand
	{
		$table = $this->getTableInfo()->getTableFullName();
		[$fields, $bindings] = $this->getInsertFieldBindings($data);
		$command = $this->createCommand("INSERT OR IGNORE INTO {$table}({$fields}) VALUES ({$bindings})");
		$this->bindColumnValues($command, $data);
		return $command;
	}

	/**
	 * Creates a SQLite INSERT ... ON CONFLICT(pk,...) DO UPDATE SET command.
	 * On conflict with $conflictColumns (defaults to primary keys), updates $updateData columns
	 * (defaults to all non-PK columns), referencing the excluded pseudo-table for new values.
	 * @param array $data name-value pairs of data to insert.
	 * @param null|array $updateData column=>value pairs to update on conflict; null = all non-PK columns from $data.
	 * @param null|array $conflictColumns conflict target columns; null = primary key columns.
	 * @return TDbCommand upsert command.
	 * @since 4.3.3
	 */
	public function createUpsertCommand(array $data, ?array $updateData = null, ?array $conflictColumns = null): TDbCommand
	{
		$conflictColumns = $this->resolveConflictColumns($conflictColumns);
		$updateData = $this->resolveUpdateData($data, $updateData, $conflictColumns);

		$table = $this->getTableInfo()->getTableFullName();
		[$fields, $bindings] = $this->getInsertFieldBindings($data);

		// Build ON CONFLICT(pk1, pk2, ...) clause
		$conflictParts = [];
		foreach ($conflictColumns as $pk) {
			$conflictParts[] = $this->getTableInfo()->getColumn($pk)->getColumnName();
		}
		$conflictClause = '(' . implode(', ', $conflictParts) . ')';

		$sql = "INSERT INTO {$table}({$fields}) VALUES ({$bindings}) ON CONFLICT{$conflictClause}";

		if (!empty($updateData)) {
			$updateParts = [];
			foreach (array_keys($updateData) as $name) {
				$quoted = $this->getTableInfo()->getColumn($name)->getColumnName();
				$updateParts[] = $quoted . ' = excluded.' . $quoted;
			}
			$sql .= ' DO UPDATE SET ' . implode(', ', $updateParts);
		} else {
			$sql .= ' DO NOTHING';
		}

		$command = $this->createCommand($sql);
		$this->bindColumnValues($command, $data);
		return $command;
	}

	/**
	 * Alters the sql to apply $limit and $offset.
	 * @param string $sql SQL query string.
	 * @param int $limit maximum number of rows, -1 to ignore limit.
	 * @param int $offset row offset, -1 to ignore offset.
	 * @return string SQL with limit and offset.
	 */
	public function applyLimitOffset($sql, $limit = -1, $offset = -1)
	{
		$limit = $limit !== null ? (int) $limit : -1;
		$offset = $offset !== null ? (int) $offset : -1;
		if ($limit > 0 || $offset > 0) {
			$limitStr = ' LIMIT ' . $limit;
			$offsetStr = $offset >= 0 ? ' OFFSET ' . $offset : '';
			return $sql . $limitStr . $offsetStr;
		} else {
			return $sql;
		}
	}
}
