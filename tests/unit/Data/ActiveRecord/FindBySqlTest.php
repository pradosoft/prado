<?php

namespace Prado\Test\Unit\Data\ActiveRecord;

use Prado\Test\Unit\Data\ActiveRecord\records\DepartmentRecord;
use Prado\Test\Unit\Harness\Traits\PradoUnitDataConnectionTrait;

class FindBySqlTest extends \PHPUnit\Framework\TestCase
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

	public function test_find_by_sql()
	{
		$deps = DepartmentRecord::finder()->findBySql('SELECT * FROM departments');
		$this->assertNotNull($deps);
	}
}
