<?php

/**
 * TMssqlTableInfo class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common\Mssql;

/**
 * Loads the base TDbTableInfo class and TMssqlTableColumn class.
 */
use Prado\Data\Common\IDbHasSchema;
use Prado\Data\Common\TDbTableInfo;
use Prado\Prado;

/**
 * TMssqlTableInfo class provides additional table information for Mssql database.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @since 3.1
 */
class TMssqlTableInfo extends TDbTableInfo implements IDbHasSchema
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
		//MSSQL alway returns the catalog, schem and table names.
		return '[' . $this->getCatalogName() . '].[' . $this->getSchemaName() . '].[' . $this->getTableName() . ']';
	}

	/**
	 * @param \Prado\Data\TDbConnection $connection database connection.
	 * @return \Prado\Data\Common\TDbCommandBuilder new command builder
	 */
	public function createCommandBuilder($connection)
	{
		return new TMssqlCommandBuilder($connection, $this);
	}
}
