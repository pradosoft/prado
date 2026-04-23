<?php

/**
 * TPgsqlCommandBuilder class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common\Pgsql;

use Prado\Data\Common\TDbCommandBuilder;
use Prado\Data\TDbCommand;

/**
 * TPgsqlCommandBuilder provides specifics methods to create limit/offset query commands
 * for Pgsql database.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @since 3.1
 */
class TPgsqlCommandBuilder extends TDbCommandBuilder
{
	/**
	 * Creates a PostgreSQL INSERT ... ON CONFLICT DO NOTHING command.
	 * Silently skips the insert when a unique/PK constraint is violated.
	 * @param array $data name-value pairs of data to be inserted.
	 * @return TDbCommand insert-or-ignore command.
	 * @since 4.3.3
	 */
	public function createInsertOrIgnoreCommand(array $data): TDbCommand
	{
		$table = $this->getTableInfo()->getTableFullName();
		[$fields, $bindings] = $this->getInsertFieldBindings($data);
		$command = $this->createCommand("INSERT INTO {$table}({$fields}) VALUES ({$bindings}) ON CONFLICT DO NOTHING");
		$this->bindColumnValues($command, $data);
		return $command;
	}

	/**
	 * Creates a PostgreSQL INSERT ... ON CONFLICT (pk,...) DO UPDATE SET command.
	 * On conflict with $conflictColumns (defaults to primary keys), updates $updateData columns
	 * (defaults to all non-PK columns), referencing the EXCLUDED pseudo-table for new values.
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

		// Build ON CONFLICT (pk1, pk2, ...) clause
		$conflictParts = [];
		foreach ($conflictColumns as $pk) {
			$conflictParts[] = $this->getTableInfo()->getColumn($pk)->getColumnName();
		}
		$conflictClause = '(' . implode(', ', $conflictParts) . ')';

		$sql = "INSERT INTO {$table}({$fields}) VALUES ({$bindings}) ON CONFLICT {$conflictClause}";

		if (!empty($updateData)) {
			$updateParts = [];
			foreach (array_keys($updateData) as $name) {
				$quoted = $this->getTableInfo()->getColumn($name)->getColumnName();
				$updateParts[] = $quoted . ' = EXCLUDED.' . $quoted;
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
	 * Overrides parent implementation. Only column of type text or character (and its variants)
	 * accepts the LIKE criteria.
	 * @param array $fields list of column id for potential search condition.
	 * @param string $keywords string of keywords
	 * @return string SQL search condition matching on a set of columns.
	 */
	public function getSearchExpression($fields, $keywords)
	{
		$columns = [];
		foreach ($fields as $field) {
			if ($this->isSearchableColumn($this->getTableInfo()->getColumn($field))) {
				$columns[] = $field;
			}
		}
		return parent::getSearchExpression($columns, $keywords);
	}
	/**
	 *
	 * @param mixed $column
	 * @return bool true if column can be used for LIKE searching.
	 */
	protected function isSearchableColumn($column)
	{
		$type = strtolower($column->getDbType());
		return $type === 'character varying' || $type === 'varchar' ||
				$type === 'character' || $type === 'char' || $type === 'text';
	}

	/**
	 * Overrides parent implementation to use PostgreSQL's ILIKE instead of LIKE (case-sensitive).
	 * @param string $column column name.
	 * @param array $words keywords
	 * @return string search condition for all words in one column.
	 */
	protected function getSearchCondition($column, $words)
	{
		$conditions = [];
		foreach ($words as $word) {
			$conditions[] = $column . ' ILIKE ' . $this->getDbConnection()->quoteString('%' . $word . '%');
		}
		return '(' . implode(' AND ', $conditions) . ')';
	}
}
