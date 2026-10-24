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
	 * Overrides parent implementation. The insert statement of a table with an
	 * identity column ends in `RETURNING <column>`, so the generated value comes
	 * back with the statement and {@see getLastInsertID()} returns it.
	 * @param array $data name-value pairs of new data to be inserted.
	 * @return TDbCommand insert command
	 * @since 4.4.0
	 */
	public function createInsertCommand($data)
	{
		$this->_insertCommand = null;
		$this->_lastInsertID = null;
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
