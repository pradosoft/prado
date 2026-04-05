<?php

/**
 * TIbmTableInfo class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common\Ibm;

use Prado\Data\Common\TDbTableInfo;

/**
 * TIbmTableInfo provides additional table information for IBM DB2 databases.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TIbmTableInfo extends TDbTableInfo
{
	/**
	 * @return string schema (owner) name of this table.
	 */
	public function getSchemaName()
	{
		return $this->getInfo('SchemaName');
	}

	/**
	 * @return string fully qualified table name (schema + table), double-quote delimited.
	 */
	public function getTableFullName()
	{
		if (($schema = $this->getSchemaName()) !== null && $schema !== '') {
			return '"' . $schema . '"."' . $this->getTableName() . '"';
		}
		return '"' . $this->getTableName() . '"';
	}

	/**
	 * @param \Prado\Data\TDbConnection $connection database connection.
	 * @return TIbmCommandBuilder new command builder.
	 */
	public function createCommandBuilder($connection)
	{
		return new TIbmCommandBuilder($connection, $this);
	}
}
