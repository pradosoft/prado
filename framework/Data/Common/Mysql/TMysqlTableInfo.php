<?php

/**
 * TMysqlTableInfo class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common\Mysql;

/**
 * Loads the base TDbTableInfo class and TMysqlTableColumn class.
 */
use Prado\Data\Common\TDbTableInfo;
use Prado\Prado;

/**
 * TMysqlTableInfo class provides additional table information for MySQL database.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @since 3.1
 */
class TMysqlTableInfo extends TDbTableInfo
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
			return '`' . $schema . '`.`' . $this->getTableName() . '`';
		} else {
			return '`' . $this->getTableName() . '`';
		}
	}

	/**
	 * @param \Prado\Data\TDbConnection $connection database connection.
	 * @return \Prado\Data\Common\TDbCommandBuilder new command builder
	 */
	public function createCommandBuilder($connection)
	{
		return new TMysqlCommandBuilder($connection, $this);
	}
}
