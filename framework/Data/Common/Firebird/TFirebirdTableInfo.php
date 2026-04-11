<?php

/**
 * TFirebirdTableInfo class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common\Firebird;

use Prado\Data\Common\TDbTableInfo;

/**
 * TFirebirdTableInfo provides additional table information for Firebird databases.
 *
 * Firebird has no schema namespace; table names are unique within a database file.
 * The full name is simply the quoted table name.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TFirebirdTableInfo extends TDbTableInfo
{
	/**
	 * @return string fully qualified table name (double-quote delimited for case safety).
	 */
	public function getTableFullName()
	{
		return '"' . strtoupper($this->getTableName()) . '"';
	}

	/**
	 * @param \Prado\Data\TDbConnection $connection database connection.
	 * @return TFirebirdCommandBuilder new command builder.
	 */
	public function createCommandBuilder($connection)
	{
		return new TFirebirdCommandBuilder($connection, $this);
	}
}
