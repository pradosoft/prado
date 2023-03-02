<?php

require_once(__DIR__ . '/records/DepartmentRecord.php');
require_once(__DIR__ . '/records/DepSections.php');

class DeleteByPkTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
		$conn = new TDbConnection('mysql:host=localhost;dbname=prado_unitest', 'prado_unitest', 'prado_unitest');
		TActiveRecordManager::getInstance()->setDbConnection($conn);
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
