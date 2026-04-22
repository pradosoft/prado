<?php

use Prado\Data\Common\Firebird\TFirebirdCommandBuilder;

class CommandBuilderFirebirdTest extends PHPUnit\Framework\TestCase
{
	protected static $sql = [
		'simple'   => 'SELECT username, age FROM accounts',
		'distinct' => 'SELECT DISTINCT username FROM accounts',
		'multiple' => 'SELECT a.username, b.name FROM accounts a, table1 b WHERE a.age = b.id',
		'ordering' => 'SELECT a.username, b.age FROM accounts a ORDER BY a.age DESC',
	];

	public function test_no_limit_no_offset()
	{
		$builder = new TFirebirdCommandBuilder();

		$sql = $builder->applyLimitOffset(self::$sql['simple'], -1, -1);
		$this->assertEquals(self::$sql['simple'], $sql);
	}

	public function test_limit_only()
	{
		$builder = new TFirebirdCommandBuilder();

		$sql = $builder->applyLimitOffset(self::$sql['simple'], 5, -1);
		$this->assertEquals('SELECT FIRST 5 username, age FROM accounts', $sql);
	}

	public function test_offset_only()
	{
		$builder = new TFirebirdCommandBuilder();

		$sql = $builder->applyLimitOffset(self::$sql['simple'], -1, 10);
		$this->assertEquals('SELECT SKIP 10 username, age FROM accounts', $sql);
	}

	public function test_limit_and_offset()
	{
		$builder = new TFirebirdCommandBuilder();

		$sql = $builder->applyLimitOffset(self::$sql['simple'], 5, 10);
		$this->assertEquals('SELECT FIRST 5 SKIP 10 username, age FROM accounts', $sql);
	}

	public function test_limit_and_offset_with_distinct()
	{
		$builder = new TFirebirdCommandBuilder();

		// FIRST/SKIP must be inserted after DISTINCT, not before it
		$sql = $builder->applyLimitOffset(self::$sql['distinct'], 3, 2);
		$this->assertEquals('SELECT DISTINCT FIRST 3 SKIP 2 username FROM accounts', $sql);
	}

	public function test_limit_and_offset_with_ordering()
	{
		$builder = new TFirebirdCommandBuilder();

		$sql = $builder->applyLimitOffset(self::$sql['ordering'], 10, 20);
		$this->assertEquals(
			'SELECT FIRST 10 SKIP 20 a.username, b.age FROM accounts a ORDER BY a.age DESC',
			$sql
		);
	}

	public function test_multiple_table_query()
	{
		$builder = new TFirebirdCommandBuilder();

		$sql = $builder->applyLimitOffset(self::$sql['multiple'], 10, 20);
		$this->assertEquals(
			'SELECT FIRST 10 SKIP 20 a.username, b.name FROM accounts a, table1 b WHERE a.age = b.id',
			$sql
		);
	}

	public function test_zero_offset_emits_skip()
	{
		$builder = new TFirebirdCommandBuilder();

		// offset=0 satisfies offset >= 0, so SKIP 0 is still emitted
		$sql = $builder->applyLimitOffset(self::$sql['simple'], 5, 0);
		$this->assertEquals('SELECT FIRST 5 SKIP 0 username, age FROM accounts', $sql);
	}

	public function test_case_insensitive_select_keyword()
	{
		$builder = new TFirebirdCommandBuilder();

		$sql = $builder->applyLimitOffset('select id from table1', 3, 1);
		$this->assertEquals('select FIRST 3 SKIP 1 id from table1', $sql);
	}
}
