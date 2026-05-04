<?php

use Prado\Data\DataGateway\TSqlCriteria;

class TSqlCriteriaTest extends PHPUnit\Framework\TestCase
{
	// -----------------------------------------------------------------------
	// Constructor
	// -----------------------------------------------------------------------

	public function testConstruct()
	{
		// No arguments: condition, limit, offset all null; empty collections.
		$c = new TSqlCriteria();
		$this->assertNull($c->getCondition());
		$this->assertNull($c->getLimit());
		$this->assertNull($c->getOffset());
		$this->assertCount(0, $c->getParameters());
		$this->assertCount(0, $c->getOrdersBy());
		$this->assertEquals('*', $c->getSelect());
	}

	public function testConstructWithConditionAndSingleParameter()
	{
		$c = new TSqlCriteria('id=:id', [':id' => 5]);
		$this->assertEquals('id=:id', $c->getCondition());
		$this->assertEquals(5, $c->getParameters()[':id']);
	}

	public function testConstructWithVariadicParameters()
	{
		// When extra args (non-array) are passed they become an indexed array.
		$c = new TSqlCriteria('a=? AND b=?', 1, 2);
		$params = $c->getParameters();
		$this->assertEquals(1, $params[0]);
		$this->assertEquals(2, $params[1]);
	}

	// -----------------------------------------------------------------------
	// Condition parsing — ORDER BY
	// -----------------------------------------------------------------------

	public function testConditionWithOrderByColumnNames()
	{
		$criteria = new TSqlCriteria();
		$criteria->Condition = "SELECT * FROM table_references ORDER BY field1 ASC, field2 DESC";
		self::assertEquals("SELECT * FROM table_references", $criteria->Condition);
		self::assertEquals(true, isset($criteria->OrdersBy['field1']));
		self::assertEquals('ASC', $criteria->OrdersBy['field1']);
		self::assertEquals(true, isset($criteria->OrdersBy['field2']));
		self::assertEquals('DESC', $criteria->OrdersBy['field2']);
	}

	public function testConditionWithOrderByExpression()
	{
		$criteria = new TSqlCriteria();
		$criteria->Condition = "SELECT * FROM table_references ORDER BY RAND()";
		self::assertEquals("SELECT * FROM table_references", $criteria->Condition);
		self::assertEquals(true, isset($criteria->OrdersBy['RAND()']));
		self::assertEquals('asc', $criteria->OrdersBy['RAND()']);
	}

	public function testConditionWithOrderByAndLimit()
	{
		$criteria = new TSqlCriteria();
		$criteria->Condition = "SELECT * FROM table_references ORDER BY field1 ASC, field2 DESC LIMIT 2";
		self::assertEquals("SELECT * FROM table_references", $criteria->Condition);
		self::assertEquals(2, $criteria->Limit);
	}

	public function testConditionWithOrderByAndLimitAndOffset()
	{
		$criteria = new TSqlCriteria();
		$criteria->Condition = "SELECT * FROM table_references ORDER BY field1 ASC, field2 DESC LIMIT 3, 2";
		self::assertEquals("SELECT * FROM table_references", $criteria->Condition);
		self::assertEquals(2, $criteria->Limit);
		self::assertEquals(3, $criteria->Offset);
	}

	public function testConditionWithOrderByAndLimitAndOffsetVariant()
	{
		$criteria = new TSqlCriteria();
		$criteria->Condition = "SELECT * FROM table_references ORDER BY field1 ASC, field2 DESC LIMIT 2 OFFSET 3";
		self::assertEquals("SELECT * FROM table_references", $criteria->Condition);
		self::assertEquals(2, $criteria->Limit);
		self::assertEquals(3, $criteria->Offset);
	}

	// -----------------------------------------------------------------------
	// Condition parsing — LIMIT / OFFSET
	// -----------------------------------------------------------------------

	public function testConditionWithLimit()
	{
		$criteria = new TSqlCriteria();
		$criteria->Condition = "SELECT * FROM table_references LIMIT 2";
		self::assertEquals("SELECT * FROM table_references", $criteria->Condition);
		self::assertEquals(2, $criteria->Limit);
	}

	public function testConditionWithLimitAndOffset()
	{
		$criteria = new TSqlCriteria();
		$criteria->Condition = "SELECT * FROM table_references LIMIT 3, 2";
		self::assertEquals("SELECT * FROM table_references", $criteria->Condition);
		self::assertEquals(2, $criteria->Limit);
		self::assertEquals(3, $criteria->Offset);
	}

	public function testConditionWithLimitAndOffsetVariant()
	{
		$criteria = new TSqlCriteria();
		$criteria->Condition = "SELECT * FROM table_references LIMIT 2 OFFSET 3";
		self::assertEquals("SELECT * FROM table_references", $criteria->Condition);
		self::assertEquals(2, $criteria->Limit);
		self::assertEquals(3, $criteria->Offset);
	}

	public function testEmptyConditionResetsToNull()
	{
		$c = new TSqlCriteria('some condition');
		$c->setCondition('');
		$this->assertNull($c->getCondition());
	}

	// -----------------------------------------------------------------------
	// Parameters
	// -----------------------------------------------------------------------

	public function testParameters()
	{
		$c = new TSqlCriteria();
		$c->Parameters[':name'] = 'alice';
		$c->Parameters[':age']  = 30;

		$this->assertEquals('alice', $c->Parameters[':name']);
		$this->assertEquals(30, $c->Parameters[':age']);
		$this->assertCount(2, $c->getParameters());
	}

	public function testSetParametersFromArray()
	{
		$c = new TSqlCriteria();
		$c->setParameters([':x' => 1, ':y' => 2]);
		$this->assertEquals(1, $c->Parameters[':x']);
		$this->assertEquals(2, $c->Parameters[':y']);
	}

	// -----------------------------------------------------------------------
	// IsNamedParameters
	// -----------------------------------------------------------------------

	public function testIsNamedParameters()
	{
		$named = new TSqlCriteria();
		$named->Parameters[':id'] = 1;
		$this->assertTrue($named->getIsNamedParameters());

		$positional = new TSqlCriteria('a=?', [1]);
		$this->assertFalse($positional->getIsNamedParameters());
	}

	public function testIsNamedParametersReturnsFalseWhenEmpty()
	{
		// No parameters at all: the foreach body is never entered → returns false.
		$c = new TSqlCriteria();
		$this->assertFalse($c->getIsNamedParameters());
	}

	// -----------------------------------------------------------------------
	// OrdersBy
	// -----------------------------------------------------------------------

	public function testOrdersBy()
	{
		$c = new TSqlCriteria();
		$c->OrdersBy['name']  = 'asc';
		$c->OrdersBy['price'] = 'desc';

		$this->assertEquals('asc', $c->OrdersBy['name']);
		$this->assertEquals('desc', $c->OrdersBy['price']);
	}

	public function testSetOrdersByFromArray()
	{
		$c = new TSqlCriteria();
		$c->setOrdersBy(['name' => 'desc', 'id' => 'asc']);

		$this->assertEquals('desc', $c->OrdersBy['name']);
		$this->assertEquals('asc', $c->OrdersBy['id']);
	}

	public function testSetOrdersByFromString()
	{
		$c = new TSqlCriteria();
		$c->setOrdersBy('name ASC, price DESC');

		$this->assertEquals('ASC', $c->OrdersBy['name']);
		$this->assertEquals('DESC', $c->OrdersBy['price']);
	}

	public function testSetOrdersByFromStringDefaultsToAsc()
	{
		// A token without a direction word defaults to 'asc'.
		$c = new TSqlCriteria();
		$c->setOrdersBy('name');
		$this->assertEquals('asc', $c->OrdersBy['name']);
	}

	// -----------------------------------------------------------------------
	// Limit / Offset setters
	// -----------------------------------------------------------------------

	public function testLimit()
	{
		$c = new TSqlCriteria();
		$this->assertNull($c->getLimit());

		$c->setLimit(10);
		$this->assertEquals(10, $c->getLimit());

		$c->Limit = 20;
		$this->assertEquals(20, $c->getLimit());
	}

	public function testOffset()
	{
		$c = new TSqlCriteria();
		$this->assertNull($c->getOffset());

		$c->setOffset(5);
		$this->assertEquals(5, $c->getOffset());

		$c->Offset = 15;
		$this->assertEquals(15, $c->getOffset());
	}

	// -----------------------------------------------------------------------
	// Select
	// -----------------------------------------------------------------------

	public function testSelectDefaultsStar()
	{
		$c = new TSqlCriteria();
		$this->assertEquals('*', $c->getSelect());
	}

	public function testSetSelect()
	{
		$c = new TSqlCriteria();
		$c->setSelect(['id', 'name']);
		$this->assertEquals(['id', 'name'], $c->getSelect());
	}

	// -----------------------------------------------------------------------
	// __toString
	// -----------------------------------------------------------------------

	public function testToString()
	{
		$c = new TSqlCriteria('id=:id', [':id' => 1]);
		$c->OrdersBy['name'] = 'asc';
		$c->Limit  = 5;
		$c->Offset = 10;

		$str = (string) $c;

		$this->assertStringContainsString('id=:id', $str);
		$this->assertStringContainsString(':id => 1', $str);
		$this->assertStringContainsString('name => asc', $str);
		$this->assertStringContainsString('5', $str);
		$this->assertStringContainsString('10', $str);
	}

	public function testToStringEmptyCriteria()
	{
		// An empty criteria should produce an empty string without errors.
		$c   = new TSqlCriteria();
		$str = (string) $c;
		$this->assertIsString($str);
		$this->assertEquals('', $str);
	}

	public function testToStringConditionOnly()
	{
		$c   = new TSqlCriteria('id > 0');
		$str = (string) $c;
		$this->assertStringContainsString('id > 0', $str);
	}
}
