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
 * TSqliteCommandBuilder class
 *
 * TSqliteCommandBuilder provides SQLite-specific methods to create query
 * commands, including LIMIT/OFFSET, ORDER BY, INSERT OR IGNORE, and UPSERT.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @author Brad Anderson <belisoful@icloud.com> insertOrIgnore, upsert
 * @since 3.1
 */
class TSqliteCommandBuilder extends TDbCommandBuilder
{
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
	 * On conflict with $conflictColumns (defaults to primary keys), updates
	 * $updateData columns (defaults to all non-PK columns), referencing the
	 * excluded pseudo-table for new values.
	 *
	 * The $updateData parameter supports four modes:
	 * - **null** — all non-conflict columns from $data use `excluded.col` (new values from the INSERT row).
	 * - **[] empty array** — DO NOTHING on conflict (insert-or-ignore behaviour).
	 * - **integer-keyed list** (e.g. `['score', 'email']`) — those columns use `excluded.col`.
	 * - **string-keyed explicit map** (e.g. `['score' => 99]`) — those columns use a bound literal value (`:_upsert_col`).
	 * - **mixed** (e.g. `['score', 'username' => 'alice_renamed']`) — integer-keyed use `excluded.col`; string-keyed use bound literals.
	 *
	 * @param array $data name-value pairs of data to insert.
	 * @param null|array $updateData null, column-name list, explicit col=>value map, or mixed; controls what is updated on conflict.
	 * @param null|array $conflictColumns conflict target columns; null = primary key columns.
	 * @return TDbCommand upsert command.
	 * @since 4.3.3
	 */
	public function createUpsertCommand(array $data, ?array $updateData = null, ?array $conflictColumns = null): TDbCommand
	{
		$conflictColumns = $this->resolveConflictColumns($conflictColumns);

		$table = $this->getTableInfo()->getTableFullName();
		[$fields, $bindings] = $this->getInsertFieldBindings($data);

		$conflictParts = [];
		foreach ($conflictColumns as $pk) {
			$conflictParts[] = $this->getTableInfo()->getColumn($pk)->getColumnName();
		}
		$conflictClause = '(' . implode(', ', $conflictParts) . ')';

		$sql = "INSERT INTO {$table}({$fields}) VALUES ({$bindings}) ON CONFLICT{$conflictClause}";

		$updateParts = [];
		$explicitBindings = [];

		if ($updateData === null) {
			// Mode: null → all non-conflict columns from $data via excluded pseudo-table
			foreach (array_keys($data) as $name) {
				if (!in_array($name, $conflictColumns, true)) {
					$quoted = $this->getTableInfo()->getColumn($name)->getColumnName();
					$updateParts[] = $quoted . ' = excluded.' . $quoted;
				}
			}
		} else {
			// Process each entry in $updateData
			foreach ($updateData as $key => $value) {
				if (is_int($key)) {
					// Integer-keyed: column name → excluded pseudo-table reference
					$quoted = $this->getTableInfo()->getColumn($value)->getColumnName();
					$updateParts[] = $quoted . ' = excluded.' . $quoted;
				} else {
					// String-keyed: explicit literal override → bound param :_upsert_<col>
					$quoted = $this->getTableInfo()->getColumn($key)->getColumnName();
					$paramName = ':_upsert_' . $key;
					$updateParts[] = $quoted . ' = ' . $paramName;
					$explicitBindings[$paramName] = $value;
				}
			}
		}

		if (!empty($updateParts)) {
			$sql .= ' DO UPDATE SET ' . implode(', ', $updateParts);
		} else {
			$sql .= ' DO NOTHING';
		}

		$command = $this->createCommand($sql);
		$this->bindColumnValues($command, $data);
		foreach ($explicitBindings as $paramName => $value) {
			$command->bindValue($paramName, $value);
		}
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
