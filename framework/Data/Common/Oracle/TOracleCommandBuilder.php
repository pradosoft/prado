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
use Prado\Prado;

/**
 * TOracleCommandBuilder provides specifics methods to create limit/offset query commands
 * for Oracle database.
 *
 * @author Marcos Nobre <marconobre[at]gmail[dot]com>
 * @since 3.1
 */
class TOracleCommandBuilder extends TDbCommandBuilder
{
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

		// When called without a specific table (e.g. a mapped query with skip/max),
		// getTableInfo() returns an empty stub without a table name or columns.
		// In that case, use Oracle 12c+ OFFSET/FETCH NEXT syntax which handles
		// arbitrary SQL without column or table metadata.
		$tableName = $this->getTableInfo()?->getTableName();
		if ($tableName === null || $tableName === '') {
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
		$orderBy = $this->findClause($sql, 'ORDER\s+BY');
		$select = $this->findClause($sql, 'SELECT');
		$from = $this->findClause($sql, 'FROM');
		$where = $this->findClause($sql, 'WHERE');
		$nfimDoWhere = $orderBy !== null ? $orderBy[0] : $nfimDaSQL;
		$niniDoSelect = $select !== null ? $select[0] + $select[1] : 0;
		$nfimDoSelect = $from !== null ? $from[0] : $nfimDaSQL;

		$WhereInSubSelect = "";
		if ($where !== null) {
			$whereStart = $where[0] + $where[1];
			$WhereInSubSelect = "WHERE " . substr($sql, $whereStart, $nfimDoWhere - $whereStart);
		}

		$sORDERBY = '';
		if ($orderBy !== null) {
			$sORDERBY = substr($sql, $orderBy[0] + $orderBy[1]);
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

	/**
	 * Locates the first occurrence of an SQL keyword as a whole word outside quoted
	 * strings and quoted identifiers, so a column such as `sort_order` or a literal
	 * `'from'` does not pass for a clause.
	 * @param string $sql SQL text.
	 * @param string $keyword keyword as a regular expression fragment, such as `ORDER\s+BY`.
	 * @return ?array `[offset, length]` of the keyword in `$sql`, or null when absent.
	 * @since 4.4.0
	 */
	protected function findClause($sql, $keyword)
	{
		$pattern = "/'(?:[^']|'')*'|\"[^\"]*\"|\\b(" . $keyword . ")\\b/i";
		if (!preg_match_all($pattern, $sql, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
			return null;
		}
		foreach ($matches as $match) {
			if (isset($match[1]) && $match[1][1] >= 0) {
				return [$match[1][1], strlen($match[1][0])];
			}
		}
		return null;
	}
}
