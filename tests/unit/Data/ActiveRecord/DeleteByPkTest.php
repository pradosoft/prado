<?php

namespace Prado\Test\Unit\Data\ActiveRecord;

use Prado\Test\Unit\Data\ActiveRecord\Records\DepSections;
use Prado\Test\Unit\Data\ActiveRecord\Records\DepartmentRecord;
use Prado\Test\Unit\Harness\Traits\PradoUnitDataConnectionTrait;

class DeleteByPkTest extends \PHPUnit\Framework\TestCase
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
