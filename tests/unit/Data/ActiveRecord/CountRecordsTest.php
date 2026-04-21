<?php

require_once(__DIR__ . '/../../PradoUnit.php');
require_once(__DIR__ . '/records/DepartmentRecord.php');

class CountRecordsTest extends PHPUnit\Framework\TestCase
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

	public function test_count()
	{
		$finder = DepartmentRecord::finder();
		$count = $finder->count('`order` > ?', 2);
		$this->assertTrue($count > 0);
	}

	public function test_count_zero()
	{
		$finder = DepartmentRecord::finder();
		$count = $finder->count('`order` > ?', 11);
		$this->assertEquals($count, 0);
	}

	public function test_count_without_parameter()
	{
		$finder = DepartmentRecord::finder();
		$this->assertEquals($finder->count(), 8);
	}
}
