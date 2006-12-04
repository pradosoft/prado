<?php

Prado::using('System.Data.ActiveRecord.TActiveRecord');
require_once(dirname(__FILE__).'/records/DepartmentRecord.php');

class CountRecordsTestCase extends UnitTestCase
{
	function setup()
	{
		$conn = new TDbConnection('pgsql:host=localhost;dbname=test', 'test','test');
		TActiveRecordManager::getInstance()->setDbConnection($conn);
	}

	function test_count()
	{
		$finder = DepartmentRecord::finder();
		$count = $finder->count('"order" > ?', 2);
		$this->assertTrue($count > 0);
	}

	function test_count_zero()
	{
		$finder = DepartmentRecord::finder();
		$count = $finder->count('"order" > ?', 11);
		$this->assertEqual($count,0);
	}
}

?>