<?php
/**
 * TMssqlTableColumn class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 * @package System.Data.Common.Mssql
 */

/**
 * Load common TDbTableCommon class.
 */
Prado::using('System.Data.Common.TDbTableColumn');

/**
 * Describes the column metadata of the schema for a Mssql database table.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package System.Data.Common.Mssql
 * @since 3.1
 */
class TMssqlTableColumn extends TDbTableColumn
{
	private static $types = array();

	/**
	 * Overrides parent implementation, returns PHP type from the db type.
	 * @return boolean derived PHP primitive type from the column db type.
	 */
	public function getPHPType()
	{

		return 'string';
	}

	/**
	 * @return boolean true if the column has identity (auto-increment)
	 */
	public function getAutoIncrement()
	{
		return $this->getInfo('AutoIncrement',false);
	}

	/**
	 * @return boolean true if auto increments.
	 */
	public function hasSequence()
	{
		return $this->getAutoIncrement();
	}

	/**
	 * @return boolean true if db type is 'timestamp'.
	 */
	public function getIsExcluded()
	{
		return strtolower($this->getDbType())==='timestamp';
	}
}

