<?php

Prado::using('System.Data.ActiveRecord.TActiveRecord');
require_once(dirname(__FILE__).'/records/DepartmentRecord.php');
require_once(dirname(__FILE__).'/records/DepSections.php');

/**
 * @package System.Data.ActiveRecord
 */
class CriteriaTest extends PHPUnit_Framework_TestCase
{
	function setup()
	{
		$conn = new TDbConnection('mysql:host=localhost;dbname=prado_unitest', 'prado_unitest','prado_unitest');
		TActiveRecordManager::getInstance()->setDbConnection($conn);
	}

	function test_orderby_only()
	{
		$criteria = new TActiveRecordCriteria;
		$criteria->OrdersBy['name'] = 'asc';
		$records = DepartmentRecord::finder()->findAll($criteria);
		$this->assertEquals(count($records), 8);
		$this->assertEquals($records[0]->name, '+GX Service');
		$this->assertEquals($records[7]->name, 'Services');
	}

	function test_orderby_only_desc()
	{
		$criteria = new TActiveRecordCriteria;
		$criteria->OrdersBy['name'] = 'desc';
		$records = DepartmentRecord::finder()->findAll($criteria);
		$this->assertEquals(count($records), 8);
		$this->assertEquals($records[7]->name, '+GX Service');
		$this->assertEquals($records[0]->name, 'Services');
	}

	function test_criteria_parameters()
	{
		$criteria = new TActiveRecordCriteria('sql', "One", "two", 3);
		$expect = array("One", "two", 3);
		$this->assertEquals($criteria->getParameters()->toArray(), $expect);
	}

	function test_criteria_parameters_array()
	{
		$expect = array("One", "two", 3);
		$criteria = new TActiveRecordCriteria('sql', $expect);
		$this->assertEquals($criteria->getParameters()->toArray(), $expect);
	}
}
