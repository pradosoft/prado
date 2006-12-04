<?php

Prado::using('System.Data.ActiveRecord.TActiveRecord');
require_once(dirname(__FILE__).'/records/DepartmentRecord.php');
require_once(dirname(__FILE__).'/records/DepSections.php');

class DeleteByPkTestCase extends UnitTestCase
{
	function setup()
	{
		$conn = new TDbConnection('pgsql:host=localhost;dbname=test', 'test','test');
		TActiveRecordManager::getInstance()->setDbConnection($conn);
	}

	function test_delete_by_pks()
	{
		$finder = DepartmentRecord::finder();
		$this->assertEqual($finder->deleteByPk(100),0);
		$this->assertEqual($finder->deleteByPk(100, 101),0);
		$this->assertEqual($finder->deleteByPk(array(100, 101)),0);
	}

	function test_delete_by_composite_pks()
	{
		$finder = DepSections::finder();
		$this->assertEqual($finder->deleteByPk(array(100,101)),0);
		$this->assertEqual($finder->deleteByPk(array(100, 101), array(102, 103)),0);
		$this->assertEqual($finder->deleteByPk(array(array(100, 101), array(102, 103))),0);
	}
}
?>