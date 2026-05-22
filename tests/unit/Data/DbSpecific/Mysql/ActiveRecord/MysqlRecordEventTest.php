<?php

require_once(__DIR__ . '/../../../../PradoUnit.php');
require_once(__DIR__ . '/records/DepartmentRecord.php');
require_once(__DIR__ . '/records/UserRecord.php');

class MysqlRecordEventTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;

	protected function getDbDriver(): ?string
	{
		return TDbDriver::DRIVER_MYSQL;
	}

	protected function getDatabaseName(): ?string
	{
		return 'prado_unitest';
	}

	protected function getIsForActiveRecord(): bool
	{
		return true;
	}

	protected function getTestTables(): array
	{
		return [DepartmentRecord::TABLE, UserRecord::TABLE];
	}

	protected function setUp(): void
	{
		$this->setUpConnection();
	}


	//	------- Tests

	public function testFindByPk()
	{
		$user1 = UserRecord::finder()->findByPk('admin');
		$this->assertNotNull($user1);
	}

	public function logger($sender, $param)
	{
		//var_dump($param);
	}
}
