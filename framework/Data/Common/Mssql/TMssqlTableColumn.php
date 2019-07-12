<?php
/**
 * TMssqlTableColumn class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\Common\Mssql
 */

namespace Prado\Data\Common\Mssql;

/**
 * Load common TDbTableCommon class.
 */
use Prado\Data\Common\TDbTableColumn;
use Prado\Prado;

/**
 * Describes the column metadata of the schema for a Mssql database table.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\Common\Mssql
 * @since 3.1
 */
class TMssqlTableColumn extends TDbTableColumn
{
	private static $types = [];

	/**
	 * Overrides parent implementation, returns PHP type from the db type.
	 * @return bool derived PHP primitive type from the column db type.
	 */
	public function getPHPType()
	{
		return 'string';
	}

	/**
	 * @return bool true if the column has identity (auto-increment)
	 */
	public function getAutoIncrement()
	{
		return $this->getInfo('AutoIncrement', false);
	}

	/**
	 * @return bool true if auto increments.
	 */
	public function hasSequence()
	{
		return $this->getAutoIncrement();
	}

	/**
	 * @return bool true if db type is 'timestamp'.
	 */
	public function getIsExcluded()
	{
		return strtolower($this->getDbType()) === 'timestamp';
	}
}
