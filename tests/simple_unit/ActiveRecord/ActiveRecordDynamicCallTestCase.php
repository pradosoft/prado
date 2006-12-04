<?php

Prado::using('System.Data.ActiveRecord.TActiveRecord');
require_once(dirname(__FILE__).'/records/DepartmentRecord.php');
require_once(dirname(__FILE__).'/records/DepSections.php');

class ActiveRecordDynamicCallTestCase extends UnitTestCase
{
	function setup()
	{
		$conn = new TDbConnection('pgsql:host=localhost;dbname=test', 'test','test');
		TActiveRecordManager::getInstance()->setDbConnection($conn);
	}

	function test_dynamic_call()
	{
		$finder = DepartmentRecord::finder();
		$r2 = $finder->findByName('Facilities');
		$this->assertNotNull($r2);
	}

	function test_dynamic_multiple_field_call()
	{
		$finder = DepartmentRecord::finder();
		$rs = $finder->findByNameAndActive('Marketing',true);
		$this->assertNotNull($rs);
	}

	function test_dynamic_call_missing_parameters_throws_exception()
	{
		$finder = DepartmentRecord::finder();
		try
		{
			$rs = $finder->findByNameAndActive('Marketing');
			$this->fail();
		}
		catch(TActiveRecordException $e)
		{
			$this->pass();
		}
	}

	function test_dynamic_call_extras_parameters_ok()
	{
		$finder = DepartmentRecord::finder();
		$rs = $finder->findByNameAndActive('Marketing',true,true);
		$this->assertNotNull($rs);
	}

}

?>