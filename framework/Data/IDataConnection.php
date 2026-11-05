<?php

/**
 * IDataConnection interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data;

use Prado\Data\Common\IDataMetaData;

/**
 * IDataConnection interface
 *
 * IDataConnection defines the interface for a data-store connection.  It is the
 * driver-agnostic entry point: SQL/PDO connections ({@see TDbConnection}) and
 * non-SQL connections (e.g. `Prado\Data\TMongoConnection` from the prado-mongodb
 * plugin) both implement it, so framework consumers — DataGateway, ActiveRecord,
 * caching, cron, i18n — can target this single contract.
 *
 * For SQL drivers the `$query` argument to {@see createCommand} is a SQL string;
 * non-SQL drivers interpret it according to their own semantics.
 *
 * ## Portability roadmap
 *
 * The `IData*` family of interfaces forms a complete driver-agnostic contract
 * for the Data layer.  A third-party driver becomes a drop-in replacement for
 * the SQL/PDO stack by implementing the **required** interfaces; the
 * **optional** `IDb*` markers advertise PDO-specific capabilities and only
 * apply to SQL-backed drivers.
 *
 * **Required interfaces** — every driver must implement these:
 * - {@see IDataConnection} — this interface; the connection itself.
 * - {@see IDataCommand} — query/command execution and parameter binding.
 * - {@see IDataReader} — forward-only iteration over result rows.
 * - {@see IDataTransaction} — commit/rollback control.
 * - {@see \Prado\Data\Common\IDataMetaData} — schema introspection.
 * - {@see \Prado\Data\Common\IDataTableInfo} — per-table metadata.
 * - {@see \Prado\Data\Common\IDataColumn} — per-column metadata.
 * - {@see \Prado\Data\Common\IDataCommandBuilder} — CRUD command factories.
 * - {@see \Prado\Data\DataGateway\IDataCriteria} — portable query criteria.
 *
 * **Capability markers** — implement only if applicable:
 * - {@see \Prado\Data\Common\IDataHasSchema} — table-info classes whose engine
 *   supports a schema/namespace concept.
 *
 * **Optional PDO-discovery markers** — SQL/PDO drivers only:
 * - {@see IDbConnection} — exposes the raw `\PDO` handle via `getPdoInstance()`.
 * - {@see \Prado\Data\Common\IDbCommand} — SQL statement-text + lifecycle.
 * - {@see \Prado\Data\Common\IDbMetaData} — SQL identifier-quoting helpers.
 * - {@see \Prado\Data\Common\IDbCommandBuilder} — SQL-string composition.
 * - {@see \Prado\Data\Common\IDbColumn} — exposes the column's PDO `PARAM_*` token.
 *
 * Non-SQL drivers must NOT implement the `IDb*` markers; code that needs them
 * uses `instanceof` and falls back appropriately when absent.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
interface IDataConnection
{
	/**
	 * Parameter-type tokens for {@see IDataCommand::bindValue()} and
	 * {@see IDataCommand::bindParameter()}.  Values default to the PDO
	 * `PARAM_*` integers so SQL drivers can re-declare them with the
	 * `PDO::PARAM_*` constants directly; non-SQL drivers may override with
	 * their own routing tokens.  Callers should reference them on the
	 * connection instance (e.g. `$conn::PARAM_INT`) so the runtime class's
	 * value is used.
	 */
	public const PARAM_NULL = 0;
	public const PARAM_INT = 1;
	public const PARAM_STR = 2;
	public const PARAM_LOB = 3;
	public const PARAM_BOOL = 5;

	/**
	 * @return string the driver name (e.g. 'mysql', 'pgsql', 'sqlite').
	 */
	public function getDriverName();

	/**
	 * @return bool whether the connection is currently open.
	 */
	public function getActive();

	/**
	 * Opens or closes the connection.
	 *
	 * @param bool $value true to open, false to close.
	 */
	public function setActive($value);

	/**
	 * Creates a command for execution against this connection.
	 *
	 * For SQL connections ({@see TDbConnection}), $query is a SQL string.
	 *
	 * @param mixed $query the query specification (SQL string or equivalent).
	 * @return IDataCommand the new command object.
	 */
	public function createCommand($query);

	/**
	 * Begins a new transaction.
	 *
	 * Each call allocates a **new** {@see IDataTransaction} object.  Any
	 * previously returned transaction object is superseded: calling
	 * {@see IDataTransaction::beginTransaction()} on it will throw because it is
	 * no longer the connection's current transaction.
	 *
	 * Throws an exception if a transaction is already active. Commit or roll back
	 * the current transaction before starting a new one.
	 *
	 * To reuse the same transaction object for sequential work units without
	 * allocating a new one, call {@see IDataTransaction::beginTransaction()}
	 * directly on the returned object after commit or rollback.
	 *
	 * @return IDataTransaction the transaction object for the new work unit.
	 */
	public function beginTransaction();

	/**
	 * Returns the currently active transaction, or null if none is open.
	 * If a transaction is not active (as in, the transaction has been completed),
	 * then this returns null.
	 *
	 * @return null|IDataTransaction the active transaction, or null.
	 */
	public function getCurrentTransaction();

	/**
	 * Returns the last {@see IDataTransaction} object associated with this
	 * connection, whether or not it is still active.
	 *
	 * Differs from {@see getCurrentTransaction()}, which returns non-null only
	 * while a transaction is open.  This method returns the object stored when
	 * {@see beginTransaction()} was last called, regardless of its state.
	 *
	 * The primary use case is the supersession guard inside
	 * {@see IDataTransaction::beginTransaction()}: before reactivating a
	 * completed transaction object the implementation checks that it is still
	 * the last one on the connection.  If {@see beginTransaction()} has been
	 * called again since, a newer object is stored here and the old one is
	 * considered superseded.
	 *
	 * @return null|IDataTransaction the last transaction object, or null if
	 *   {@see beginTransaction()} has never been called on this connection.
	 */
	public function getLastTransaction(): ?IDataTransaction;

	/**
	 * Commits the currently active transaction on this connection.
	 *
	 * A convenience method for cases where the caller does not hold a reference
	 * to the transaction object. Returns false (and is a no-op) when no
	 * transaction is active.
	 *
	 * @return ?bool true if a transaction was committed, false if none was active.
	 */
	public function commit(): ?bool;

	/**
	 * Rolls back the currently active transaction on this connection.
	 *
	 * A convenience method for cases where the caller does not hold a reference
	 * to the transaction object. Returns false (and is a no-op) when no
	 * transaction is active.
	 *
	 * @return ?bool true if a transaction was rolled back, false if none was active.
	 */
	public function rollback(): ?bool;

	/**
	 * Returns the ID of the last inserted row or sequence value.
	 *
	 * For SQL/PDO drivers this wraps `PDO::lastInsertId()`.  Non-SQL drivers
	 * should return the equivalent last-insert identifier for their store, or
	 * an empty string if the concept does not apply.
	 *
	 * @param string $sequenceName name of the sequence object (required by some DBMS).
	 * @return string the row ID of the last inserted row, or the last value retrieved
	 *   from the sequence object.
	 */
	public function getLastInsertID($sequenceName = '');

	/**
	 * Returns the metadata helper for this connection.
	 *
	 * The metadata object provides schema introspection (table and column info)
	 * and identifier quoting.  For SQL connections this returns the appropriate
	 * {@see \Prado\Data\Common\TDbMetaData} subclass for the active driver.
	 *
	 * @return IDataMetaData the metadata helper for this connection.
	 */
	public function getDbMetaData();

	// -------------------------------------------------------------------------
	// SQL/PDO-oriented methods.
	// SQL drivers implement these fully.  Non-SQL drivers should provide no-op
	// stubs (return null, empty string, or a sensible default) for any method
	// that does not apply to their underlying store.
	// -------------------------------------------------------------------------

	/**
	 * Returns the connection string (DSN) used to open this connection.
	 *
	 * Non-SQL drivers that do not use a DSN may return an empty string or a
	 * driver-defined connection descriptor.
	 *
	 * @return string the connection string / DSN.
	 */
	public function getConnectionString();

	/**
	 * Quotes a string for safe inclusion in a SQL query.
	 *
	 * Wraps the underlying driver's quoting function (e.g. PDO::quote for SQL
	 * drivers).  The connection must be open before calling this method.
	 * Non-SQL drivers should return the string unmodified.
	 *
	 * @param string $str the string to quote.
	 * @return string the properly quoted string.
	 */
	public function quoteString($str);

	/**
	 * Returns the current column-name case mode for this connection.
	 *
	 * Wraps PDO::ATTR_CASE for SQL drivers.  Returns a
	 * {@see \Prado\Data\TDbColumnCaseMode} value.  Non-SQL drivers may return
	 * null or a driver-defined default.
	 *
	 * @return mixed the current column case mode (TDbColumnCaseMode enum value).
	 */
	public function getColumnCase();

	/**
	 * Sets the column-name case mode for this connection.
	 *
	 * Wraps PDO::ATTR_CASE for SQL drivers.  Accepts a
	 * {@see \Prado\Data\TDbColumnCaseMode} value.  Non-SQL drivers may no-op
	 * this method.
	 *
	 * @param mixed $value the column case mode (TDbColumnCaseMode enum value).
	 */
	public function setColumnCase($value);

	/**
	 * Returns the value of a driver connection attribute.
	 *
	 * For SQL drivers the attribute name is a PDO attribute constant
	 * (e.g. PDO::ATTR_CASE).  Non-SQL drivers may return null for unknown
	 * attribute names.
	 *
	 * @param int $name the attribute identifier.
	 * @return mixed the attribute value, or null if not supported.
	 */
	public function getAttribute($name);

	/**
	 * Sets a driver connection attribute.
	 *
	 * For SQL drivers the attribute name is a PDO attribute constant
	 * (e.g. PDO::ATTR_CASE).  Non-SQL drivers may no-op this method.
	 *
	 * @param int $name the attribute identifier.
	 * @param mixed $value the attribute value to set.
	 */
	public function setAttribute($name, $value);

}
