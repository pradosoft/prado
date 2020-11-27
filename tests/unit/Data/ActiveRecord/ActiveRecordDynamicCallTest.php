<?php

require_once(__DIR__ . '/records/DepartmentRecord.php');
require_once(__DIR__ . '/records/DepSections.php');

class ActiveRecordDynamicCallTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
		$conn = new TDbConnection('mysql:host=localhost;dbname=prado_unitest', 'prado_unitest', 'prado_unitest');
		TActiveRecordManager::getInstance()->setDbConnection($conn);
	}

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
		self::expectException('Prado\\Exceptions\\TDbException');
		$rs = $finder->findByNameAndActive('Marketing');
	}

	public function test_dynamic_call_extras_parameters_ok()
	{
		$finder = DepartmentRecord::finder();
		self::expectException('Prado\\Exceptions\\TDbException');
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
