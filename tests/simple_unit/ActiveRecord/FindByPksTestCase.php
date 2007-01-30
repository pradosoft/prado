<?php
Prado::using('System.Data.ActiveRecord.TActiveRecord');
require_once(dirname(__FILE__).'/records/DepartmentRecord.php');
require_once(dirname(__FILE__).'/records/DepSections.php');

class FindByPksTestCase extends UnitTestCase
{
	function setup()
	{
		$conn = new TDbConnection('pgsql:host=localhost;dbname=test', 'test','test');
		TActiveRecordManager::getInstance()->setDbConnection($conn);
	}

	function test_find_by_1pk()
	{
		$dep = DepartmentRecord::finder()->findByPk(1);
		$this->assertNotNull($dep);
		$this->assertEqual($dep->department_id, 1);
	}

	function test_find_by_1pk_array()
	{
		$dep = DepartmentRecord::finder()->findByPk(array(1));
		$this->assertNotNull($dep);
		$this->assertEqual($dep->department_id, 1);
	}

	function test_find_by_pks()
	{
		$deps = DepartmentRecord::finder()->findAllByPks(1,2,4);
		$this->assertEqual(count($deps), 3);

		$this->assertEqual($deps[0]->department_id, 1);
		$this->assertEqual($deps[1]->department_id, 2);
		$this->assertEqual($deps[2]->department_id, 4);
	}

	function test_find_by_pks_with_invalid()
	{
		$deps = DepartmentRecord::finder()->findAllByPks(4,2,14);
		$this->assertEqual(count($deps), 2);

		$this->assertEqual($deps[0]->department_id, 2);
		$this->assertEqual($deps[1]->department_id, 4);
	}

	function test_find_by_composite_pks()
	{
		$ds = DepSections::finder()->findAllByPks(array(1,1), array(2,5));
		$this->assertEqual(count($ds), 2);

		$this->assertIsDepSection($ds[0], 1, 1);
		$this->assertIsDepSection($ds[1], 2, 5);
	}

	function assertIsDepSection($dep, $dep_id, $sec_id)
	{
		$this->assertTrue($dep instanceof DepSections);
		$this->assertEqual($dep->department_id, $dep_id);
		$this->assertEqual($dep->section_id, $sec_id);
	}
}

?>