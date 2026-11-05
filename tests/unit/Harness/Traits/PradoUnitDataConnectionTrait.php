<?php

/**
 * PradoUnit class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Traits;

use Prado\Test\Unit\PradoUnit;
use Prado\Data\TDbConnection;
use Prado\Data\TDbDriver;

/**
 * PradoUnitDataConnectionTrait class
 *
 * This trait has the common features of Data test classes.
 *
 * This routes duplicate errors for database connection and table existence for tests.
 *
 * Test classes override these hook methods:
 *
 * - {@see getDbDriver()} — the override point for driver-specific test classes.
 *   It returns a {@see TDbDriver} constant (e.g. `TDbDriver::DRIVER_PGSQL`), and
 *   {@see getPradoUnitSetup()} and {@see getDatabaseName()} derive from it.  The
 *   default is `null`, which selects MySQL.
 *
 * - {@see getPradoUnitSetup()} — the `PradoUnit` factory method to call
 *   (e.g. `'setupFirebirdConnection'`).  Override it only when a factory method
 *   cannot be expressed through a driver constant.
 *
 * - {@see getDatabaseName()} — the database name passed to the factory method.  It
 *   is null for Firebird, Oracle, and IBM DB2, whose databases are a file path or
 *   service name, to have a direct connection without a specific database, and
 *   `'prado_unitest'` for all others.
 *
 * - {@see getTestTables()} — the array of tables that the test uses.  The generic
 *   setUp in the trait checks for each of them.
 *
 * {@see getIsForActiveRecord()} specifies if {@see TActiveRecordManager}.DbConnection
 * should be set the newly created connection.
 *
 * @todo generalize Exception groups.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
trait PradoUnitDataConnectionTrait
{
	protected function setUpConnection(): ?TDbConnection
	{
		$unitSetup = $this->getPradoUnitSetup();
		if (empty($unitSetup)) {
			return null;
		}
		$conn = PradoUnit::$unitSetup($this->getDatabaseName(), $this->getIsForActiveRecord());
		if (is_array($conn)) {
			// The 4.3 unit suite runs every driver's tests in one job; a driver
			// whose PDO extension is not installed is skipped, not failed.
			if (str_starts_with($conn[0], 'extension:')) {
				$this->markTestSkipped($conn[1]);
			}
			PradoUnit::failFirstThenSkip($this, $conn[0], null, $conn[1]);
			return null;
		} elseif ($conn instanceof \Exception) {
			throw $conn;
		} elseif ($conn instanceof TDbConnection) {
			foreach ($this->getTestTables() as $tableName) {
				$tableResult = PradoUnit::checkForTable($conn, $tableName);
				if (is_array($tableResult)) {
					PradoUnit::failFirstThenSkip($this, $tableResult[0], null, $tableResult[1]);
					return null;
				} elseif ($tableResult instanceof \Exception) {
					throw $tableResult;
				}
			}
		}
		return $conn;
	}

	/**
	 * Opens a connection through the {@see getPradoUnitSetup()} factory method
	 * without the table checks of {@see setUpConnection()}.
	 * @param ?string $dbName the database name; null uses {@see getDatabaseName()}.
	 * @return null|array{0:string,1:string}|\Exception|TDbConnection the factory result,
	 *   or null when no factory method is configured.
	 * @since 4.4.0
	 */
	protected function setupPradoUnitConnection(?string $dbName = null)
	{
		$unitSetup = $this->getPradoUnitSetup();
		if (empty($unitSetup)) {
			return null;
		}
		return PradoUnit::$unitSetup($dbName ?? $this->getDatabaseName());
	}

	protected function setUp(): void
	{
		$connection = $this->setUpConnection();
	}

	/**
	 * @return ?string the {@see TDbDriver} constant of the test class; null selects MySQL.
	 * @since 4.4.0
	 */
	protected function getDbDriver(): ?string
	{
		return null;
	}

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

	protected function getPradoUnitSetup(): ?string
	{
		switch ($this->getDbDriver()) {
			case TDbDriver::DRIVER_FIREBIRD:
			case TDbDriver::DRIVER_INTERBASE: return 'setupFirebirdConnection';
			case TDbDriver::DRIVER_IBM:       return 'setupIbmConnection';
			case TDbDriver::DRIVER_OCI:       return 'setupOracleConnection';
			case TDbDriver::DRIVER_PGSQL:     return 'setupPgsqlConnection';
			case TDbDriver::DRIVER_SQLITE:    return 'setupSqliteConnection';
			case TDbDriver::DRIVER_SQLSRV:
			case TDbDriver::DRIVER_DBLIB:
			case TDbDriver::EXTENSION_MSSQL:  return 'setupSqlSrvConnection';
			default:                          return 'setupMysqlConnection';
		}
	}

	protected function getTestTables(): array
	{
		return ['prado_unitest'];
	}

	/**
	 * @return bool whether the new connection is set on the {@see TActiveRecordManager}.
	 */
	protected function getIsForActiveRecord(): bool
	{
		return false;
	}
}
