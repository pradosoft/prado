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

	function test_find_by_sql_arb()
	{
		$sql = 'SELECT c.name as category, i.name as item
			FROM items i, categories c
			WHERE i.category_id = c.category_id LIMIT 2';
		$items = TActiveRecord::finder('SqlTest')->findBySql($sql);

		$sql = "SELECT users.*, 'hello' as another_value FROM users LIMIT 2";
		$users = TActiveRecord::finder('UserRecord2')->findBySql($sql);
		var_dump($users);
	}
}

?>