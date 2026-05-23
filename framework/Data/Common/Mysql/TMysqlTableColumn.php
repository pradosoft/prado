<?php

/**
 * TMysqlTableColumn class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
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
 * @since 3.1
 */
class TMysqlTableColumn extends TDbTableColumn
{
	private static $types = [
		'integer' => ['bit', 'tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint'],
		// 'boolean' and 'bool' are MySQL aliases for TINYINT(1).  MySQL always
		// stores and reports them as 'tinyint(1)' in SHOW FULL FIELDS, so they are
		// detected via the tinyint(1) path in getPHPType() rather than this table.
		// The entries are kept here as a fallback in case a future MySQL version
		// ever returns the bare keyword in schema metadata.
		'boolean' => ['boolean', 'bool'],
		'float' => ['float', 'double', 'double precision', 'decimal', 'dec', 'numeric', 'fixed'],
	];

	/**
	 * Returns the raw column type string as reported by SHOW FULL FIELDS, before
	 * the parenthesised portion is stripped into {@see getDbType()} and
	 * {@see getColumnSize()}.  Examples: `'tinyint(1)'`, `'varchar(255)'`,
	 * `'enum(\'a\',\'b\')'`.
	 *
	 * This is used by {@see getPHPType()} to detect the `tinyint(1)` → boolean
	 * convention in a forward-compatible way.  If a future MySQL version stops
	 * including integer display widths in SHOW FULL FIELDS output (so that
	 * {@see getColumnSize()} returns `null` for `TINYINT(1)` columns), the raw
	 * ColumnType string still identifies them correctly as long as MySQL continues
	 * to report `tinyint(1)` for columns declared as `BOOLEAN` / `BOOL`.
	 *
	 * @return null|string raw type string, or null for columns introspected before
	 *                     this field was added to the metadata.
	 * @since 4.3.3
	 */
	public function getColumnType(): ?string
	{
		return $this->getInfo('ColumnType');
	}

	/**
	 * Overrides parent implementation, returns PHP type from the db type.
	 *
	 * Boolean detection uses two complementary signals so that it remains correct
	 * across MySQL versions:
	 *
	 * - **`ColumnSize === 1`** — works while SHOW FULL FIELDS includes display
	 *   widths (MySQL ≤ 8.x, and MySQL 9.x which retains `tinyint(1)` as a
	 *   special case for the boolean convention).
	 * - **`ColumnType === 'tinyint(1)'`** — a forward-compatible fallback that
	 *   checks the raw type string preserved before parsing.  If MySQL ever stops
	 *   reporting the `(1)` suffix in the Type field but another metadata source
	 *   (e.g. information_schema) still provides it, this path can be updated
	 *   without touching the detection logic.
	 *
	 * @return string derived PHP primitive type from the column db type.
	 */
	public function getPHPType()
	{
		$dbtype = trim(str_replace(['unsigned', 'zerofill'], ['', ''], strtolower($this->getDbType())));
		if ($dbtype === 'tinyint') {
			$columnType = strtolower(trim((string) $this->getColumnType()));
			if ($this->getColumnSize() === 1 || $columnType === 'tinyint(1)') {
				return 'boolean';
			}
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
