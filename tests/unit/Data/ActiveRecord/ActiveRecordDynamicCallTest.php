<?php

require_once(__DIR__ . '/../../PradoUnit.php');
require_once(__DIR__ . '/records/DepartmentRecord.php');
require_once(__DIR__ . '/records/DepSections.php');

class ActiveRecordDynamicCallTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;
	
	protected function getIsForActiveRecord(): bool
	{
		return true;
	}
	
	protected function getTestTables(): array
	{
		return [DepartmentRecord::TABLE];
	}
	
	
	//	------- Tests
	/*
	protected function setUp(): void
	{
		$conn = PradoUnit::setupMysqlConnection('prado_unitest', true);
		if (is_string($conn)) {
			$this->markTestSkipped($conn);
		} elseif ($conn instanceof \Exception) {
			throw $conn;
		} elseif ($conn instanceof TDbConnection) {
			$tableException = PradoUnit::checkForTable($conn, DepartmentRecord::TABLE);
			if (is_string($tableException)) {
				$this->markTestSkipped($tableException);
			} elseif ($tableException instanceof \Exception) {
				throw $tableException;
			}
		}
		
		

		try {
			$conn = new TDbConnection('mysql:host=localhost;dbname=prado_unitest', 'prado_unitest', 'prado_unitest');
			$conn->setActive(true);
			TActiveRecordManager::getInstance()->setDbConnection($conn);
		} catch(\Exception $e) {
			if (!PradoUnit::skipDatabaseTests()) {
				throw $e;
			}
			$this->markTestSkipped('Env set PRADO_UNITTEST_SKIP_DB=1 - skip for missing database connection.');
		}
		
		
	} */

	public function test_multiple_field_and_or()
	{
		$finder = DepartmentRecord::finder();
		$r2 = $finder->findAllByName_And_Description_Or_Active_Or_Order('Facilities', null, false, 1);
		$this->assertNotNull($r2);
	}

	public function test_dynamic_call()
	{
		$finder = DepartmentRecord::finder();
		$r2 = $finder->findByName('Facilities');
		$this->assertNotNull($r2);
	}

	public function test_dynamic_multiple_field_call()
	{
		$finder = DepartmentRecord::finder();
		$rs = $finder->findByNameAndActive('Marketing', true);
		$this->assertNotNull($rs);
	}

	public function test_dynamic_call_missing_parameters_throws_exception()
	{
		$finder = DepartmentRecord::finder();
		self::expectException(\Prado\Exceptions\TDbException::class);
		$rs = $finder->findByNameAndActive('Marketing');
	}

	public function test_dynamic_call_extras_parameters_ok()
	{
		$finder = DepartmentRecord::finder();
		self::expectException(\Prado\Exceptions\TDbException::class);
		$rs = $finder->findByNameAndActive('Marketing', true, true);
	}

	public function test_dynamic_delete_by()
	{
		$finder = DepartmentRecord::finder();
		//$finder->RecordManager->OnDelete[] = array($this, 'assertDeleteSql');
		$this->assertEquals($finder->deleteByName('tasds'), 0);
	}

	public function assertDeleteSql($sender, $param)
	{
	}
}
