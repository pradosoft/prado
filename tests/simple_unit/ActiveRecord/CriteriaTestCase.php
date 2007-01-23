<?php

Prado::using('System.Data.ActiveRecord.TActiveRecord');
require_once(dirname(__FILE__).'/records/DepartmentRecord.php');
require_once(dirname(__FILE__).'/records/DepSections.php');

class CriteriaTestCase extends UnitTestCase
{
	function setup()
	{
		$conn = new TDbConnection('pgsql:host=localhost;dbname=test', 'test','test');
		TActiveRecordManager::getInstance()->setDbConnection($conn);
	}

	function test_orderby_only()
	{
		$criteria = new TActiveRecordCriteria;
		$criteria->OrdersBy['name'] = 'asc';
		$records = DepartmentRecord::finder()->findAll($criteria);
		$this->assertEqual(count($records), 8);
		$this->assertEqual($records[0]->name, '+GX Service');
		$this->assertEqual($records[7]->name, 'Marketing');
	}

	function test_orderby_only_desc()
	{
		$criteria = new TActiveRecordCriteria;
		$criteria->OrdersBy['name'] = 'desc';
		$records = DepartmentRecord::finder()->findAll($criteria);
		$this->assertEqual(count($records), 8);
		$this->assertEqual($records[7]->name, '+GX Service');
		$this->assertEqual($records[0]->name, 'Marketing');
	}

	function test_criteria_parameters()
	{
		$criteria = new TActiveRecordCriteria('sql', "One", "two", 3);
		$expect = array("One", "two", 3);
		$this->assertEqual($criteria->getParameters()->toArray(), $expect);
	}

	function test_criteria_parameters_array()
	{
		$expect = array("One", "two", 3);
		$criteria = new TActiveRecordCriteria('sql', $expect);
		$this->assertEqual($criteria->getParameters()->toArray(), $expect);
	}
}

?>