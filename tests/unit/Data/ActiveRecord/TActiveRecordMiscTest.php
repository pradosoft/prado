<?php

require_once(__DIR__ . '/../../PradoUnit.php');

use Prado\Data\ActiveRecord\TActiveRecordChangeEventParameter;
use Prado\Data\ActiveRecord\TActiveRecordCriteria;
use Prado\Data\ActiveRecord\TActiveRecordInvalidFinderResult;
use Prado\Data\DataGateway\TSqlCriteria;

class TActiveRecordMiscTest extends PHPUnit\Framework\TestCase
{
	// -------  TActiveRecordChangeEventParameter  -------

	public function test_is_valid_defaults_to_true()
	{
		$param = new TActiveRecordChangeEventParameter();
		$this->assertTrue($param->getIsValid());
	}

	public function test_set_is_valid_false()
	{
		$param = new TActiveRecordChangeEventParameter();
		$param->setIsValid(false);
		$this->assertFalse($param->getIsValid());
	}

	public function test_set_is_valid_true()
	{
		$param = new TActiveRecordChangeEventParameter();
		$param->setIsValid(false);
		$param->setIsValid(true);
		$this->assertTrue($param->getIsValid());
	}

	public function test_set_is_valid_coerces_string_false()
	{
		$param = new TActiveRecordChangeEventParameter();
		$param->setIsValid('false');
		$this->assertFalse($param->getIsValid());
	}

	public function test_set_is_valid_coerces_string_true()
	{
		$param = new TActiveRecordChangeEventParameter();
		$param->setIsValid(false);
		$param->setIsValid('true');
		$this->assertTrue($param->getIsValid());
	}

	public function test_set_is_valid_coerces_integer_zero()
	{
		$param = new TActiveRecordChangeEventParameter();
		$param->setIsValid(0);
		$this->assertFalse($param->getIsValid());
	}

	public function test_set_is_valid_coerces_integer_one()
	{
		$param = new TActiveRecordChangeEventParameter();
		$param->setIsValid(false);
		$param->setIsValid(1);
		$this->assertTrue($param->getIsValid());
	}

	public function test_change_param_is_teventparameter()
	{
		$param = new TActiveRecordChangeEventParameter();
		$this->assertInstanceOf(\Prado\TEventParameter::class, $param);
	}

	// -------  TActiveRecordInvalidFinderResult  -------

	public function test_null_constant()
	{
		$this->assertSame('Null', TActiveRecordInvalidFinderResult::Null);
	}

	public function test_exception_constant()
	{
		$this->assertSame('Exception', TActiveRecordInvalidFinderResult::Exception);
	}

	public function test_constants_via_reflection()
	{
		$ref = new ReflectionClass(TActiveRecordInvalidFinderResult::class);
		$values = array_values($ref->getConstants());
		$this->assertCount(2, $values);
		$this->assertContains('Null', $values);
		$this->assertContains('Exception', $values);
	}

	public function test_is_tenumerable()
	{
		$this->assertTrue(is_a(TActiveRecordInvalidFinderResult::class, \Prado\TEnumerable::class, true));
	}

	public function test_enumerable_iterator()
	{
		$enum = new TActiveRecordInvalidFinderResult();
		$values = [];
		foreach ($enum as $k => $v) {
			$values[$k] = $v;
		}
		$this->assertArrayHasKey('Null', $values);
		$this->assertArrayHasKey('Exception', $values);
	}

	// -------  TActiveRecordCriteria  -------

	public function test_extends_tsql_criteria()
	{
		$criteria = new TActiveRecordCriteria();
		$this->assertInstanceOf(TSqlCriteria::class, $criteria);
	}

	public function test_criteria_condition()
	{
		$criteria = new TActiveRecordCriteria();
		$criteria->Condition = 'username = :name';
		$this->assertSame('username = :name', $criteria->Condition);
	}

	public function test_criteria_parameters()
	{
		$criteria = new TActiveRecordCriteria();
		$criteria->Parameters[':name'] = 'admin';
		$criteria->Parameters[':pass'] = 'prado';
		$this->assertSame('admin', $criteria->Parameters[':name']);
		$this->assertSame('prado', $criteria->Parameters[':pass']);
	}

	public function test_criteria_orders_by()
	{
		$criteria = new TActiveRecordCriteria();
		$criteria->OrdersBy['level'] = 'desc';
		$criteria->OrdersBy['name'] = 'asc';
		$this->assertSame('desc', $criteria->OrdersBy['level']);
		$this->assertSame('asc', $criteria->OrdersBy['name']);
	}

	public function test_criteria_limit_and_offset()
	{
		$criteria = new TActiveRecordCriteria();
		$criteria->Limit = 10;
		$criteria->Offset = 20;
		$this->assertSame(10, $criteria->Limit);
		$this->assertSame(20, $criteria->Offset);
	}

	public function test_criteria_constructor_with_condition_and_parameters()
	{
		$criteria = new TActiveRecordCriteria('age > :age', [':age' => 30]);
		$this->assertSame('age > :age', $criteria->Condition);
		$this->assertSame(30, $criteria->Parameters[':age']);
	}

	public function test_criteria_limit_and_offset_via_properties()
	{
		$criteria = new TActiveRecordCriteria();
		$criteria->Limit = 5;
		$criteria->Offset = 10;
		$this->assertSame(5, $criteria->Limit);
		$this->assertSame(10, $criteria->Offset);
	}
}
