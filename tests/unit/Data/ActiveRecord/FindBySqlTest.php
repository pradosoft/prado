<?php

require_once(__DIR__ . '/../../PradoUnit.php');
require_once(__DIR__ . '/records/DepartmentRecord.php');
require_once(__DIR__ . '/records/UserRecord.php');

class UserRecord2 extends UserRecord
{
	public $another_value;
}

class SqlTest extends TActiveRecord
{
	public $category;
	public $item;

	const TABLE = 'items';
}

class FindBySqlTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
		try {
			$conn = new TDbConnection('mysql:host=localhost;dbname=prado_unitest', 'prado_unitest', 'prado_unitest');
			$conn->setActive(true);
			TActiveRecordManager::getInstance()->setDbConnection($conn);
		} catch(\Exception $e) {
			if (!PradoUnit::skipDatabaseTests()) {
				throw $e;
			}
			$this->markTestSkipped('Env set PRADO_UNITTEST_SKIP_DB=1 - skip for missing database connection.');
		}
	}

	public function test_find_by_sql()
	{
		$deps = DepartmentRecord::finder()->findBySql('SELECT * FROM departments');
		$this->assertNotNull($deps);
	}
}
