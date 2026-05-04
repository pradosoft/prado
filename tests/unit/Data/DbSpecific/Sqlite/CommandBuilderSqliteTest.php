<?php

use Prado\Data\Common\Sqlite\TSqliteCommandBuilder;

/**
 * Tests for TSqliteCommandBuilder::applyLimitOffset.
 *
 * SQLite's applyLimitOffset uses a combined entry condition ($limit > 0 || $offset > 0),
 * so limit=0 and offset=0 both fail to trigger the clause. When offset > 0 but limit <= 0,
 * SQLite syntax requires a LIMIT clause before OFFSET, so LIMIT -1 is emitted.
 *
 * Key differences from MySQL (base TDbCommandBuilder):
 * - limit=0 → no change (MySQL emits LIMIT 0)
 * - zero/zero → no change (MySQL emits LIMIT 0 OFFSET 0)
 * - offset-only → LIMIT -1 OFFSET n (MySQL emits just OFFSET n)
 */
class CommandBuilderSqliteTest extends PHPUnit\Framework\TestCase
{
	protected static string $sql = 'SELECT username, age FROM accounts';

	public function test_no_limit_or_offset()
	{
		$builder = new TSqliteCommandBuilder();
		$result = $builder->applyLimitOffset(self::$sql);
		$this->assertEquals(self::$sql, $result);
	}

	public function test_limit_only()
	{
		$builder = new TSqliteCommandBuilder();
		$result = $builder->applyLimitOffset(self::$sql, 5);
		// limit > 0, offset=-1 (< 0), so OFFSET clause is omitted
		$this->assertEquals(self::$sql . ' LIMIT 5', $result);
	}

	public function test_limit_with_negative_offset()
	{
		$builder = new TSqliteCommandBuilder();
		// Explicit offset=-1 → no OFFSET clause
		$result = $builder->applyLimitOffset(self::$sql, 5, -1);
		$this->assertEquals(self::$sql . ' LIMIT 5', $result);
	}

	public function test_offset_only()
	{
		$builder = new TSqliteCommandBuilder();
		// offset > 0 triggers the block; SQLite requires LIMIT before OFFSET,
		// so LIMIT -1 is emitted as a "no limit" placeholder.
		$result = $builder->applyLimitOffset(self::$sql, -1, 10);
		$this->assertEquals(self::$sql . ' LIMIT -1 OFFSET 10', $result);
	}

	public function test_offset_one()
	{
		$builder = new TSqliteCommandBuilder();
		// Smallest positive offset still forces LIMIT -1
		$result = $builder->applyLimitOffset(self::$sql, -1, 1);
		$this->assertEquals(self::$sql . ' LIMIT -1 OFFSET 1', $result);
	}

	public function test_limit_and_offset()
	{
		$builder = new TSqliteCommandBuilder();
		$result = $builder->applyLimitOffset(self::$sql, 5, 10);
		$this->assertEquals(self::$sql . ' LIMIT 5 OFFSET 10', $result);
	}

	public function test_zero_limit_no_change()
	{
		$builder = new TSqliteCommandBuilder();
		// 0 > 0 is false and offset default is -1, so entry condition fails → unchanged
		$result = $builder->applyLimitOffset(self::$sql, 0);
		$this->assertEquals(self::$sql, $result);
	}

	public function test_zero_zero_no_change()
	{
		$builder = new TSqliteCommandBuilder();
		// 0 > 0 || 0 > 0 is false → unchanged (differs from MySQL which emits LIMIT 0 OFFSET 0)
		$result = $builder->applyLimitOffset(self::$sql, 0, 0);
		$this->assertEquals(self::$sql, $result);
	}

	public function test_limit_with_zero_offset()
	{
		$builder = new TSqliteCommandBuilder();
		// limit > 0 triggers the block; offset=0 is >= 0 so OFFSET 0 is appended
		$result = $builder->applyLimitOffset(self::$sql, 5, 0);
		$this->assertEquals(self::$sql . ' LIMIT 5 OFFSET 0', $result);
	}
}
