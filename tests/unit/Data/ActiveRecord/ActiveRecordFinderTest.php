<?php

require_once(__DIR__ . '/records/DepartmentRecord.php');

class ActiveRecordFinderTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
		$conn = new TDbConnection('mysql:host=localhost;dbname=prado_unitest', 'prado_unitest', 'prado_unitest');
		TActiveRecordManager::getInstance()->setDbConnection($conn);
	}

	public function testFindBySQL_returns_one_record()
	{
		$department = DepartmentRecord::finder()->find('department_id < ?', 5);
		$this->assertNotNull($department);
	}

	public function testFindBySQL_returns_zero_records()
	{
		$department = DepartmentRecord::finder()->find('department_id > ?', 15);
		$this->assertNull($department);
	}

	public function test_find_by_sql_returns_iterator()
	{
		$deps = DepartmentRecord::finder()->findAll('department_id < :id', ['id' => 5]);
		$this->assertEquals(count($deps), 4);
	}

	public function test_find_by_multiple_parameters()
	{
		$department = DepartmentRecord::finder()->find('department_id < ? AND `order` > ?', 5, 2);
		$this->assertNotNull($department);
	}

	public function test_find_by_array_parameter()
	{
		$department = DepartmentRecord::finder()->find('department_id < ? AND `order` > ?', [5, 2]);
		$this->assertNotNull($department);
	}
}
