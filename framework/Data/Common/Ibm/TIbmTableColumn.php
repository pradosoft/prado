<?php

/**
 * TIbmTableColumn class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common\Ibm;

use Prado\Data\Common\TDbTableColumn;

/**
 * Describes the column metadata of a schema for an IBM DB2 database table.
 *
 * Maps DB2 SQL types to PHP primitive types and exposes identity (auto-increment)
 * column information.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TIbmTableColumn extends TDbTableColumn
{
	private static $types = [
		'integer' => ['integer', 'int', 'bigint', 'smallint'],
		'boolean' => ['boolean'],
		'float' => ['double', 'real', 'float', 'decimal', 'numeric', 'decfloat'],
	];

	/**
	 * Overrides parent implementation, returns PHP type from the DB2 column type.
	 * @return string derived PHP primitive type.
	 */
	public function getPHPType()
	{
		$dbtype = strtolower(trim($this->getDbType()));
		foreach (self::$types as $type => $dbtypes) {
			if (in_array($dbtype, $dbtypes)) {
				return $type;
			}
		}
		return 'string';
	}

	/**
	 * @return bool true if this is an identity (auto-increment) column.
	 */
	public function getAutoIncrement()
	{
		return $this->getInfo('AutoIncrement', false);
	}

	/**
	 * @return bool true if auto-increment (identity) is defined.
	 */
	public function hasSequence()
	{
		return $this->getAutoIncrement();
	}
}
