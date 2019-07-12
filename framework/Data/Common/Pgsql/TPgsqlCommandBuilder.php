<?php
/**
 * TPgsqlCommandBuilder class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\Common\Pgsql
 */

namespace Prado\Data\Common\Pgsql;

use Prado\Data\Common\TDbCommandBuilder;
use Prado\Prado;

/**
 * TPgsqlCommandBuilder provides specifics methods to create limit/offset query commands
 * for Pgsql database.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\Common\Pgsql
 * @since 3.1
 */
class TPgsqlCommandBuilder extends TDbCommandBuilder
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
