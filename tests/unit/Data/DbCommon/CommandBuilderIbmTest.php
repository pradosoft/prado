<?php

use Prado\Data\Common\Ibm\TIbmCommandBuilder;

class CommandBuilderIbmTest extends PHPUnit\Framework\TestCase
{
	protected static $sql = [
		'simple'   => 'SELECT username, age FROM accounts',
		'multiple' => 'SELECT a.username, b.name FROM accounts a, table1 b WHERE a.age = b.id',
		'ordering' => 'SELECT a.username, b.age FROM accounts a ORDER BY a.age DESC',
	];

	public function test_no_limit_no_offset()
	{
		$builder = new TIbmCommandBuilder();

		$sql = $builder->applyLimitOffset(self::$sql['simple'], -1, -1);
		$this->assertEquals(self::$sql['simple'], $sql);
	}

	public function test_limit_only()
	{
		$builder = new TIbmCommandBuilder();

		$sql = $builder->applyLimitOffset(self::$sql['simple'], 5, -1);
		$this->assertEquals(self::$sql['simple'] . ' FETCH FIRST 5 ROWS ONLY', $sql);
	}

	public function test_limit_with_zero_offset()
	{
		$builder = new TIbmCommandBuilder();

		// offset=0 satisfies offset <= 0: uses FETCH FIRST, not ROW_NUMBER()
		$sql = $builder->applyLimitOffset(self::$sql['simple'], 10, 0);
		$this->assertEquals(self::$sql['simple'] . ' FETCH FIRST 10 ROWS ONLY', $sql);
	}

	public function test_limit_and_offset()
	{
		$builder = new TIbmCommandBuilder();

		$sql = $builder->applyLimitOffset(self::$sql['simple'], 10, 5);
		$lower = 6;   // offset + 1
		$upper = 15;  // offset + limit
		$expect = 'SELECT * FROM (SELECT prado_inner.*, ROW_NUMBER() OVER() AS prado_rownum '
			. 'FROM (' . self::$sql['simple'] . ') AS prado_inner) AS prado_outer '
			. 'WHERE prado_rownum BETWEEN ' . $lower . ' AND ' . $upper;
		$this->assertEquals($expect, $sql);
	}

	public function test_limit_and_offset_page_two()
	{
		$builder = new TIbmCommandBuilder();

		// page 2 of 3 results per page
		$sql = $builder->applyLimitOffset(self::$sql['multiple'], 3, 3);
		$lower = 4;  // 3 + 1
		$upper = 6;  // 3 + 3
		$expect = 'SELECT * FROM (SELECT prado_inner.*, ROW_NUMBER() OVER() AS prado_rownum '
			. 'FROM (' . self::$sql['multiple'] . ') AS prado_inner) AS prado_outer '
			. 'WHERE prado_rownum BETWEEN ' . $lower . ' AND ' . $upper;
		$this->assertEquals($expect, $sql);
	}

	public function test_limit_and_offset_with_ordering()
	{
		$builder = new TIbmCommandBuilder();

		$sql = $builder->applyLimitOffset(self::$sql['ordering'], 5, 10);
		$lower = 11;
		$upper = 15;
		$expect = 'SELECT * FROM (SELECT prado_inner.*, ROW_NUMBER() OVER() AS prado_rownum '
			. 'FROM (' . self::$sql['ordering'] . ') AS prado_inner) AS prado_outer '
			. 'WHERE prado_rownum BETWEEN ' . $lower . ' AND ' . $upper;
		$this->assertEquals($expect, $sql);
	}

	public function test_first_page_offset_one()
	{
		$builder = new TIbmCommandBuilder();

		$sql = $builder->applyLimitOffset(self::$sql['simple'], 20, 1);
		$lower = 2;
		$upper = 21;
		$expect = 'SELECT * FROM (SELECT prado_inner.*, ROW_NUMBER() OVER() AS prado_rownum '
			. 'FROM (' . self::$sql['simple'] . ') AS prado_inner) AS prado_outer '
			. 'WHERE prado_rownum BETWEEN ' . $lower . ' AND ' . $upper;
		$this->assertEquals($expect, $sql);
	}
}
