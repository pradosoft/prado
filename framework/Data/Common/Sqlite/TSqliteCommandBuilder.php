<?php
/**
 * TSqliteCommandBuilder class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\Common\Sqlite
 */

namespace Prado\Data\Common\Sqlite;

use Prado\Data\Common\TDbCommandBuilder;
use Prado\Prado;

/**
 * TSqliteCommandBuilder provides specifics methods to create limit/offset query commands
 * for Sqlite database.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\Common\Sqlite
 * @since 3.1
 */
class TSqliteCommandBuilder extends TDbCommandBuilder
{
	/**
	 * Alters the sql to apply $limit and $offset.
	 * @param string $sql SQL query string.
	 * @param int $limit maximum number of rows, -1 to ignore limit.
	 * @param int $offset row offset, -1 to ignore offset.
	 * @return string SQL with limit and offset.
	 */
	public function applyLimitOffset($sql, $limit = -1, $offset = -1)
	{
		$limit = $limit !== null ? (int) $limit : -1;
		$offset = $offset !== null ? (int) $offset : -1;
		if ($limit > 0 || $offset > 0) {
			$limitStr = ' LIMIT ' . $limit;
			$offsetStr = $offset >= 0 ? ' OFFSET ' . $offset : '';
			return $sql . $limitStr . $offsetStr;
		} else {
			return $sql;
		}
	}
}
