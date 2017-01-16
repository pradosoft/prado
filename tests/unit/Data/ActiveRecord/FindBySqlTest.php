<?php
Prado::using('System.Data.ActiveRecord.TActiveRecord');
require_once(dirname(__FILE__).'/records/DepartmentRecord.php');
require_once(dirname(__FILE__).'/records/UserRecord.php');

class UserRecord2 extends UserRecord
{
	public $another_value;
}

class SqlTest extends TActiveRecord
{
	public $category;
	public $item;

	const TABLE='items';
}

/**
 * @package System.Data.ActiveRecord
 */
class FindBySqlTest extends PHPUnit_Framework_TestCase
{
	function setup()
	{
		$conn = new TDbConnection('mysql:host=localhost;dbname=prado_unitest', 'prado_unitest','prado_unitest');
		TActiveRecordManager::getInstance()->setDbConnection($conn);
	}

	function test_find_by_sql()
	{
		$deps = DepartmentRecord::finder()->findBySql('SELECT * FROM departments');
		$this->assertTrue(count($deps) > 0);
	}
}
