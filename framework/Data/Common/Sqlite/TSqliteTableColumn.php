<?php

/**
 * TSqliteTableColumn class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common\Sqlite;

/**
 * Load common TDbTableCommon class.
 */
use Prado\Data\Common\TDbTableColumn;
use Prado\Prado;

/**
 * Describes the column metadata of the schema for an SQLite database table.
 *
 * {@see getPHPType()} maps the declared type to a PHP type by name, and a declared
 * type outside the map follows the SQLite type affinity rules. `DbType` holds the
 * declared type in lowercase without its size, so `VARCHAR(45)` is `varchar`.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @since 3.1
 */
class TSqliteTableColumn extends TDbTableColumn
{
	/**
	 * @var array PHP type → declared SQLite types with that PHP type.
	 */
	private static $types = [
		'integer' => ['int', 'integer', 'tinyint', 'smallint', 'mediumint', 'bigint', 'unsigned big int', 'int2', 'int8'],
		'boolean' => ['boolean', 'bool'],
		'float' => ['real', 'double', 'double precision', 'float', 'numeric', 'decimal'],
	];

	/**
	 * Overrides parent implementation, returns PHP type from the db type.
	 * A declared type in the type map returns its PHP type; any other declared type
	 * returns {@see getAffinityType()}.
	 * @return string derived PHP primitive type from the column db type.
	 */
	public function getPHPType()
	{
		$dbtype = strtolower($this->getDbType());
		foreach (self::$types as $type => $dbtypes) {
			if (in_array($dbtype, $dbtypes)) {
				return $type;
			}
		}
		return $this->getAffinityType($dbtype);
	}

	/**
	 * Derives the PHP type from the SQLite type affinity of a declared type. The
	 * rules apply in order, as SQLite applies them:
	 *
	 * | declared type contains | affinity | PHP type |
	 * |---|---|---|
	 * | `int` | INTEGER | `integer` |
	 * | `char`, `clob`, or `text` | TEXT | `string` |
	 * | `blob`, or no type | BLOB | `string` |
	 * | `real`, `floa`, or `doub` | REAL | `float` |
	 * | anything else | NUMERIC | `string` |
	 *
	 * @param string $dbtype declared type in lowercase.
	 * @return string PHP type.
	 * @since 4.4.0
	 */
	protected function getAffinityType($dbtype)
	{
		if (str_contains($dbtype, 'int')) {
			return 'integer';
		}
		if (str_contains($dbtype, 'char') || str_contains($dbtype, 'clob') || str_contains($dbtype, 'text')) {
			return 'string';
		}
		if ($dbtype === '' || str_contains($dbtype, 'blob')) {
			return 'string';
		}
		if (str_contains($dbtype, 'real') || str_contains($dbtype, 'floa') || str_contains($dbtype, 'doub')) {
			return 'float';
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
