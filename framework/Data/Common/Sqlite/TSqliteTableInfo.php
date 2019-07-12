<?php
/**
 * TSqliteTableInfo class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\Common\Sqlite
 */

namespace Prado\Data\Common\Sqlite;

/**
 * Loads the base TDbTableInfo class and TSqliteTableColumn class.
 */
use Prado\Data\Common\TDbTableInfo;
use Prado\Prado;

/**
 * TSqliteTableInfo class provides additional table information for PostgreSQL database.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\Common\Sqlite
 * @since 3.1
 */
class TSqliteTableInfo extends TDbTableInfo
{
	/**
	 * @param TDbConnection $connection database connection.
	 * @return TDbCommandBuilder new command builder
	 */
	public function createCommandBuilder($connection)
	{
		return new TSqliteCommandBuilder($connection, $this);
	}

	/**
	 * @return string full name of the table, database dependent.
	 */
	public function getTableFullName()
	{
		return "'" . $this->getTableName() . "'";
	}
}
