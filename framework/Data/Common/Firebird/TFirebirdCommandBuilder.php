<?php

/**
 * TFirebirdCommandBuilder class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common\Firebird;

use PDOStatement;
use Prado\Data\IDataCommand;
use Prado\Data\TDbCommand;
use Prado\Data\Common\TDbCommandBuilder;

/**
 * TFirebirdCommandBuilder provides Firebird-specific LIMIT/OFFSET and last-insert-ID support.
 *
 * Firebird uses `SELECT FIRST n SKIP m FROM ...` syntax (injected after SELECT)
 * rather than a trailing LIMIT/OFFSET clause.
 *
 * Firebird has no last-insert-id function. A generated identity value (Firebird 3+)
 * is available only through `INSERT ... RETURNING <column>`: {@see createInsertCommand()}
 * appends the clause for the table's identity column, and {@see getLastInsertID()}
 * reads the value from that executed statement. A Firebird 2.x generator is not
 * reported; the generator must be queried directly.
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
	 * @return IDataCommand insert-or-ignore MERGE command.
	 */
	public function createInsertOrIgnoreCommand(array $data): IDataCommand
	{
		$this->assertActiveTransaction();
		$this->clearInsertIdentity();
		$conflictColumns = $this->resolveConflictColumns(null);
		return $this->buildMergeStatement($data, [], $conflictColumns, 'FROM RDB$DATABASE', false);
	}

	/**
	 * Creates a Firebird MERGE ... WHEN MATCHED THEN UPDATE WHEN NOT MATCHED THEN INSERT command.
	 * Requires an active transaction; throws TDbException otherwise.
	 * Uses Firebird MERGE with USING (SELECT ... FROM RDB$DATABASE) and no AS keyword for aliases.
	 *
	 * The $updateData parameter supports four modes:
	 * - **null** — all non-conflict columns updated via the MERGE source alias (s.col).
	 * - **[] empty array** — no WHEN MATCHED branch (insert-or-ignore semantics).
	 * - **integer-keyed list** (e.g. `['score']`) — those columns use the source alias (s.col).
	 * - **string-keyed explicit map** (e.g. `['score' => 99]`) — those columns use a bound literal (`:_upsert_col`).
	 * - **mixed** — integer-keyed use s.col; string-keyed use bound literals.
	 *
	 * @param array $data name-value pairs of data to insert.
	 * @param null|array $updateData null, column-name list, explicit col=>value map, or mixed; controls what is updated on conflict.
	 * @param null|array $conflictColumns conflict target columns; null = primary key columns.
	 * @return IDataCommand upsert MERGE command.
	 */
	public function createUpsertCommand(array $data, ?array $updateData = null, ?array $conflictColumns = null): IDataCommand
	{
		$this->assertActiveTransaction();
		$this->clearInsertIdentity();
		$conflictColumns = $this->resolveConflictColumns($conflictColumns);
		return $this->buildMergeStatement($data, $updateData, $conflictColumns, 'FROM RDB$DATABASE', false);
	}

	/**
	 * Children override this if there is something specific about the column Name.
	 * @param string $columnName The name of the column to place in the sql.
	 * @return string null if no change, or a string if there is a change.
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
	 * @var ?TDbCommand the last insert command with a `RETURNING` clause.
	 */
	private $_insertCommand;
	/**
	 * @var ?int the identity value read from the last insert command.
	 */
	private $_lastInsertID;

	/**
	 * @return ?TFirebirdTableColumn the first identity column of the table, or null when there is none.
	 * @since 4.4.0
	 */
	protected function getIdentityColumn()
	{
		foreach ($this->getTableInfo()->getColumns() as $column) {
			if ($column->hasSequence()) {
				return $column;
			}
		}
		return null;
	}

	/**
	 * Clears the `RETURNING` insert command and its identity value so that
	 * {@see getLastInsertID()} never reports the identity of an earlier insert
	 * after a new command has been created. The MERGE-based insertOrIgnore and
	 * upsert commands carry no `RETURNING` row, so their last insert id is null.
	 * @since 4.4.0
	 */
	protected function clearInsertIdentity(): void
	{
		$this->_insertCommand = null;
		$this->_lastInsertID = null;
	}

	/**
	 * Overrides parent implementation. The insert statement of a table with an
	 * identity column ends in `RETURNING <column>`, so the generated value comes
	 * back with the statement and {@see getLastInsertID()} returns it.
	 * @param array $data name-value pairs of new data to be inserted.
	 * @return TDbCommand insert command
	 * @since 4.4.0
	 */
	public function createInsertCommand($data)
	{
		$this->clearInsertIdentity();
		$table = $this->getTableInfo()->getTableFullName();
		[$fields, $bindings] = $this->getInsertFieldBindings($data);
		$sql = "INSERT INTO {$table}({$fields}) VALUES ($bindings)";
		$identity = $this->getIdentityColumn();
		if ($identity !== null) {
			$sql .= ' RETURNING ' . $identity->getColumnName();
		}
		$command = $this->createCommand($sql);
		$this->bindColumnValues($command, $data);
		if ($identity !== null) {
			$this->_insertCommand = $command;
		}
		return $command;
	}

	/**
	 * Overrides parent implementation. Returns the identity value of the last insert
	 * command this builder created, read once from the `RETURNING` row of its
	 * executed statement.
	 * @return null|int last inserted identity value; null without an identity column
	 * or before an insert command of this builder has run.
	 */
	public function getLastInsertID()
	{
		if ($this->_lastInsertID === null && $this->_insertCommand !== null) {
			$statement = $this->_insertCommand->getPdoStatement();
			if ($statement instanceof PDOStatement) {
				$value = $statement->fetchColumn();
				$statement->closeCursor();
				if ($value !== false && $value !== null) {
					$this->_lastInsertID = (int) $value;
				}
			}
		}
		return $this->_lastInsertID;
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
