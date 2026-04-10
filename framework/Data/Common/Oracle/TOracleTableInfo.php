<?php

/**
 * TOracleTableInfo class file.
 *
 * @author Marcos Nobre <marconobre[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common\Oracle;

use Prado\Data\Common\IDbHasSchema;
use Prado\Data\Common\TDbTableInfo;
use Prado\Prado;

/**
 * TOracleTableInfo provides additional table information for Oracle databases.
 *
 * @author Marcos Nobre <marconobre[at]gmail[dot]com>
 * @since 3.1
 */
class TOracleTableInfo extends TDbTableInfo implements IDbHasSchema
{
	/**
	 * @return string full name of the table, schema-qualified.
	 */
	public function getTableFullName()
	{
		return $this->getSchemaName() . '.' . $this->getTableName();
	}

	/**
	 * @param \Prado\Data\TDbConnection $connection database connection.
	 * @return \Prado\Data\Common\TDbCommandBuilder new command builder
	 */
	public function createCommandBuilder($connection)
	{
		return new TOracleCommandBuilder($connection, $this);
	}
}
