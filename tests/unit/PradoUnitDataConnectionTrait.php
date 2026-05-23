<?php

/**
 * PradoUnitDataConnectionTrait trait file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

// No namespace — test infrastructure lives outside the Prado\ hierarchy.
// This file is loaded by PradoUnitRequires.php; do not add require_once here.

/**
 * PradoUnitDataConnectionTrait
 *
 * Shared database-connection boilerplate for data-layer test classes.
 *
 * Provides a ready-made {@see setUpConnection()} implementation and sensible
 * driver-aware defaults for the hook methods that test classes may override:
 *
 * - {@see getDbDriver()} — **the single override point** for driver-specific test
 *   classes.  Returns a {@see TDbDriver} constant (e.g. `TDbDriver::DRIVER_PGSQL`).
 *   The default returns `null` (no-op / skip DB setup).  The testsuite folder
 *   already encodes the driver, so no environment variable is needed — each class
 *   simply declares its own driver.  All other methods derive from this one.
 *
 * - {@see getPradoUnitSetup()} — returns the `PradoUnit` factory method to call
 *   (e.g. `'setupFirebirdConnection'`).  Derived from {@see getDbDriver()};
 *   override only when a factory-method name cannot be expressed through a driver
 *   constant (rare).
 *
 * - {@see getDatabaseName()} — returns the logical database / schema name passed to
 *   the factory method.  Derived from {@see getPradoUnitSetup()}.  Returns `null`
 *   for Firebird, Oracle, and IBM DB2 (file-path / service-name databases) and
 *   `'prado_unitest'` for all others.  Override only for non-standard schema names.
 *
 * - {@see getIsForActiveRecord()} — when `true`, the opened connection is registered
 *   with {@see TActiveRecordManager} as its default connection.
 *
 * - {@see getTestTables()} — returns table names that must exist before any test method
 *   runs.  The default is an empty array; override to list tables required by the class.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
trait PradoUnitDataConnectionTrait
{
	/**
	 * Establishes and validates a database connection for the current test class.
	 *
	 * Called from {@see setUp()} before each test method.  Returns the live
	 * {@see TDbConnection} on success, or `null` when no driver setup method is
	 * configured ({@see getPradoUnitSetup()} returns an empty value).
	 *
	 * ## Connection phase
	 *
	 * Delegates to the static `PradoUnit::{getPradoUnitSetup()}` method, passing
	 * {@see getDatabaseName()} and {@see getIsForActiveRecord()} as arguments.
	 * That method returns one of three types:
	 *
	 * - **`string`** — the driver or database is unavailable in the current
	 *   environment (e.g. the PDO extension is not loaded, or the server refused
	 *   the connection while `PRADO_UNITTEST_SKIP_DB=1` is set).  The string
	 *   carries a human-readable reason and this method forwards it directly to
	 *   {@see \PHPUnit\Framework\TestCase::markTestSkipped()}, which aborts the
	 *   test as skipped.
	 *
	 * - **`\Exception`** — an unexpected failure occurred (e.g. the server is
	 *   reachable but the credentials are wrong, or `PRADO_UNITTEST_SKIP_DB` is
	 *   *not* set and the connection was refused).  The exception is **re-thrown
	 *   intentionally**, causing the test to fail with an error rather than be
	 *   silently skipped.  This is by design: a loud failure alerts the developer
	 *   that a database they expect to be reachable is not.
	 *
	 * - **`TDbConnection`** — the connection succeeded; the method proceeds to the
	 *   table-validation phase below.
	 *
	 * ## Table-validation phase
	 *
	 * For each table name returned by {@see getTestTables()},
	 * {@see PradoUnit::checkForTable()} is called.  It returns:
	 *
	 * - **`null`** — the table exists; continue.
	 * - **`string`** — the table is missing or inaccessible; the test is marked
	 *   skipped via {@see \PHPUnit\Framework\TestCase::markTestSkipped()}.
	 * - **`\Exception`** — an unexpected error while probing the table; the
	 *   exception is **re-thrown intentionally** for the same reason as above.
	 *
	 * ## Return value
	 *
	 * Returns the validated {@see TDbConnection} (active) after all table checks
	 * pass.  Returns `null` only when {@see getPradoUnitSetup()} is empty —
	 * callers must treat `null` as "no database required" and skip or ignore DB
	 * operations accordingly.
	 *
	 * @return ?TDbConnection The validated database connection, or null if no
	 *                        driver setup method is configured.
	 * @throws \Exception     Re-thrown from {@see PradoUnit::setupXxxConnection()}
	 *                        or {@see PradoUnit::checkForTable()} when an
	 *                        unexpected (non-availability) failure occurs.
	 * @agents Do not edit this method without authorization and providing a reason why.
	 */
	protected function setUpConnection(): ?TDbConnection
	{
		$unitSetup = $this->getPradoUnitSetup();
		if (empty($unitSetup)) {
			return null;
		}
		$conn = PradoUnit::$unitSetup($this->getDatabaseName(), $this->getIsForActiveRecord());
		if (is_string($conn)) {
			$this->markTestSkipped($conn);
		} elseif ($conn instanceof \Exception) {
			throw $conn;
		} elseif ($conn instanceof TDbConnection) {
			foreach ($this->getTestTables() as $tableName) {
				$tableException = PradoUnit::checkForTable($conn, $tableName);
				if (is_string($tableException)) {
					$this->markTestSkipped($tableException);
				} elseif ($tableException instanceof \Exception) {
					throw $tableException;
				}
			}
		}
		return $conn;
	}

	/**
	 * Opens a database connection without PHPUnit auto-skip/rethrow integration.
	 *
	 * Like {@see setUpConnection()}, this method delegates to
	 * `PradoUnit::{getPradoUnitSetup()}`, but it does **not** call
	 * `markTestSkipped()` or rethrow exceptions — it returns whatever the factory
	 * method returns and lets the caller decide how to handle failure.  Use this
	 * when you want to check `$conn instanceof TDbConnection` yourself before
	 * proceeding.
	 *
	 * Unlike `setUpConnection()`, this method also accepts an explicit `$dbName`
	 * override so callers that know the target schema can bypass
	 * {@see getDatabaseName()}.
	 *
	 * Return values follow the same contract as the underlying factory:
	 *
	 * - **`TDbConnection`** — connection succeeded.
	 * - **`string`**        — driver or database unavailable; human-readable reason.
	 * - **`\Exception`**    — unexpected connection failure.
	 * - **`null`**          — no driver setup method is configured
	 *                         ({@see getPradoUnitSetup()} is empty).
	 *
	 * @param string|null $dbName Database/schema name to connect to.  When `null`,
	 *                            falls back to {@see getDatabaseName()}.
	 * @return TDbConnection|string|\Exception|null
	 */
	protected function setupPradoUnitConnection(?string $dbName = null)
	{
		$unitSetup = $this->getPradoUnitSetup();
		if (empty($unitSetup)) {
			return null;
		}
		$database = $dbName ?? $this->getDatabaseName();
		return PradoUnit::$unitSetup($database, $this->getIsForActiveRecord());
	}

	/**
	 * Default setUp — validates the database connection before each test method.
	 *
	 * Calls {@see setUpConnection()} as a gate: if the driver is unavailable the
	 * test is marked skipped; if an unexpected error occurs the exception propagates
	 * as a test error.  The returned connection is not stored here; test classes that
	 * need access to the connection should override this method and store it themselves.
	 */
	protected function setUp(): void
	{
		$this->setUpConnection();
	}

	/**
	 * Returns the PDO driver identifier for the database under test.
	 *
	 * This is the **single point of driver configuration** for a test class.
	 * {@see getPradoUnitSetup()} and {@see getDatabaseName()} both derive their
	 * answers from this method, so overriding it is all that is needed to pin a
	 * driver-specific test class to a particular database — no environment variable
	 * or additional method override is required.
	 *
	 * The default returns `null`, which causes {@see setUpConnection()} to skip
	 * database setup entirely (no-op).  Driver-specific test classes in
	 * `tests/unit/Data/DbSpecific/{Driver}/` override this method to declare their
	 * driver; the testsuite name and folder already encode that information, so no
	 * environment variable is needed.
	 *
	 * ```php
	 * protected function getDbDriver(): ?string
	 * {
	 *     return TDbDriver::DRIVER_SQLSRV;
	 * }
	 * ```
	 *
	 * @return string|null A {@see TDbDriver} constant value, or `null` to skip
	 *                     database setup entirely.
	 */
	protected function getDbDriver(): ?string
	{
		return null;
	}

	/**
	 * Returns the `PradoUnit` factory method name for the current database driver.
	 *
	 * Derived from {@see getDbDriver()} — override that method rather than this one.
	 * Returns `null` when {@see getDbDriver()} returns `null` (or an unrecognised
	 * value), which causes {@see setUpConnection()} to skip database setup.
	 *
	 * | {@see TDbDriver} constant  | Factory method              |
	 * |----------------------------|-----------------------------|
	 * | `DRIVER_FIREBIRD`          | `setupFirebirdConnection`   |
	 * | `DRIVER_INTERBASE`         | `setupFirebirdConnection`   |
	 * | `DRIVER_IBM`               | `setupIbmConnection`        |
	 * | `DRIVER_MYSQL`             | `setupMysqlConnection`      |
	 * | `DRIVER_OCI`               | `setupOracleConnection`     |
	 * | `DRIVER_PGSQL`             | `setupPgsqlConnection`      |
	 * | `DRIVER_SQLITE`            | `setupSqliteConnection`     |
	 * | `DRIVER_SQLSRV`            | `setupSqlSrvConnection`     |
	 * | `DRIVER_DBLIB`             | `setupSqlSrvConnection`     |
	 * | `'mssql'`                  | `setupSqlSrvConnection`     |
	 * | *(null or unrecognised)*   | `null` — no database        |
	 *
	 * @return string|null The `PradoUnit` factory method name, or `null` to skip
	 *                     database setup entirely.
	 */
	protected function getPradoUnitSetup(): ?string
	{
		switch ($this->getDbDriver()) {
			case TDbDriver::DRIVER_FIREBIRD:
			case TDbDriver::DRIVER_INTERBASE: return 'setupFirebirdConnection';
			case TDbDriver::DRIVER_IBM:       return 'setupIbmConnection';
			case TDbDriver::DRIVER_MYSQL:     return 'setupMysqlConnection';
			case TDbDriver::DRIVER_OCI:       return 'setupOracleConnection';
			case TDbDriver::DRIVER_PGSQL:     return 'setupPgsqlConnection';
			case TDbDriver::DRIVER_SQLITE:    return 'setupSqliteConnection';
			case TDbDriver::DRIVER_SQLSRV:
			case TDbDriver::DRIVER_DBLIB:
			case 'mssql':                     return 'setupSqlSrvConnection';
			default:                          return null;
		}
	}

	/**
	 * Returns the logical database / schema name to pass to the connection factory.
	 *
	 * Derived from {@see getPradoUnitSetup()} so driver-specific test classes that
	 * override {@see getDbDriver()} never need to touch this method.
	 *
	 * Returns `null` for Firebird, Oracle, and IBM DB2, whose "database" is a
	 * file path or service name configured entirely through driver-specific
	 * environment variables (`FIREBIRD_DB_PATH`, `ORACLE_SERVICE_NAME`, etc.) and
	 * must not be overridden here.  Returns `'prado_unitest'` for all other drivers.
	 *
	 * @return string|null The database name, or `null` to use the driver default.
	 */
	protected function getDatabaseName(): ?string
	{
		switch ($this->getPradoUnitSetup()) {
			case 'setupFirebirdConnection':
			case 'setupOracleConnection':
			case 'setupIbmConnection':
				return null;
			default:
				return 'prado_unitest';
		}
	}

	/**
	 * Returns whether the opened connection should be registered with
	 * {@see TActiveRecordManager} as its default connection.
	 *
	 * @return bool `true` to register the connection for Active Record use.
	 */
	protected function getIsForActiveRecord(): bool
	{
		return false;
	}

	/**
	 * Returns the table names that must exist in the database before any test
	 * method runs.  {@see setUpConnection()} calls {@see PradoUnit::checkForTable()}
	 * for each name and marks the test skipped (or re-throws) if a table is missing.
	 *
	 * The default is `['address']` — this table is present in every driver's
	 * initialisation script (`tests/initdb_*.sql`) and serves as a universal
	 * sentinel that the database has been properly seeded.  Override in test
	 * classes that require a different or broader set of tables.
	 *
	 * @return string[] Unquoted table names to probe.
	 */
	protected function getTestTables(): array
	{
		return ['address'];
	}
}
