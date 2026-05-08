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
 * IDataColumn defines the minimum driver-agnostic contract for a column (field)
 * metadata object.
 *
 * The interface is shaped after the core accessors of {@see TDbTableColumn}, which
 * is the canonical SQL implementation, but is intentionally decoupled from it so
 * that application code and third-party plugins can supply custom implementations
 * without coupling to the SQL class hierarchy.  For example, a MongoDB field
 * descriptor or a spreadsheet column descriptor may implement this interface
 * without inheriting from `TDbTableColumn`.
 *
 * The interface covers identity ({@see getColumnName()}, {@see getColumnId()}),
 * nullability ({@see getAllowNull()}), the raw database type ({@see getDbType()}),
 * and the PHP primitive type ({@see getPHPType()}).  All of these are meaningful
 * to any data-store driver.
 *
 * PDO-specific binding ({@see IDbColumn::getPdoType()}) lives on the sub-interface
 * {@see IDbColumn}, following the same layering pattern as
 * {@see \Prado\Data\IDataConnection} / {@see \Prado\Data\IDbConnection}.
 * Code that works exclusively with SQL/PDO drivers should type-hint against
 * {@see IDbColumn}; code that must remain driver-agnostic uses this interface.
 *
 * Driver-specific concerns — default values, ordinal position, sequence names,
 * auto-increment flags — remain on the concrete implementation class.
 * Code that needs those details should check `instanceof TDbTableColumn`
 * explicitly, following the same marker-interface pattern used by
 * {@see IDbHasSchema}.
 *
 * Concrete SQL implementations: {@see TDbTableColumn} and its driver-specific
 * subclasses ({@see TMysqlTableColumn}, {@see TSqliteTableColumn},
 * {@see TPgsqlTableColumn}, {@see TOracleTableColumn}, {@see TIbmTableColumn},
 * {@see TFirebirdTableColumn}).
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
interface IDataColumn
{
	/**
	 * Returns the identifier-quoted column name as it should appear in SQL.
	 *
	 * For SQL drivers this is the driver-specific quoted form, e.g. `` `id` ``
	 * for MySQL or `"id"` for PostgreSQL.  For non-SQL implementations the
	 * value is driver-defined but must uniquely identify the column within
	 * the context of its containing collection.
	 *
	 * @return string the identifier-quoted column name.
	 */
	public function getColumnName();

	/**
	 * Returns the bare (unquoted) column identifier.
	 *
	 * This is the canonical key used to look up the column in the column map
	 * and to reference it in ORDER BY clauses where quoting is not needed.
	 *
	 * @return string the bare column identifier.
	 */
	public function getColumnId();

	/**
	 * Returns the native database type string for this column.
	 *
	 * The value is driver-specific (e.g. `'varchar'`, `'integer'`, `'text'`
	 * for SQL drivers; `'string'`, `'int32'` for document stores).
	 *
	 * @return ?string the native type string, or null if not available.
	 */
	public function getDbType();

	/**
	 * Returns whether null is a legal value for this column.
	 *
	 * @return bool true if null is allowed; false otherwise.
	 */
	public function getAllowNull();

	/**
	 * Returns the PHP primitive type that best represents this column's declared
	 * database type.
	 *
	 * The returned string is one of the PHP primitive type names: `'string'`,
	 * `'integer'`, `'boolean'`, or `'double'`.  It is used by
	 * {@see \Prado\Shell\Actions\TActiveRecordAction} for code generation
	 * and is the driver-agnostic counterpart to {@see IDbColumn::getPdoType()}.
	 *
	 * Non-SQL drivers should return `'string'` as the safe default when no
	 * more specific type can be determined.
	 *
	 * @return string the PHP primitive type name for this column.
	 */
	public function getPHPType();
}
