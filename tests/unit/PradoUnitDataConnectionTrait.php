<?php

/**
 * PradoUnit class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */
 
 // No Namespace for unit tests, separate from the system
 require_once('PradoUnitDataConnectionTrait.php');
 
 /**
  * PradoUnitDataConnectionTrait class
  *
  * This trait has the common features of Data test classes.
  *
  * This routes duplicate errors for database connection and table existence for tests.
  *
  * {@see getDatabaseName()} is the database name.  it can be set to null to have a 
  * direct connection without a specific database.
  *
  * {@see getIsForActiveRecord()} specifies if {@see TActiveRecordManager}.DbConnection
  *  should be set the newly created connection.
  *
  * {@see getTestTables()} specifies the array of tables that the test uses.  The
  * generic setUp in the trait will check for each of them.
  *
  * @todo generalize Exception groups.
  * @author Brad Anderson <belisoful@icloud.com>
  * @since 4.3.3
  */

trait PradoUnitDataConnectionTrait {
	
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
	protected function setUp(): void
	{
		$connection = $this->setUpConnection();
	}
	
	protected function getDatabaseName(): ?string
	{
		return 'prado_unitest';
	}
	
	protected function getPradoUnitSetup(): ?string
	{
		return 'setupMysqlConnection';
	}
	
	protected function getIsForActiveRecord(): bool
	{
		return false;
	}
	
	protected function getTestTables(): array
	{
		return ['prado_unitest'];
	}
}


