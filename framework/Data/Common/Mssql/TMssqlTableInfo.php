<?php
/**
 * TMssqlTableInfo class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\Common\Mssql
 */

namespace Prado\Data\Common\Mssql;

/**
 * Loads the base TDbTableInfo class and TMssqlTableColumn class.
 */
use Prado\Data\Common\TDbTableInfo;
use Prado\Prado;

/**
 * TMssqlTableInfo class provides additional table information for Mssql database.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\Common\Mssql
 * @since 3.1
 */
class TMssqlTableInfo extends TDbTableInfo
{
	/**
	 * @return string name of the schema this column belongs to.
	 */
	public function getSchemaName()
	{
		return $this->getInfo('SchemaName');
	}

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
	 * @param TDbConnection $connection database connection.
	 * @return TDbCommandBuilder new command builder
	 */
	public function createCommandBuilder($connection)
	{
		return new TMssqlCommandBuilder($connection, $this);
	}
}
