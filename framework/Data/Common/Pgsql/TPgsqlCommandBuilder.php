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
 * @author Brad Anderson <belisoful@icloud.com> insertOrIgnore, upsert
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
	 *
	 * The $updateData parameter supports four modes:
	 * - **null** — all non-conflict columns from $data use `EXCLUDED.col` (new values from the INSERT row).
	 * - **[] empty array** — DO NOTHING on conflict (insert-or-ignore behaviour).
	 * - **integer-keyed list** (e.g. `['score', 'email']`) — those columns use `EXCLUDED.col`.
	 * - **string-keyed explicit map** (e.g. `['score' => 99]`) — those columns use a bound literal value (`:_upsert_col`).
	 * - **mixed** (e.g. `['score', 'username' => 'alice_renamed']`) — integer-keyed use `EXCLUDED.col`; string-keyed use bound literals.
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

		// Build ON CONFLICT (pk1, pk2, ...) clause
		$conflictParts = [];
		foreach ($conflictColumns as $pk) {
			$conflictParts[] = $this->getTableInfo()->getColumn($pk)->getColumnName();
		}
		$conflictClause = '(' . implode(', ', $conflictParts) . ')';

		$sql = "INSERT INTO {$table}({$fields}) VALUES ({$bindings}) ON CONFLICT {$conflictClause}";

		$updateParts = [];
		$explicitBindings = [];

		if ($updateData === null) {
			// Mode: null → all non-conflict columns from $data via EXCLUDED pseudo-table
			foreach (array_keys($data) as $name) {
				if (!in_array($name, $conflictColumns, true)) {
					$quoted = $this->getTableInfo()->getColumn($name)->getColumnName();
					$updateParts[] = $quoted . ' = EXCLUDED.' . $quoted;
				}
			}
		} else {
			// Process each entry in $updateData
			foreach ($updateData as $key => $value) {
				if (is_int($key)) {
					// Integer-keyed: column name → EXCLUDED pseudo-table reference
					$quoted = $this->getTableInfo()->getColumn($value)->getColumnName();
					$updateParts[] = $quoted . ' = EXCLUDED.' . $quoted;
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
