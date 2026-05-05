<?php

/**
 * TSqlSrvTableInfo class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common\SqlSrv;

use Prado\Data\Common\IDbHasSchema;
use Prado\Data\Common\SqlSrv\TSqlSrvCommandBuilder;
use Prado\Data\Common\TDbTableInfo;

/**
 * TSqlSrvTableInfo class
 *
 * TSqlSrvTableInfo class provides additional table information for SqlSrv database.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @since 3.1
 */
class TSqlSrvTableInfo extends TDbTableInfo implements IDbHasSchema
{
	/**
	 * @return string catalog name (database name)
	 */
	public function getCatalogName()
	{
		return $this->getInfo('CatalogName');
	}

	/**
	 * @return string full name of the table, database dependent.
	 */
	public function getTableFullName()
	{
		//SQL Server always returns the catalog, schema and table names.
		return '[' . $this->getCatalogName() . '].[' . $this->getSchemaName() . '].[' . $this->getTableName() . ']';
	}

	/**
	 * @param \Prado\Data\TDbConnection $connection database connection.
	 * @return \Prado\Data\Common\TDbCommandBuilder new command builder
	 */
	public function createCommandBuilder($connection)
	{
		return new TSqlSrvCommandBuilder($connection, $this);
	}
}
