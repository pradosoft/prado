<?php

namespace Prado\Test\Unit\Data\ActiveRecord;

use Prado\Data\ActiveRecord\TActiveRecordCriteria;
use Prado\Test\Unit\Data\ActiveRecord\records\DepartmentRecord;
use Prado\Test\Unit\Harness\Traits\PradoUnitDataConnectionTrait;

class CriteriaTest extends \PHPUnit\Framework\TestCase
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

	public function test_orderby_only()
	{
		$criteria = new TActiveRecordCriteria;
		$criteria->OrdersBy['name'] = 'asc';
		$records = DepartmentRecord::finder()->findAll($criteria);
		$this->assertEquals(count($records), 8);
		$this->assertEquals($records[0]->name, '+GX Service');
		$this->assertEquals($records[7]->name, 'Services');
	}

	public function test_orderby_only_desc()
	{
		$criteria = new TActiveRecordCriteria;
		$criteria->OrdersBy['name'] = 'desc';
		$records = DepartmentRecord::finder()->findAll($criteria);
		$this->assertEquals(count($records), 8);
		$this->assertEquals($records[7]->name, '+GX Service');
		$this->assertEquals($records[0]->name, 'Services');
	}

	public function test_criteria_parameters()
	{
		$criteria = new TActiveRecordCriteria('sql', "One", "two", 3);
		$expect = ["One", "two", 3];
		$this->assertEquals($criteria->getParameters()->toArray(), $expect);
	}

	public function test_criteria_parameters_array()
	{
		$expect = ["One", "two", 3];
		$criteria = new TActiveRecordCriteria('sql', $expect);
		$this->assertEquals($criteria->getParameters()->toArray(), $expect);
	}
}
