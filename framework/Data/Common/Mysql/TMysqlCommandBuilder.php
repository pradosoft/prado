<?php

/**
 * TMysqlCommandBuilder class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common\Mysql;

use Prado\Data\Common\TDbCommandBuilder;
use Prado\Data\TDbCommand;

/**
 * TMysqlCommandBuilder implements TDbCommandBuilder with MySQL-specific syntax.
 *
 * Adds support for MySQL-specific insertOrIgnore (INSERT IGNORE) and
 * upsert (INSERT ... ON DUPLICATE KEY UPDATE) statements.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @author Brad Anderson <belisoful@icloud.com> insertOrIgnore, upsert
 * @since 3.1
 */
class TMysqlCommandBuilder extends TDbCommandBuilder
{
	/**
	 * Creates a MySQL INSERT IGNORE command.
	 * Silently skips the insert when a duplicate key constraint is violated.
	 * @param array $data name-value pairs of data to be inserted.
	 * @return TDbCommand insert-or-ignore command.
	 * @since 4.3.3
	 */
	public function createInsertOrIgnoreCommand(array $data): TDbCommand
	{
		$table = $this->getTableInfo()->getTableFullName();
		[$fields, $bindings] = $this->getInsertFieldBindings($data);
		$command = $this->createCommand("INSERT IGNORE INTO {$table}({$fields}) VALUES ({$bindings})");
		$this->bindColumnValues($command, $data);
		return $command;
	}

	/**
	 * Creates a MySQL INSERT ... ON DUPLICATE KEY UPDATE command.
	 * On duplicate key conflict, updates the specified columns using MySQL's ON DUPLICATE KEY UPDATE syntax.
	 *
	 * The $updateData parameter controls what is updated on conflict and supports four modes:
	 * - **`null`** — all non-conflict columns from `$data` use `VALUES(col)` (takes values from the attempted INSERT row).
	 * - **`[]` empty array** — no update; falls back to INSERT IGNORE (conflict is silently discarded).
	 * - **integer-keyed column-name list** (e.g. `['score', 'email']`) — those columns use `VALUES(col)` to pull the value from the INSERT row.
	 * - **string-keyed explicit map** (e.g. `['score' => 99]`) — those columns use a bound literal value (`:_upsert_score`), NOT the INSERT row value.
	 * - **mixed** (e.g. `['score', 'username' => 'alice_renamed']`) — integer-keyed entries use `VALUES(col)`; string-keyed entries use bound literals.
	 *
	 * @param array $data name-value pairs of data to insert.
	 * @param null|array $updateData null, column-name list, explicit col=>value map, or mixed; controls what is updated on conflict.
	 * @param null|array $conflictColumns conflict target columns excluded from the update clause; null = primary key columns.
	 * @return TDbCommand upsert command.
	 * @since 4.3.3
	 */
	public function createUpsertCommand(array $data, ?array $updateData = null, ?array $conflictColumns = null): TDbCommand
	{
		$conflictColumns = $this->resolveConflictColumns($conflictColumns);

		$table = $this->getTableInfo()->getTableFullName();
		[$fields, $bindings] = $this->getInsertFieldBindings($data);

		$updateParts = [];
		$explicitBindings = [];

		if ($updateData === null) {
			// Mode: null → all non-conflict columns from $data via VALUES(col)
			foreach (array_keys($data) as $name) {
				if (!in_array($name, $conflictColumns, true)) {
					$quoted = $this->getTableInfo()->getColumn($name)->getColumnName();
					$updateParts[] = $quoted . '=VALUES(' . $quoted . ')';
				}
			}
		} else {
			// Process each entry in $updateData
			foreach ($updateData as $key => $value) {
				if (is_int($key)) {
					// Integer-keyed: column name → VALUES(col) from INSERT row
					$quoted = $this->getTableInfo()->getColumn($value)->getColumnName();
					$updateParts[] = $quoted . '=VALUES(' . $quoted . ')';
				} else {
					// String-keyed: explicit literal override → bound param :_upsert_<col>
					$quoted = $this->getTableInfo()->getColumn($key)->getColumnName();
					$paramName = ':_upsert_' . $key;
					$updateParts[] = $quoted . '=' . $paramName;
					$explicitBindings[$paramName] = $value;
				}
			}
		}

		if (!empty($updateParts)) {
			$sql = "INSERT INTO {$table}({$fields}) VALUES ({$bindings}) ON DUPLICATE KEY UPDATE " . implode(', ', $updateParts);
		} else {
			$sql = "INSERT IGNORE INTO {$table}({$fields}) VALUES ({$bindings})";
		}

		$command = $this->createCommand($sql);
		$this->bindColumnValues($command, $data);
		foreach ($explicitBindings as $paramName => $value) {
			$command->bindValue($paramName, $value);
		}
		return $command;
	}
}
