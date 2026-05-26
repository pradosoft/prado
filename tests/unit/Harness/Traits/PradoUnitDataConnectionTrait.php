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


