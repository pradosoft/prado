<?php

Prado::using('System.Data.ActiveRecord.TActiveRecord');
require_once(dirname(__FILE__).'/records/DepartmentRecord.php');
require_once(dirname(__FILE__).'/records/DepSections.php');

/**
 * @package System.Data.ActiveRecord
 */
class DeleteByPkTest extends PHPUnit_Framework_TestCase
{
	function setup()
	{
		$conn = new TDbConnection('mysql:host=localhost;dbname=prado_unitest', 'prado_unitest','prado_unitest');
		TActiveRecordManager::getInstance()->setDbConnection($conn);
	}

	function test_delete_by_pks()
	{
		$finder = DepartmentRecord::finder();
		$this->assertEquals($finder->deleteByPk(100),0);
		$this->assertEquals($finder->deleteByPk(100, 101),0);
		$this->assertEquals($finder->deleteByPk(array(100, 101)),0);
	}

	function test_delete_by_composite_pks()
	{
		$finder = DepSections::finder();
		$this->assertEquals($finder->deleteByPk(array(100,101)),0);
		$this->assertEquals($finder->deleteByPk(array(100, 101), array(102, 103)),0);
		$this->assertEquals($finder->deleteByPk(array(array(100, 101), array(102, 103))),0);
	}
}