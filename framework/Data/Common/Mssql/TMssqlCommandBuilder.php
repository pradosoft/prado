<?php
/**
 * TMsssqlCommandBuilder class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\Common\Mssql
 */

namespace Prado\Data\Common\Mssql;

use Prado\Data\Common\TDbCommandBuilder;
use Prado\Prado;

/**
 * TMssqlCommandBuilder provides specifics methods to create limit/offset query commands
 * for MSSQL servers.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\Common\Mssql
 * @since 3.1
 */
class TMssqlCommandBuilder extends TDbCommandBuilder
{
	/**
	 * Overrides parent implementation. Uses "SELECT @@Identity".
	 * @return null|int last insert id, null if none is found.
	 */
	public function getLastInsertID()
	{
		foreach ($this->getTableInfo()->getColumns() as $column) {
			if ($column->hasSequence()) {
				$command = $this->getDbConnection()->createCommand('SELECT @@Identity');
				return (int) ($command->queryScalar());
			}
		}
		return null;
	}

	/**
	 * Overrides parent implementation. Alters the sql to apply $limit and $offset.
	 * The idea for limit with offset is done by modifying the sql on the fly
	 * with numerous assumptions on the structure of the sql string.
	 * The modification is done with reference to the notes from
	 * http://troels.arvin.dk/db/rdbms/#select-limit-offset
	 *
	 * <code>
	 * SELECT * FROM (
	 *  SELECT TOP n * FROM (
	 *    SELECT TOP z columns      -- (z=n+skip)
	 *    FROM tablename
	 *    ORDER BY key ASC
	 *  ) AS FOO ORDER BY key DESC -- ('FOO' may be anything)
	 * ) AS BAR ORDER BY key ASC    -- ('BAR' may be anything)
	 * </code>
	 *
	 * <b>Regular expressions are used to alter the SQL query. The resulting SQL query
	 * may be malformed for complex queries.</b> The following restrictions apply
	 *
	 * <ul>
	 *   <li>
	 * In particular, <b>commas</b> should <b>NOT</b>
	 * be used as part of the ordering expression or identifier. Commas must only be
	 * used for separating the ordering clauses.
	 *  </li>
	 *  <li>
	 * In the ORDER BY clause, the column name should NOT be be qualified
	 * with a table name or view name. Alias the column names or use column index.
	 * </li>
	 * <li>
	 * No clauses should follow the ORDER BY clause, e.g. no COMPUTE or FOR clauses.
	 * </li>
	 * </ul>
	 *
	 * @param string $sql SQL query string.
	 * @param int $limit maximum number of rows, -1 to ignore limit.
	 * @param int $offset row offset, -1 to ignore offset.
	 * @return string SQL with limit and offset.
	 */
	public function applyLimitOffset($sql, $limit = -1, $offset = -1)
	{
		$limit = $limit !== null ? (int) $limit : -1;
		$offset = $offset !== null ? (int) $offset : -1;
		if ($limit > 0 && $offset <= 0) { //just limit
			$sql = preg_replace('/^([\s(])*SELECT( DISTINCT)?(?!\s*TOP\s*\()/i', "\\1SELECT\\2 TOP $limit", $sql);
		} elseif ($limit > 0 && $offset > 0) {
			$sql = $this->rewriteLimitOffsetSql($sql, $limit, $offset);
		}
		return $sql;
	}

	/**
	 * Rewrite sql to apply $limit > and $offset > 0 for MSSQL database.
	 * See http://troels.arvin.dk/db/rdbms/#select-limit-offset
	 * @param string $sql sql query
	 * @param int $limit > 0
	 * @param int $offset > 0
	 * @return string sql modified sql query applied with limit and offset.
	 */
	protected function rewriteLimitOffsetSql($sql, $limit, $offset)
	{
		$fetch = $limit + $offset;
		$sql = preg_replace('/^([\s(])*SELECT( DISTINCT)?(?!\s*TOP\s*\()/i', "\\1SELECT\\2 TOP $fetch", $sql);
		$ordering = $this->findOrdering($sql);

		$orginalOrdering = $this->joinOrdering($ordering);
		$reverseOrdering = $this->joinOrdering($this->reverseDirection($ordering));
		$sql = "SELECT * FROM (SELECT TOP {$limit} * FROM ($sql) as [__inner top table__] {$reverseOrdering}) as [__outer top table__] {$orginalOrdering}";
		return $sql;
	}

	/**
	 * Base on simplified syntax http://msdn2.microsoft.com/en-us/library/aa259187(SQL.80).aspx
	 *
	 * @param string $sql $sql
	 * @return array ordering expression as key and ordering direction as value
	 */
	protected function findOrdering($sql)
	{
		if (!preg_match('/ORDER BY/i', $sql)) {
			return [];
		}
		$matches = [];
		$ordering = [];
		preg_match_all('/(ORDER BY)[\s"\[](.*)(ASC|DESC)?(?:[\s"\[]|$|COMPUTE|FOR)/i', $sql, $matches);
		if (count($matches) > 1 && count($matches[2]) > 0) {
			$parts = explode(',', $matches[2][0]);
			foreach ($parts as $part) {
				$subs = [];
				if (preg_match_all('/(.*)[\s"\]](ASC|DESC)$/i', trim($part), $subs)) {
					if (count($subs) > 1 && count($subs[2]) > 0) {
						$ordering[$subs[1][0]] = $subs[2][0];
					}
					//else what?
				} else {
					$ordering[trim($part)] = 'ASC';
				}
			}
		}
		return $ordering;
	}

	/**
	 * @param array $orders ordering obtained from findOrdering()
	 * @return string concat the orderings
	 */
	protected function joinOrdering($orders)
	{
		if (count($orders) > 0) {
			$str = [];
			foreach ($orders as $column => $direction) {
				$str[] = $column . ' ' . $direction;
			}
			return 'ORDER BY ' . implode(', ', $str);
		}
		return '';
	}

	/**
	 * @param array $orders original ordering
	 * @return array ordering with reversed direction.
	 */
	protected function reverseDirection($orders)
	{
		foreach ($orders as $column => $direction) {
			$orders[$column] = strtolower(trim($direction)) === 'desc' ? 'ASC' : 'DESC';
		}
		return $orders;
	}
}
