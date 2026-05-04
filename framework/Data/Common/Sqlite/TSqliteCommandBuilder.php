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
 * @since 3.1
 */
class TSqliteCommandBuilder extends TDbCommandBuilder
{
	/*
	 * Applies ORDER BY to a SQL string using bare (unquoted) column identifiers.
	 *
	 * Bare identifiers are used rather than the quoted names returned by
	 * {@see \Prado\Data\Common\TDbTableColumn::getColumnName()} because SQLite
	 * handles unquoted identifiers reliably across all known builds; this avoids
	 * any risk of quoted-identifier edge cases on non-standard builds.
	 *
	 * Note: {@see createFindCommand()} delegates ordering to this method via
	 * {@see TDbCommandBuilder::applyCriterias()}.
	 *
	 * @param string $sql SQL string without existing ordering.
	 * @param array $ordering pairs of column names as key and direction as value.
	 * @return string modified SQL applied with ORDER BY.
	 * @since 4.3.3
	 *
	public function applyOrdering($sql, $ordering)
	{
		$orders = [];
		foreach ($ordering as $name => $direction) {
			$direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';
			if (false !== strpos($name, '(') && false !== strpos($name, ')')) {
				$key = $name;
			} else {
				// Use the bare (unquoted) column id.
				$key = $this->getTableInfo()->getColumn($name)->getColumnId();
			}
			$orders[] = $key . ' ' . $direction;
		}
		if (count($orders) > 0) {
			$sql .= ' ORDER BY ' . implode(', ', $orders);
		}
		return $sql;
	}*/

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
	 * @param array $data name-value pairs of data to insert.
	 * @param null|array $updateData column=>value pairs to update on conflict;
	 *   null = all non-PK columns from $data.
	 * @param null|array $conflictColumns conflict target columns;
	 *   null = primary key columns.
	 * @return TDbCommand upsert command.
	 * @since 4.3.3
	 */
	public function createUpsertCommand(array $data, ?array $updateData = null, ?array $conflictColumns = null): TDbCommand
	{
		$conflictColumns = $this->resolveConflictColumns($conflictColumns);
		$updateData = $this->resolveUpdateData($data, $updateData, $conflictColumns);

		$table = $this->getTableInfo()->getTableFullName();
		[$fields, $bindings] = $this->getInsertFieldBindings($data);

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
