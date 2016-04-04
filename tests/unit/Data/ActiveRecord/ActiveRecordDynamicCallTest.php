<?php
Prado::using('System.Data.ActiveRecord.TActiveRecord');
require_once(dirname(__FILE__).'/records/DepartmentRecord.php');
require_once(dirname(__FILE__).'/records/DepSections.php');

/**
 * @package System.Data.ActiveRecord
 */
class ActiveRecordDynamicCallTest extends PHPUnit_Framework_TestCase
{
	function setup()
	{
		$conn = new TDbConnection('mysql:host=localhost;dbname=prado_unitest', 'prado_unitest','prado_unitest');
		TActiveRecordManager::getInstance()->setDbConnection($conn);
	}

	function test_multiple_field_and_or()
	{
		$finder = DepartmentRecord::finder();
		$r2 = $finder->findAllByName_And_Description_Or_Active_Or_Order('Facilities', null, false, 1);
		$this->assertNotNull($r2);
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
		catch(TDbException $e)
		{
			return;
		}
		$this->fail();
	}

	function test_dynamic_call_extras_parameters_ok()
	{
		$finder = DepartmentRecord::finder();
		try
		{
			$rs = $finder->findByNameAndActive('Marketing',true,true);
			$this->fail();
		}
		catch(TDbException $e)
		{
			return;
		}
		$this->fail();
	}

	function test_dynamic_delete_by()
	{
		$finder = DepartmentRecord::finder();
		//$finder->RecordManager->OnDelete[] = array($this, 'assertDeleteSql');
		$this->assertEquals($finder->deleteByName('tasds'), 0);
	}

	function assertDeleteSql($sender, $param)
	{
	}
}
