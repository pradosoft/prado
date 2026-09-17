<?php

namespace Prado\Test\Unit\Data\ActiveRecord;

use Prado\Test\Unit\Data\ActiveRecord\Records\DepartmentRecord;
use Prado\Test\Unit\Data\ActiveRecord\Records\UserRecord;
use Prado\Test\Unit\Harness\Traits\PradoUnitDataConnectionTrait;

class RecordEventTest extends \PHPUnit\Framework\TestCase
{
	use PradoUnitDataConnectionTrait;
	
	protected function getIsForActiveRecord(): bool
	{
		return true;
	}
	
	protected function getTestTables(): array
	{
		return [DepartmentRecord::TABLE];
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
