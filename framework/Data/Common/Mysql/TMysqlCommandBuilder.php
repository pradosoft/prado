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
	 * On duplicate key conflict, updates the non-PK columns using the VALUES() function
	 * for broad compatibility with MySQL 5.x through 8.x.
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

		$updateParts = [];
		foreach (array_keys($updateData) as $name) {
			$quoted = $this->getTableInfo()->getColumn($name)->getColumnName();
			$updateParts[] = $quoted . '=VALUES(' . $quoted . ')';
		}

		if (!empty($updateParts)) {
			$sql = "INSERT INTO {$table}({$fields}) VALUES ({$bindings}) ON DUPLICATE KEY UPDATE " . implode(', ', $updateParts);
		} else {
			$sql = "INSERT IGNORE INTO {$table}({$fields}) VALUES ({$bindings})";
		}

		$command = $this->createCommand($sql);
		$this->bindColumnValues($command, $data);
		return $command;
	}
}
