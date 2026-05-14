<?php

/**
 * TOracleCommandBuilder class file.
 *
 * @author Marcos Nobre <marconobre[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common\Oracle;

use Prado\Data\Common\TDbCommandBuilder;
use Prado\Data\TDbCommand;

/**
 * TOracleCommandBuilder provides specifics methods to create limit/offset query commands
 * for Oracle database.
 *
 * @author Marcos Nobre <marconobre[at]gmail[dot]com>
 * @author Brad Anderson <belisoful@icloud.com> insertOrIgnore, upsert
 * @since 3.1
 */
class TOracleCommandBuilder extends TDbCommandBuilder
{
	/**
	 * Creates an Oracle MERGE ... WHEN NOT MATCHED THEN INSERT command (insertOrIgnore).
	 * Requires an active transaction; throws TDbException otherwise.
	 * Uses Oracle MERGE with USING (SELECT ... FROM DUAL) and no AS keyword for aliases.
	 * @param array $data name-value pairs of data to be inserted.
	 * @return TDbCommand insert-or-ignore MERGE command.
	 * @since 4.3.3
	 */
	public function createInsertOrIgnoreCommand(array $data): TDbCommand
	{
		$this->assertActiveTransaction();
		$conflictColumns = $this->resolveConflictColumns(null);
		return $this->buildMergeStatement($data, [], $conflictColumns, 'FROM DUAL', false);
	}

	/**
	 * Creates an Oracle MERGE ... WHEN MATCHED THEN UPDATE WHEN NOT MATCHED THEN INSERT command.
	 * Requires an active transaction; throws TDbException otherwise.
	 * Uses Oracle MERGE with USING (SELECT ... FROM DUAL) and no AS keyword for aliases.
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
	 * @return TDbCommand upsert MERGE command.
	 * @since 4.3.3
	 */
	public function createUpsertCommand(array $data, ?array $updateData = null, ?array $conflictColumns = null): TDbCommand
	{
		$this->assertActiveTransaction();
		$conflictColumns = $this->resolveConflictColumns($conflictColumns);
		return $this->buildMergeStatement($data, $updateData, $conflictColumns, 'FROM DUAL', false);
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
		return $type === 'character varying' || $type === 'varchar2' || $type === 'character' || $type === 'char' || $type === 'text';
	}

	/**
	 * Overrides parent implementation to use PostgreSQL's ILIKE instead of LIKE (case-sensitive).
	 * @param string $column column name.
	 * @param array $words keywords
	 * @param mixed $sql
	 * @param mixed $limit
	 * @param mixed $offset
	 * @return string search condition for all words in one column.
	 */
	/*
	*
	*	how Oracle don't implements ILIKE, this method won't be overrided
	*
	protected function getSearchCondition($column, $words)
	{
		$conditions=array();
		foreach($words as $word)
			$conditions[] = $column.' LIKE '.$this->getDbConnection()->quoteString('%'.$word.'%');
		return '('.implode(' AND ', $conditions).')';
	}
	*/

	/**
	 * Overrides parent implementation to use Oracle way of get paginated RecordSet instead of using LIMIT sql clause.
	 * @param string $sql SQL query string.
	 * @param int $limit maximum number of rows, -1 to ignore limit.
	 * @param int $offset row offset, -1 to ignore offset.
	 * @return string SQL with limit and offset in Oracle way.
	 */
	public function applyLimitOffset($sql, $limit = -1, $offset = -1)
	{
		if ((int) $limit <= 0 && (int) $offset <= 0) {
			return $sql;
		}

		// When called from SqlMap (e.g. queryForList with skip/max), no specific
		// table is known so getTableInfo() returns an empty stub without a table
		// name or columns.  In that case, use Oracle 12c+ OFFSET/FETCH NEXT
		// syntax which handles arbitrary SQL without column or table metadata.
		$tableInfo = $this->getTableInfo();
		$tableName = $tableInfo !== null ? $tableInfo->getTableName() : null;
		if ($tableInfo === null || $tableName === null || $tableName === '') {
			$result = rtrim($sql);
			$offset = (int) $offset;
			$limit = (int) $limit;
			if ($offset > 0) {
				$result .= ' OFFSET ' . $offset . ' ROWS';
			}
			if ($limit > 0) {
				$result .= ' FETCH NEXT ' . $limit . ' ROWS ONLY';
			}
			return $result;
		}

		$pradoNUMLIN = 'pradoNUMLIN';
		$fieldsALIAS = 'xyz';

		$nfimDaSQL = strlen($sql);
		$nfimDoWhere = (stripos($sql, 'ORDER') !== false ? stripos($sql, 'ORDER') : $nfimDaSQL);
		$selectPos = stripos($sql, 'SELECT');
		$niniDoSelect = ($selectPos !== false ? $selectPos : 0) + 6;
		$nfimDoSelect = (stripos($sql, 'FROM') !== false ? stripos($sql, 'FROM') : $nfimDaSQL);

		$WhereInSubSelect = "";
		$wherePos = stripos($sql, 'WHERE');
		if ($wherePos !== false) {
			$whereStart = $wherePos + 5;
			$WhereInSubSelect = "WHERE " . substr($sql, $whereStart, $nfimDoWhere - $whereStart);
		}

		$sORDERBY = '';
		if (stripos($sql, 'ORDER') !== false) {
			$p = stripos($sql, 'ORDER');
			$sORDERBY = substr($sql, $p + 8);
		}

		$fields = substr($sql, 0, $nfimDoSelect);
		$fields = trim(substr($fields, $niniDoSelect));
		$aliasedFields = ', ';

		if (trim($fields) == '*') {
			$aliasedFields = ", {$fieldsALIAS}.{$fields}";
			$fields = '';
			$arr = $this->getTableInfo()->getColumns();
			foreach ($arr as $field) {
				$fields .= strtolower($field->getColumnName()) . ', ';
			}
			$fields = str_replace('"', '', $fields);
			$fields = trim($fields);
			$fields = substr($fields, 0, strlen($fields) - 1);
		} else {
			if (strpos($fields, ',') !== false) {
				$arr = $this->getTableInfo()->getColumns();
				foreach ($arr as $field) {
					$field = strtolower($field);
					$existAS = str_ireplace(' as ', '-as-', $field);
					if (strpos($existAS, '-as-') === false) {
						$aliasedFields .= "{$fieldsALIAS}." . trim($field) . ", ";
					} else {
						$aliasedFields .= "{$field}, ";
					}
				}
				$aliasedFields = trim($aliasedFields);
				$aliasedFields = substr($aliasedFields, 0, strlen($aliasedFields) - 1);
			}
		}
		if ($aliasedFields == ', ') {
			$aliasedFields = " , $fieldsALIAS.* ";
		}

		/* ************************
		$newSql = " SELECT $fields FROM ".
				  "(					".
				  "		SELECT rownum as {$pradoNUMLIN} {$aliasedFields} FROM ".
				  " ($sql) {$fieldsALIAS} WHERE rownum <= {$limit} ".
				  ") WHERE {$pradoNUMLIN} >= {$offset} ";

		************************* */
		$offset = (int) $offset;
		$toReg = $offset + $limit;
		$fullTableName = $this->getTableInfo()->getTableFullName();
		if (empty($sORDERBY)) {
			$sORDERBY = "ROWNUM";
		}

		$newSql = " SELECT $fields FROM " .
					"(					" .
					"		SELECT ROW_NUMBER() OVER ( ORDER BY {$sORDERBY} ) -1 as {$pradoNUMLIN} {$aliasedFields} " .
					"		FROM {$fullTableName} {$fieldsALIAS} $WhereInSubSelect" .
					") nn					" .
					" WHERE nn.{$pradoNUMLIN} >= {$offset} AND nn.{$pradoNUMLIN} < {$toReg} ";
		//echo $newSql."\n<br>\n";
		return $newSql;
	}
}
