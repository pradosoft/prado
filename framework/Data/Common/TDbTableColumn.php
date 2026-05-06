<?php

/**
 * TDbTableColumn class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common;

use PDO;

/**
 * TDbTableColumn class
 *
 * TDbTableColumn describes the metadata of a single column in a database table.
 *
 * Each instance wraps a flat associative info array that is populated by the
 * driver-specific {@see TDbMetaData} subclass when it introspects the live
 * schema.  The info array is passed to the constructor and accessed internally
 * through {@see getInfo()} / {@see setInfo()}.  Driver subclasses
 * (e.g. {@see TMysqlTableColumn}, {@see TSqliteTableColumn}) extend this class
 * to map native database types to PHP primitives and to expose any
 * engine-specific column attributes.
 *
 * ## Info array keys
 *
 * The following keys are recognized by the base class; each has a getter:
 *
 * | Key                  | Getter                          | Notes                                             |
 * |----------------------|---------------------------------|---------------------------------------------------|
 * | `ColumnName`         | {@see getColumnName()}          | Identifier-quoted name, e.g. `"id"` or `` `id` `` |
 * | `ColumnId`           | {@see getColumnId()}            | Bare (unquoted) column name used in ORDER BY       |
 * | `ColumnSize`         | {@see getColumnSize()}          | Maximum character or byte length, if applicable    |
 * | `ColumnIndex`        | {@see getColumnIndex()}         | Zero-based ordinal position in the table           |
 * | `DbType`             | {@see getDbType()}              | Native type string, e.g. `'varchar'`, `'integer'`  |
 * | `AllowNull`          | {@see getAllowNull()}           | `true` when NULL is a legal value; default `false` |
 * | `DefaultValue`       | {@see getDefaultValue()}        | Column default; {@see UNDEFINED_VALUE} when absent |
 * | `NumericPrecision`   | {@see getNumericPrecision()}    | Total significant-digit count for numeric types    |
 * | `NumericScale`       | {@see getNumericScale()}        | Decimal digits after the point for numeric types   |
 * | `IsPrimaryKey`       | {@see getIsPrimaryKey()}        | `true` when the column is part of the primary key  |
 * | `IsForeignKey`       | {@see getIsForeignKey()}        | `true` when the column is a foreign key            |
 * | `SequenceName`       | {@see getSequenceName()}        | Auto-increment sequence name, or null if none      |
 *
 * ## UNDEFINED_VALUE
 *
 * The sentinel {@see UNDEFINED_VALUE} is PHP's `INF`.  It is returned by
 * {@see getDefaultValue()} when the column has no declared default, so callers
 * can distinguish "default is `null`" from "no default defined":
 * ```php
 * if ($col->getDefaultValue() === TDbTableColumn::UNDEFINED_VALUE) {
 *     // no default — column must be supplied on INSERT
 * }
 * ```
 *
 * ## Type mapping
 *
 * {@see getPHPType()} returns the PHP primitive type that best represents the
 * column's database type: `'string'` (default), `'integer'`, or `'boolean'`.
 * Driver subclasses override this to implement their specific type maps.
 * {@see getPdoType()} translates the PHP type to a `PDO::PARAM_*` constant and
 * is used by {@see TDbCommandBuilder::bindColumnValues()} when constructing
 * INSERT and UPDATE commands.
 *
 * ## Exclusion
 *
 * {@see getIsExcluded()} returns `false` in the base class.  Driver subclasses
 * may override it to mark computed or auto-generated columns that should be
 * omitted from INSERT and UPDATE statements.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @since 3.1
 */
class TDbTableColumn extends \Prado\TComponent implements IDataColumn
{
	public const UNDEFINED_VALUE = INF; //use infinity for undefined value

	private $_info = [];

	/**
	 * Sets the table column meta data.
	 * @param array $columnInfo table column information.
	 */
	public function __construct($columnInfo)
	{
		$this->_info = $columnInfo;
		parent::__construct();
	}

	/**
	 * @param string $name information array key name
	 * @param mixed $default default value if information array value is null
	 * @return mixed information array value.
	 */
	protected function getInfo($name, $default = null)
	{
		return $this->_info[$name] ?? $default;
	}

	/**
	 * @param string $name information array key name
	 * @param mixed $value new information array value.
	 */
	protected function setInfo($name, $value)
	{
		$this->_info[$name] = $value;
	}

	/**
	 * Returns the derived PHP primitive type from the db type. Default returns 'string'.
	 * @return string derived PHP primitive type from the column db type.
	 */
	public function getPHPType()
	{
		return 'string';
	}

	/**
	 * @return int PDO bind param/value types, default returns string.
	 */
	public function getPdoType()
	{
		switch ($this->getPHPType()) {
			case 'boolean': return PDO::PARAM_BOOL;
			case 'integer': return PDO::PARAM_INT;
			case 'string': return PDO::PARAM_STR;
		}
		return PDO::PARAM_STR;
	}

	/**
	 * @return string name of the column in the table (identifier quoted).
	 */
	public function getColumnName()
	{
		return $this->getInfo('ColumnName');
	}

	/**
	 * @return string name of the column with quoted identifier.
	 */
	public function getColumnId()
	{
		return $this->getInfo('ColumnId');
	}

	/**
	 * @return string size of the column.
	 */
	public function getColumnSize()
	{
		return $this->getInfo('ColumnSize');
	}

	/**
	 * @return int zero-based ordinal position of the column in the table.
	 */
	public function getColumnIndex()
	{
		return $this->getInfo('ColumnIndex');
	}

	/**
	 * @return string column type.
	 */
	public function getDbType()
	{
		return $this->getInfo('DbType');
	}

	/**
	 * @return bool specifies whether value Null is allowed, default is false.
	 */
	public function getAllowNull()
	{
		return $this->getInfo('AllowNull', false);
	}

	/**
	 * @return mixed default column value if column value was null.
	 */
	public function getDefaultValue()
	{
		return $this->getInfo('DefaultValue', self::UNDEFINED_VALUE);
	}

	/**
	 * @return string precision of the column data, if the data is numeric.
	 */
	public function getNumericPrecision()
	{
		return $this->getInfo('NumericPrecision');
	}

	/**
	 * @return string scale of the column data, if the data is numeric.
	 */
	public function getNumericScale()
	{
		return $this->getInfo('NumericScale');
	}

	public function getMaxiumNumericConstraint()
	{
		if (($precision = $this->getNumericPrecision()) !== null) {
			$scale = $this->getNumericScale();
			return $scale === null ? pow(10, (int) $precision) : pow(10, (int) $precision - (int) $scale);
		}
	}

	/**
	 * @return bool whether this column is a primary key for the table, default is false.
	 */
	public function getIsPrimaryKey()
	{
		return $this->getInfo('IsPrimaryKey', false);
	}

	/**
	 * @return bool whether this column is a foreign key, default is false.
	 */
	public function getIsForeignKey()
	{
		return $this->getInfo('IsForeignKey', false);
	}

	/**
	 * @return string sequence name, only applicable if column is a sequence
	 */
	public function getSequenceName()
	{
		return $this->getInfo('SequenceName');
	}

	/**
	 * @return bool whether the column is a sequence.
	 */
	public function hasSequence()
	{
		return $this->getSequenceName() !== null;
	}

	/**
	 * @return bool whether this column is excluded from insert and update.
	 */
	public function getIsExcluded()
	{
		return false;
	}
}
