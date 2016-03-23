<?php
/**
 * TSqliteTableColumn class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 * @package System.Data.Common.Sqlite
 */

/**
 * Load common TDbTableCommon class.
 */
Prado::using('System.Data.Common.TDbTableColumn');

/**
 * Describes the column metadata of the schema for a PostgreSQL database table.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package System.Data.Common.Sqlite
 * @since 3.1
 */
class TSqliteTableColumn extends TDbTableColumn
{
	/**
	 * @TODO add sqlite types.
	 */
	private static $types = array();

	/**
	 * Overrides parent implementation, returns PHP type from the db type.
	 * @return boolean derived PHP primitive type from the column db type.
	 */
	public function getPHPType()
	{
		$dbtype = strtolower($this->getDbType());
		foreach(self::$types as $type => $dbtypes)
		{
			if(in_array($dbtype, $dbtypes))
				return $type;
		}
		return 'string';
	}

	/**
	 * @return boolean true if column will auto-increment when the column value is inserted as null.
	 */
	public function getAutoIncrement()
	{
		return $this->getInfo('AutoIncrement', false);
	}

	/**
	 * @return boolean true if auto increment is true.
	 */
	public function hasSequence()
	{
		return $this->getAutoIncrement();
	}
}

