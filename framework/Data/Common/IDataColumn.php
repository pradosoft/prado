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
 * IDataColumn defines the minimum contract for a column (field) metadata object.
 *
 * The interface is shaped after the core accessors of {@see TDbTableColumn}, which
 * is the canonical SQL implementation, but is intentionally decoupled from it so
 * that application code and third-party plugins can supply custom implementations
 * without coupling to the SQL class hierarchy.  For example, a MongoDB field
 * descriptor or a spreadsheet column descriptor may implement this interface
 * without inheriting from `TDbTableColumn`.
 *
 * The interface covers the core column contract including type reporting
 * ({@see getPHPType()}, {@see getPdoType()}) and nullability.  More
 * driver-specific concerns — default values, ordinal position, sequence
 * names, auto-increment flags — remain on the concrete implementation class.
 * Code that needs those details should check `instanceof TDbTableColumn`
 * explicitly, following the same marker-interface pattern used by
 * {@see IDbHasSchema}.  Non-SQL implementations should stub {@see getPdoType()}
 * with a sensible default (e.g. `PDO::PARAM_STR`).
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

	// -------------------------------------------------------------------------
	// SQL/PDO-oriented methods.
	// SQL drivers implement these fully.  Non-SQL drivers should provide a
	// no-op stub returning a sensible default (e.g. PDO::PARAM_STR / 2).
	// -------------------------------------------------------------------------

	/**
	 * Returns a driver type token that best represents this column's declared
	 * database type, for use when binding parameter values.
	 *
	 * For SQL/PDO drivers the returned integer is one of the stable PDO type
	 * constants: `PDO::PARAM_BOOL (5)`, `PDO::PARAM_INT (1)`,
	 * `PDO::PARAM_STR (2)`.  Used by
	 * {@see \Prado\Data\Common\TDbCommandBuilder::bindColumnValues()} when
	 * constructing INSERT and UPDATE commands.
	 *
	 * Non-SQL drivers that do not use PDO parameter binding may return
	 * `PDO::PARAM_STR` (2) as a safe default, or the equivalent type token
	 * meaningful to their binding layer.
	 *
	 * Prefer {@see getPHPType()} combined with
	 * {@see \Prado\Data\IDataCommand::getColumnTypeFromValue()} for new code
	 * that must remain driver-agnostic.
	 *
	 * @return int the driver parameter-type token.
	 */
	public function getPdoType();
}
