<?php
Prado::using('System.Data.ActiveRecord.TActiveRecord');
require_once(dirname(__FILE__).'/records/DepartmentRecord.php');

class ActiveRecordFinderTestCase extends UnitTestCase
{
	function setup()
	{
		$conn = new TDbConnection('pgsql:host=localhost;dbname=test', 'test','test');
		TActiveRecordManager::getInstance()->setDbConnection($conn);
	}

	function testFindBySQL_returns_one_record()
	{
		$department = DepartmentRecord::finder()->find('department_id < ?', 5);
		$this->assertNotNull($department);
	}

	function testFindBySQL_returns_zero_records()
	{
		$department = DepartmentRecord::finder()->find('department_id > ?', 15);
		$this->assertNull($department);
	}

	function test_find_by_sql_returns_iterator()
	{
		$deps = DepartmentRecord::finder()->findAll('department_id < :id', array('id'=>5));
		$this->assertEqual(count($deps),4);
	}

	function test_find_by_multiple_parameters()
	{
		$department = DepartmentRecord::finder()->find('department_id < ? AND "order" > ?', 5,2);
		$this->assertNotNull($department);
	}

	function test_find_by_array_parameter()
	{
		$department = DepartmentRecord::finder()->find('department_id < ? AND "order" > ?', array(5,2));
		$this->assertNotNull($department);
	}

}

?>