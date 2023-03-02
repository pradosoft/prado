<?php


use Prado\Data\DataGateway\TSqlCriteria;

class TSqlCriteriaTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
	}

	protected function tearDown(): void
	{
	}

	public function testConstruct()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

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

	public function testParameters()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testIsNamedParameters()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testOrdersBy()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testLimit()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testOffset()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testToString()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}
}
