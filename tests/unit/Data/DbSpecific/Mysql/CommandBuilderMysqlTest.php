<?php

use Prado\Data\Common\Mysql\TMysqlCommandBuilder;

/**
 * Tests for TMysqlCommandBuilder::applyLimitOffset.
 *
 * TMysqlCommandBuilder inherits the base TDbCommandBuilder::applyLimitOffset unchanged.
 * The base implementation uses independent >= 0 thresholds for limit and offset,
 * so limit=0 emits LIMIT 0, offset=0 emits OFFSET 0, and offset-only emits just OFFSET n
 * (no LIMIT clause). This differs from SQLite which requires LIMIT -1 for offset-only.
 */
class CommandBuilderMysqlTest extends PHPUnit\Framework\TestCase
{
	protected static string $sql = 'SELECT username, age FROM accounts';

	public function test_no_limit_or_offset()
	{
		$builder = new TMysqlCommandBuilder();
		$result = $builder->applyLimitOffset(self::$sql);
		$this->assertEquals(self::$sql, $result);
	}

	public function test_limit_only()
	{
		$builder = new TMysqlCommandBuilder();
		$result = $builder->applyLimitOffset(self::$sql, 5);
		$this->assertEquals(self::$sql . ' LIMIT 5', $result);
	}

	public function test_limit_with_negative_offset()
	{
		$builder = new TMysqlCommandBuilder();
		// offset=-1 means "no offset"; only LIMIT clause should appear
		$result = $builder->applyLimitOffset(self::$sql, 5, -1);
		$this->assertEquals(self::$sql . ' LIMIT 5', $result);
	}

	public function test_offset_only()
	{
		$builder = new TMysqlCommandBuilder();
		// MySQL base builder emits just OFFSET n when limit=-1; no LIMIT clause
		$result = $builder->applyLimitOffset(self::$sql, -1, 10);
		$this->assertEquals(self::$sql . ' OFFSET 10', $result);
	}

	public function test_limit_and_offset()
	{
		$builder = new TMysqlCommandBuilder();
		$result = $builder->applyLimitOffset(self::$sql, 5, 10);
		$this->assertEquals(self::$sql . ' LIMIT 5 OFFSET 10', $result);
	}

	public function test_zero_limit()
	{
		$builder = new TMysqlCommandBuilder();
		// limit=0 is >= 0, so LIMIT 0 is emitted (unlike SQLite where 0 > 0 is false)
		$result = $builder->applyLimitOffset(self::$sql, 0);
		$this->assertEquals(self::$sql . ' LIMIT 0', $result);
	}

	public function test_zero_limit_zero_offset()
	{
		$builder = new TMysqlCommandBuilder();
		// Both 0 >= 0, so both clauses are emitted
		$result = $builder->applyLimitOffset(self::$sql, 0, 0);
		$this->assertEquals(self::$sql . ' LIMIT 0 OFFSET 0', $result);
	}

	public function test_zero_offset_only()
	{
		$builder = new TMysqlCommandBuilder();
		// offset=0 is >= 0, so OFFSET 0 is emitted
		$result = $builder->applyLimitOffset(self::$sql, -1, 0);
		$this->assertEquals(self::$sql . ' OFFSET 0', $result);
	}

	public function test_limit_with_zero_offset()
	{
		$builder = new TMysqlCommandBuilder();
		// offset=0 is valid and appended
		$result = $builder->applyLimitOffset(self::$sql, 5, 0);
		$this->assertEquals(self::$sql . ' LIMIT 5 OFFSET 0', $result);
	}
}
