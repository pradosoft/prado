<?php

/**
 * IDataColumn interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\Common;

/**
 * IDataColumn interface
 *
 * Driver-agnostic contract for column (field) metadata.  Shaped after
 * {@see TDbTableColumn} but decoupled so non-SQL drivers (document stores,
 * spreadsheets, etc.) can implement it without inheriting the SQL class
 * hierarchy.  Driver-specific concerns (default values, sequence names,
 * auto-increment, etc.) remain on the concrete implementation class.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
interface IDataColumn
{
	/**
	 * @return string the identifier-quoted column name (driver-specific form).
	 */
	public function getColumnName();

	/**
	 * @return string the bare (unquoted) column identifier.
	 */
	public function getColumnId();

	/**
	 * @return ?string the native database type string (e.g. `'varchar'`,
	 *   `'integer'`), or null if not available.
	 */
	public function getDbType();

	/**
	 * @return bool whether null is a legal value for this column.
	 */
	public function getAllowNull();

	/**
	 * Returns the PHP primitive type name (`'string'`, `'integer'`, `'boolean'`,
	 * `'double'`) that best represents this column's declared type.  Used by
	 * {@see \Prado\Shell\Actions\TActiveRecordAction} for code generation.
	 * @return string the PHP primitive type name.
	 */
	public function getPHPType();

	/**
	 * Returns the bind-parameter token for this column, used by
	 * {@see \Prado\Data\IDataCommand::bindValue} / `bindParameter`.  One of the
	 * {@see \Prado\Data\IDataConnection} `PARAM_*` constants; resolves to
	 * `PDO::PARAM_*` for SQL/PDO drivers.
	 * @return int the bind-parameter token.
	 * @since 4.3.3
	 */
	public function getParamType();
}
