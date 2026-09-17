<?php

namespace Prado\Test\Unit\Data\ActiveRecord;

use Prado\Test\Unit\Data\ActiveRecord\Records\DepartmentRecord;
use Prado\Test\Unit\Data\ActiveRecord\Records\UserRecord;
use Prado\Test\Unit\Harness\Traits\PradoUnitDataConnectionTrait;
use Prado\Data\ActiveRecord\TActiveRecord;

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
