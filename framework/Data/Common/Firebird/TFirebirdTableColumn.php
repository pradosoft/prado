<?php

/**
 * TFirebirdTableColumn class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common\Firebird;

use Prado\Data\Common\TDbTableColumn;

/**
 * Describes the column metadata of a schema for a Firebird database table.
 *
 * Maps Firebird SQL types (derived from RDB$FIELD_TYPE codes) to PHP primitive
 * types, and exposes identity (auto-increment) column information for Firebird 3+.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TFirebirdTableColumn extends TDbTableColumn
{
	private static $types = [
		'integer' => ['SMALLINT', 'INTEGER', 'BIGINT'],
		'boolean' => ['BOOLEAN'],
		'float' => ['FLOAT', 'DOUBLE PRECISION', 'DECIMAL', 'NUMERIC', 'DECFLOAT(16)', 'DECFLOAT(34)'],
	];

	/**
	 * Overrides parent implementation, returns PHP type from the Firebird column type.
	 * @return string derived PHP primitive type.
	 */
	public function getPHPType()
	{
		$dbtype = strtoupper(trim($this->getDbType()));
		foreach (self::$types as $type => $dbtypes) {
			if (in_array($dbtype, $dbtypes)) {
				return $type;
			}
		}
		return 'string';
	}

	/**
	 * @return bool true if this is an identity (auto-increment) column (Firebird 3+).
	 */
	public function getAutoIncrement()
	{
		return $this->getInfo('AutoIncrement', false);
	}

	/**
	 * @return bool true if auto-increment is defined.
	 */
	public function hasSequence()
	{
		return $this->getAutoIncrement();
	}
}
