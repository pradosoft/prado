<?php

/**
 * TDbCommandBuilder class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common;

use PDO;
use Traversable;
use Prado\Data\TDbCommand;
use Prado\Exceptions\TDbException;

/**
 * TDbCommandBuilder provides basic methods to create query commands for tables
 * given by {@see setTableInfo TableInfo}.
 *
 * This builder creates database-specific SQL commands for CRUD operations:
 * - {@see createFindCommand()}: SELECT queries
 * - {@see createInsertCommand()}: INSERT statements
 * - {@see createUpdateCommand()}: UPDATE statements
 * - {@see createDeleteCommand()}: DELETE statements
 * - {@see createInsertOrIgnoreCommand()}: INSERT OR IGNORE (since 4.3.3)
 * - {@see createUpsertCommand()}: INSERT...ON CONFLICT UPDATE (since 4.3.3)
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @since 3.1
 */
class TDbCommandBuilder extends \Prado\TComponent
{
	private $_connection;
	private $_tableInfo;

	/**
	 * @param null|\Prado\Data\TDbConnection $connection database connection.
	 * @param null|TDbTableInfo $tableInfo table information.
	 */
	public function __construct($connection = null, $tableInfo = null)
	{
		$this->setDbConnection($connection);
		$this->setTableInfo($tableInfo);
		parent::__construct();
	}

	/**
	 * @return \Prado\Data\TDbConnection database connection.
	 */
	public function getDbConnection()
	{
		return $this->_connection;
	}

	/**
	 * @param \Prado\Data\TDbConnection $value database connection.
	 */
	public function setDbConnection($value)
	{
		$this->_connection = $value;
	}

	/**
	 * @param TDbTableInfo $value table information.
	 */
	public function setTableInfo($value)
	{
		$this->_tableInfo = $value;
	}

	/**
	 * @return TDbTableInfo table information.
	 */
	public function getTableInfo()
	{
		return $this->_tableInfo;
	}

	/**
	 * Iterate through all the columns and returns the last insert id of the
	 * first column that has a sequence or serial.
	 * @return mixed last insert id, null if none is found.
	 */
	public function getLastInsertID()
	{
		foreach ($this->getTableInfo()->getColumns() as $column) {
			if ($column->hasSequence()) {
				return $this->getDbConnection()->getLastInsertID($column->getSequenceName());
			}
		}
	}

	/**
	 * Alters the sql to apply $limit and $offset. Default implementation is applicable
	 * for PostgreSQL, MySQL and SQLite.
	 * @param string $sql SQL query string.
	 * @param int $limit maximum number of rows, -1 to ignore limit.
	 * @param int $offset row offset, -1 to ignore offset.
	 * @return string SQL with limit and offset.
	 */
	public function applyLimitOffset($sql, $limit = -1, $offset = -1)
	{
		$limit = $limit !== null ? (int) $limit : -1;
		$offset = $offset !== null ? (int) $offset : -1;
		$limitStr = $limit >= 0 ? ' LIMIT ' . $limit : '';
		$offsetStr = $offset >= 0 ? ' OFFSET ' . $offset : '';
		return $sql . $limitStr . $offsetStr;
	}

	/**
	 * @param string $sql SQL string without existing ordering.
	 * @param array $ordering pairs of column names as key and direction as value.
	 * @return string modified SQL applied with ORDER BY.
	 */
	public function applyOrdering($sql, $ordering)
	{
		$orders = [];
		foreach ($ordering as $name => $direction) {
			$direction = strtolower($direction) == 'desc' ? 'DESC' : 'ASC';
			if (false !== strpos($name, '(') && false !== strpos($name, ')')) {
				// key is a function (bad practice, but we need to handle it)
				$key = $name;
			} else {
				// key is a column
				$key = $this->getTableInfo()->getColumn($name)->getColumnName();
			}
			$orders[] = $key . ' ' . $direction;
		}
		if (count($orders) > 0) {
			$sql .= ' ORDER BY ' . implode(', ', $orders);
		}
		return $sql;
	}

	/**
	 * Computes the SQL condition for search a set of column using regular expression
	 * (or LIKE, depending on database implementation) to match a string of
	 * keywords (default matches all keywords).
	 * @param array $fields list of column id for potential search condition.
	 * @param string $keywords string of keywords
	 * @return string SQL search condition matching on a set of columns.
	 */
	public function getSearchExpression($fields, $keywords)
	{
		if (strlen(trim($keywords)) == 0) {
			return '';
		}
		$words = preg_split('/\s/u', $keywords);
		$conditions = [];
		foreach ($fields as $field) {
			$column = $this->getTableInfo()->getColumn($field)->getColumnName();
			$conditions[] = $this->getSearchCondition($column, $words);
		}
		return '(' . implode(' OR ', $conditions) . ')';
	}

	/**
	 * @param string $column column name.
	 * @param array $words keywords
	 * @return string search condition for all words in one column.
	 */
	protected function getSearchCondition($column, $words)
	{
		$conditions = [];
		foreach ($words as $word) {
			$conditions[] = $column . ' LIKE ' . $this->getDbConnection()->quoteString('%' . $word . '%');
		}
		return '(' . implode(' AND ', $conditions) . ')';
	}

	/**
	 *
	 * Different behavior depends on type of passed data
	 * string
	 * 	usage without modification
	 *
	 * null
	 * 	will be expanded to full list of quoted table column names (quoting depends on database)
	 *
	 * array
	 * - Column names will be quoted if used as key or value of array
	 * ```php
	 * array('col1', 'col2', 'col2')
	 * // SELECT `col1`, `col2`, `col3` FROM...
	 * ```
	 *
	 * - Column aliasing
	 * ```php
	 * array('mycol1' => 'col1', 'mycol2' => 'COUNT(*)')
	 * // SELECT `col1` AS mycol1, COUNT(*) AS mycol2 FROM...
	 * ```
	 *
	 * - NULL and scalar values (strings will be quoted depending on database)
	 * ```php
	 * array('col1' => 'my custom string', 'col2' => 1.0, 'col3' => 'NULL')
	 * // SELECT "my custom string" AS `col1`, 1.0 AS `col2`, NULL AS `col3` FROM...
	 * ```
	 *
	 * - If the *-wildcard char is used as key or value, add the full list of quoted table column names
	 * ```php
	 * array('col1' => 'NULL', '*')
	 * // SELECT `col1`, `col2`, `col3`, NULL AS `col1` FROM...
	 * ```
	 * @param mixed $data
	 * @return array of generated fields - use implode(', ', $selectfieldlist) to collapse field list for usage
	 * @since 3.1.7
	 * @todo add support for table aliasing
	 * @todo add support for quoting of column aliasing
	 */
	public function getSelectFieldList($data = '*')
	{
		if (is_scalar($data)) {
			$tmp = explode(',', $data);
			$result = [];
			foreach ($tmp as $v) {
				$result[] = trim($v);
			}
			return $result;
		}

		$bHasWildcard = false;
		$result = [];
		if (is_array($data) || $data instanceof Traversable) {
			$columns = $this->getTableInfo()->getColumns();
			foreach ($data as $key => $value) {
				if ($key === '*' || $value === '*') {
					$bHasWildcard = true;
					continue;
				}

				if (strToUpper($key) === 'NULL') {
					$result[] = 'NULL';
					continue;
				}

				if (strpos($key, '(') !== false && strpos($key, ')') !== false) {
					$result[] = $key;
					continue;
				}

				if (preg_match('/\bAS\b/i', $key)) {
					$result[] = $key;
					continue;
				}

				if (preg_match('/\bAS\b/i', $value)) {
					$result[] = $value;
					continue;
				}

				$v = isset($columns[$value]);
				$k = isset($columns[$key]);
				if (is_int($key) && $v) {
					$key = $value;
					$k = $v;
				}

				if (strToUpper($value) === 'NULL') {
					if ($k) {
						$result[] = 'NULL AS ' . $columns[$key]->getColumnName();
					} else {
						$result[] = 'NULL' . (is_string($key) ? (' AS ' . (string) $key) : '');
					}
					continue;
				}

				if (strpos($value, '(') !== false && strpos($value, ')') !== false) {
					if ($k) {
						$result[] = $value . ' AS ' . $columns[$key]->getColumnName();
					} else {
						$result[] = $value . (is_string($key) ? (' AS ' . (string) $key) : '');
					}
					continue;
				}

				if ($v && $key == $value) {
					$result[] = $columns[$value]->getColumnName();
					continue;
				}

				if ($k && $value == null) {
					$result[] = $columns[$key]->getColumnName();
					continue;
				}

				if (is_string($key) && $v) {
					$result[] = $columns[$value]->getColumnName() . ' AS ' . $key;
					continue;
				}

				if (is_numeric($value) && $k) {
					$result[] = $value . ' AS ' . $columns[$key]->getColumnName();
					continue;
				}

				if (is_string($value) && $k) {
					$result[] = $this->getDbConnection()->quoteString($value) . ' AS ' . $columns[$key]->getColumnName();
					continue;
				}

				if (!$v && !$k && is_int($key)) {
					$result[] = is_numeric($value) ? $value : $this->getDbConnection()->quoteString((string) $value);
					continue;
				}

				$result[] = (is_numeric($value) ? $value : $this->getDbConnection()->quoteString((string) $value)) . ' AS ' . $key;
			}
		}

		if ($data === null || count($result) == 0 || $bHasWildcard) {
			$result = $result = array_merge($this->getTableInfo()->getColumnNames(), $result);
		}

		return $result;
	}

	/**
	 * Creates a SELECT command for the table.
	 * The table name is obtained from the {@see setTableInfo TableInfo} property.
	 * @param string $where query condition.
	 * @param array $parameters condition parameters.
	 * @param array $ordering ORDER BY clause.
	 * @param int $limit maximum rows.
	 * @param int $offset row offset.
	 * @param string $select columns to select.
	 * @return TDbCommand query command.
	 */
	public function createFindCommand($where = '1=1', $parameters = [], $ordering = [], $limit = -1, $offset = -1, $select = '*')
	{
		$table = $this->getTableInfo()->getTableFullName();
		$fields = implode(', ', $this -> getSelectFieldList($select));
		$sql = "SELECT {$fields} FROM {$table}";
		if (!empty($where)) {
			$sql .= " WHERE {$where}";
		}
		return $this->applyCriterias($sql, $parameters, $ordering, $limit, $offset);
	}

	/**
	 * Applies ordering, limit, and offset to the SQL and binds parameters.
	 * @param string $sql SQL query.
	 * @param array $parameters binding parameters.
	 * @param array $ordering ORDER BY clause.
	 * @param int $limit maximum rows.
	 * @param int $offset row offset.
	 * @return TDbCommand command with criteria applied.
	 */
	public function applyCriterias($sql, $parameters = [], $ordering = [], $limit = -1, $offset = -1)
	{
		if (count($ordering) > 0) {
			$sql = $this->applyOrdering($sql, $ordering);
		}
		if ($limit >= 0 || $offset >= 0) {
			$sql = $this->applyLimitOffset($sql, $limit, $offset);
		}
		$command = $this->createCommand($sql);
		$this->bindArrayValues($command, $parameters);
		return $command;
	}

	/**
	 * Creates a COUNT(*) command for the table.
	 * @param string $where count condition.
	 * @param array $parameters binding parameters.
	 * @param array $ordering ORDER BY clause.
	 * @param int $limit maximum rows.
	 * @param int $offset row offset.
	 * @return TDbCommand count command.
	 */
	public function createCountCommand($where = '1=1', $parameters = [], $ordering = [], $limit = -1, $offset = -1)
	{
		return $this->createFindCommand($where, $parameters, $ordering, $limit, $offset, 'COUNT(*)');
	}

	/**
	 * Creates a delete command for the table described in {@see setTableInfo TableInfo}.
	 * The conditions for delete is given by the $where argument and the parameters
	 * for the condition is given by $parameters.
	 * @param string $where delete condition.
	 * @param array $parameters delete parameters.
	 * @return TDbCommand delete command.
	 */
	public function createDeleteCommand($where, $parameters = [])
	{
		$table = $this->getTableInfo()->getTableFullName();
		if (!empty($where)) {
			$where = ' WHERE ' . $where;
		}
		$command = $this->createCommand("DELETE FROM {$table}" . $where);
		$this->bindArrayValues($command, $parameters);
		return $command;
	}

	/**
	 * Creates an insert command for the table described in {@see setTableInfo TableInfo} for the given data.
	 * Each array key in the $data array must correspond to the column name of the table
	 * (if a column allows to be null, it may be omitted) to be inserted with
	 * the corresponding array value.
	 * @param array $data name-value pairs of new data to be inserted.
	 * @return TDbCommand insert command
	 */
	public function createInsertCommand($data)
	{
		$table = $this->getTableInfo()->getTableFullName();
		[$fields, $bindings] = $this->getInsertFieldBindings($data);
		$command = $this->createCommand("INSERT INTO {$table}({$fields}) VALUES ($bindings)");
		$this->bindColumnValues($command, $data);
		return $command;
	}

	/**
	 * Creates an INSERT OR IGNORE command for the table.
	 * Base implementation always throws TDbException; driver-specific subclasses must override.
	 * @param array $data name-value pairs of data to be inserted.
	 * @throws TDbException always, in the base implementation.
	 * @return TDbCommand insert-or-ignore command.
	 * @since 4.3.3
	 */
	public function createInsertOrIgnoreCommand(array $data): TDbCommand
	{
		throw new TDbException('dbcommandbuilder_insertorignore_not_supported');
	}

	/**
	 * Creates an UPSERT (insert-or-update) command for the table.
	 * Base implementation always throws TDbException; driver-specific subclasses must override.
	 * @param array $data name-value pairs of data to insert.
	 * @param null|array $updateData column=>value pairs to update on conflict; null = all non-PK columns from $data.
	 * @param null|array $conflictColumns conflict target columns; null = primary key columns.
	 * @throws TDbException always, in the base implementation.
	 * @return TDbCommand upsert command.
	 * @since 4.3.3
	 */
	public function createUpsertCommand(array $data, ?array $updateData = null, ?array $conflictColumns = null): TDbCommand
	{
		throw new TDbException('dbcommandbuilder_upsert_not_supported');
	}

	/**
	 * Resolves the conflict columns, defaulting to the table's primary keys.
	 * @param null|array $conflictColumns explicit conflict columns, or null to use primary keys.
	 * @return array resolved conflict column names.
	 * @since 4.3.3
	 */
	protected function resolveConflictColumns(?array $conflictColumns): array
	{
		return $conflictColumns ?? $this->getTableInfo()->getPrimaryKeys();
	}

	/**
	 * Resolves the update data for upsert, defaulting to all non-PK columns from $data.
	 * @param array $data full insert data.
	 * @param null|array $updateData explicit update data, or null to use all non-PK columns.
	 * @param array $conflictColumns the resolved conflict columns (primary keys).
	 * @return array resolved update data.
	 * @since 4.3.3
	 */
	protected function resolveUpdateData(array $data, ?array $updateData, array $conflictColumns): array
	{
		return $updateData ?? array_diff_key($data, array_flip($conflictColumns));
	}

	/**
	 * Checks that an active transaction exists on the current connection.
	 * Called by MERGE-based drivers (MSSQL, Oracle, DB2, Firebird) before building MERGE statements.
	 * @throws TDbException if no active transaction is found.
	 * @since 4.3.3
	 */
	protected function requiresActiveTransaction(): void
	{
		if ($this->getDbConnection()->getCurrentTransaction() === null) {
			throw new TDbException('dbcommandbuilder_upsert_requires_transaction', $this::class);
		}
	}

	/**
	 * Builds a MERGE INTO statement for MERGE-based drivers (MSSQL, Oracle, DB2, Firebird).
	 *
	 * The USING SELECT uses raw column names (from array_keys($data)) as aliases.
	 * Table column references use getColumnName() (quoted) from the table metadata.
	 * When $updateData is empty, the WHEN MATCHED branch is omitted (insertOrIgnore behaviour).
	 *
	 * @param array $data full row data (all columns).
	 * @param array $updateData columns to update on match (empty = insertOrIgnore, no UPDATE branch).
	 * @param array $conflictColumns primary/conflict key column names.
	 * @param string $dualSource dual/dummy table source, e.g. 'FROM DUAL', 'FROM SYSIBM.SYSDUMMY1', '' for MSSQL.
	 * @param bool $useAsAlias true to emit 'AS t'/'AS s'; false to emit bare 't'/'s' (Oracle, Firebird).
	 * @return TDbCommand prepared MERGE command with bound parameters.
	 * @since 4.3.3
	 */
	protected function buildMergeStatement(array $data, array $updateData, array $conflictColumns, string $dualSource, bool $useAsAlias): TDbCommand
	{
		$table = $this->getTableInfo()->getTableFullName();
		$tableAlias = $useAsAlias ? 'AS t' : 't';
		$sourceAlias = $useAsAlias ? 'AS s' : 's';

		// Build USING SELECT: SELECT :col1 AS col1, :col2 AS col2, ... [FROM dual]
		$usingParts = [];
		foreach (array_keys($data) as $name) {
			$usingParts[] = ':' . $name . ' AS ' . $name;
		}
		$usingSelect = 'SELECT ' . implode(', ', $usingParts);
		if ($dualSource !== '') {
			$usingSelect .= ' ' . $dualSource;
		}

		// Build ON clause: t.{pk_quoted} = s.pk AND ...
		$onParts = [];
		foreach ($conflictColumns as $pk) {
			$quoted = $this->getTableInfo()->getColumn($pk)->getColumnName();
			$onParts[] = 't.' . $quoted . ' = s.' . $pk;
		}
		$onClause = implode(' AND ', $onParts);

		// Build MERGE statement
		$sql = "MERGE INTO {$table} {$tableAlias} USING ({$usingSelect}) {$sourceAlias} ON ({$onClause})";

		// WHEN MATCHED branch (omit for insertOrIgnore when $updateData is empty)
		if (!empty($updateData)) {
			$updateParts = [];
			foreach (array_keys($updateData) as $name) {
				$quoted = $this->getTableInfo()->getColumn($name)->getColumnName();
				$updateParts[] = 't.' . $quoted . ' = s.' . $name;
			}
			$sql .= ' WHEN MATCHED THEN UPDATE SET ' . implode(', ', $updateParts);
		}

		// WHEN NOT MATCHED branch
		$insertCols = [];
		$insertVals = [];
		foreach (array_keys($data) as $name) {
			$insertCols[] = $this->getTableInfo()->getColumn($name)->getColumnName();
			$insertVals[] = 's.' . $name;
		}
		$sql .= ' WHEN NOT MATCHED THEN INSERT (' . implode(', ', $insertCols) . ') VALUES (' . implode(', ', $insertVals) . ')';

		$command = $this->createCommand($sql);
		$this->bindColumnValues($command, $data);
		return $command;
	}

	/**
	 * Creates an update command for the table described in {@see setTableInfo TableInfo} for the given data.
	 * Each array key in the $data array must correspond to the column name to be updated with the corresponding array value.
	 * @param array $data name-value pairs of data to be updated.
	 * @param string $where update condition.
	 * @param array $parameters update parameters.
	 * @return TDbCommand update command.
	 */
	public function createUpdateCommand($data, $where, $parameters = [])
	{
		$table = $this->getTableInfo()->getTableFullName();
		if ($this->hasIntegerKey($parameters)) {
			$fields = implode(', ', $this->getColumnBindings($data, true));
		} else {
			$fields = implode(', ', $this->getColumnBindings($data));
		}

		if (!empty($where)) {
			$where = ' WHERE ' . $where;
		}
		$command = $this->createCommand("UPDATE {$table} SET {$fields}" . $where);
		$this->bindArrayValues($command, array_merge($data, $parameters));
		return $command;
	}

	/**
	 * Returns a list of insert field name and a list of binding names.
	 * @param object $values array or object to be inserted.
	 * @return array tuple ($fields, $bindings)
	 */
	protected function getInsertFieldBindings($values)
	{
		$fields = [];
		$bindings = [];
		foreach (array_keys($values) as $name) {
			$fields[] = $this->getTableInfo()->getColumn($name)->getColumnName();
			$bindings[] = ':' . $name;
		}
		return [implode(', ', $fields), implode(', ', $bindings)];
	}

	/**
	 * Create a name-value or position-value if $position=true binding strings.
	 * @param array $values data for binding.
	 * @param bool $position true to bind as position values.
	 * @return string update column names with corresponding binding substrings.
	 */
	protected function getColumnBindings($values, $position = false)
	{
		$bindings = [];
		foreach (array_keys($values) as $name) {
			$column = $this->getTableInfo()->getColumn($name)->getColumnName();
			$bindings[] = $position ? $column . ' = ?' : $column . ' = :' . $name;
		}
		return $bindings;
	}

	/**
	 * @param string $sql SQL query string.
	 * @return TDbCommand corresponding database command.
	 */
	public function createCommand($sql)
	{
		$this->getDbConnection()->setActive(true);
		return $this->getDbConnection()->createCommand($sql);
	}

	/**
	 * Bind the name-value pairs of $values where the array keys correspond to column names.
	 * @param TDbCommand $command database command.
	 * @param array $values name-value pairs.
	 */
	public function bindColumnValues($command, $values)
	{
		foreach ($values as $name => $value) {
			$column = $this->getTableInfo()->getColumn($name);
			if ($value === null && $column->getAllowNull()) {
				$command->bindValue(':' . $name, null, PDO::PARAM_NULL);
			} else {
				$command->bindValue(':' . $name, $value, $column->getPdoType());
			}
		}
	}

	/**
	 * @param TDbCommand $command database command
	 * @param array $values values for binding.
	 */
	public function bindArrayValues($command, $values)
	{
		if ($this->hasIntegerKey($values)) {
			$values = array_values($values);
			for ($i = 0, $max = count($values); $i < $max; $i++) {
				$command->bindValue($i + 1, $values[$i], $this->getPdoType($values[$i]));
			}
		} else {
			foreach ($values as $name => $value) {
				$prop = $name[0] === ':' ? $name : ':' . $name;
				$command->bindValue($prop, $value, $this->getPdoType($value));
			}
		}
	}

	/**
	 * @param mixed $value PHP value
	 * @return null|int PDO parameter types.
	 */
	public static function getPdoType($value)
	{
		switch (gettype($value)) {
			case 'boolean': return PDO::PARAM_BOOL;
			case 'integer': return PDO::PARAM_INT;
			case 'string': return PDO::PARAM_STR;
			case 'NULL': return PDO::PARAM_NULL;
		}
		return null;
	}

	/**
	 * @param array $array
	 * @return bool true if any array key is an integer.
	 */
	protected function hasIntegerKey($array)
	{
		foreach ($array as $k => $v) {
			if (gettype($k) === 'integer') {
				return true;
			}
		}
		return false;
	}
}
