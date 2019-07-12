<?php
/**
 * TSqliteTableColumn class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\Common\Sqlite
 */

namespace Prado\Data\Common\Sqlite;

/**
 * Load common TDbTableCommon class.
 */
use Prado\Data\Common\TDbTableColumn;
use Prado\Prado;

/**
 * Describes the column metadata of the schema for a PostgreSQL database table.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\Common\Sqlite
 * @since 3.1
 */
class TSqliteTableColumn extends TDbTableColumn
{
	/**
	 * @TODO add sqlite types.
	 */
	private static $types = [];

	/**
	 * Overrides parent implementation, returns PHP type from the db type.
	 * @return bool derived PHP primitive type from the column db type.
	 */
	public function getPHPType()
	{
		$dbtype = strtolower($this->getDbType());
		foreach (self::$types as $type => $dbtypes) {
			if (in_array($dbtype, $dbtypes)) {
				return $type;
			}
		}
		return 'string';
	}

	/**
	 * @return bool true if column will auto-increment when the column value is inserted as null.
	 */
	public function getAutoIncrement()
	{
		return $this->getInfo('AutoIncrement', false);
	}

	/**
	 * @return bool true if auto increment is true.
	 */
	public function hasSequence()
	{
		return $this->getAutoIncrement();
	}
}
