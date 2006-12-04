<?php

Prado::using('System.Data.ActiveRecord.TActiveRecord');
require_once(dirname(__FILE__).'/records/DepartmentRecord.php');

class FindBySqlTestCase extends UnitTestCase
{
	function setup()
	{
		$conn = new TDbConnection('pgsql:host=localhost;dbname=test', 'test','test');
		TActiveRecordManager::getInstance()->setDbConnection($conn);
	}

	function test_find_by_sql()
	{
		$deps = DepartmentRecord::finder()->findBySql('SELECT * FROM departments');
		$this->assertTrue(count($deps) > 0);
	}
}

?>