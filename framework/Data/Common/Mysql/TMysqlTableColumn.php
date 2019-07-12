<?php
/**
 * TMysqlTableColumn class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\Common\Mysql
 */

namespace Prado\Data\Common\Mysql;

/**
 * Load common TDbTableCommon class.
 */
use Prado\Data\Common\TDbTableColumn;
use Prado\Prado;

/**
 * Describes the column metadata of the schema for a Mysql database table.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\Common\Mysql
 * @since 3.1
 */
class TMysqlTableColumn extends TDbTableColumn
{
	private static $types = [
		'integer' => ['bit', 'tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint'],
		'boolean' => ['boolean', 'bool'],
		'float' => ['float', 'double', 'double precision', 'decimal', 'dec', 'numeric', 'fixed']
		];

	/**
	 * Overrides parent implementation, returns PHP type from the db type.
	 * @return bool derived PHP primitive type from the column db type.
	 */
	public function getPHPType()
	{
		$dbtype = trim(str_replace(['unsigned', 'zerofill'], ['', '', ], strtolower($this->getDbType())));
		if ($dbtype === 'tinyint' && $this->getColumnSize() === 1) {
			return 'boolean';
		}
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

	public function getDbTypeValues()
	{
		return $this->getInfo('DbTypeValues');
	}
}
