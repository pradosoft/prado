<?php

require_once(__DIR__ . '/../../PradoUnit.php');
require_once(__DIR__ . '/records/DepartmentRecord.php');
require_once(__DIR__ . '/records/DepSections.php');

class DeleteByPkTest extends PHPUnit\Framework\TestCase
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

	public function test_delete_by_pks()
	{
		$finder = DepartmentRecord::finder();
		$this->assertEquals($finder->deleteByPk(100), 0);
		$this->assertEquals($finder->deleteByPk(100, 101), 0);
		$this->assertEquals($finder->deleteByPk([100, 101]), 0);
	}

	public function test_delete_by_composite_pks()
	{
		$finder = DepSections::finder();
		$this->assertEquals($finder->deleteByPk([100, 101]), 0);
		$this->assertEquals($finder->deleteByPk([100, 101], [102, 103]), 0);
		$this->assertEquals($finder->deleteByPk([[100, 101], [102, 103]]), 0);
	}
}
