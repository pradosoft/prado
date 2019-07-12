<?php
/**
 * TPgsqlTableInfo class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\Common\Pgsql
 */

namespace Prado\Data\Common\Pgsql;

/**
 * Loads the base TDbTableInfo class and TPgsqlTableColumn class.
 */
use Prado\Data\Common\TDbTableInfo;
use Prado\Prado;

/**
 * TPgsqlTableInfo class provides additional table information for PostgreSQL database.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\Common\Pgsql
 * @since 3.1
 */
class TPgsqlTableInfo extends TDbTableInfo
{
	/**
	 * @return string name of the schema this column belongs to.
	 */
	public function getSchemaName()
	{
		return $this->getInfo('SchemaName');
	}

	/**
	 * @return string full name of the table, database dependent.
	 */
	public function getTableFullName()
	{
		if (($schema = $this->getSchemaName()) !== null) {
			return $schema . '.' . $this->getTableName();
		} else {
			return $this->getTableName();
		}
	}

	/**
	 * @param TDbConnection $connection database connection.
	 * @return TDbCommandBuilder new command builder
	 */
	public function createCommandBuilder($connection)
	{
		return new TPgsqlCommandBuilder($connection, $this);
	}
}
