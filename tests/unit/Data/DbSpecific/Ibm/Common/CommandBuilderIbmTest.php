<?php

namespace Prado\Test\Unit\Data\DbSpecific\Ibm\Common;

use Prado\Data\Common\Ibm\TIbmCommandBuilder;

class CommandBuilderIbmTest extends \PHPUnit\Framework\TestCase
{
	protected static $sql = [
		'simple'   => 'SELECT username, age FROM accounts',
		'multiple' => 'SELECT a.username, b.name FROM accounts a, table1 b WHERE a.age = b.id',
		'ordering' => 'SELECT a.username, b.age FROM accounts a ORDER BY a.age DESC',
	];

	public function test_ibm_no_limit_no_offset()
	{
		$builder = new TIbmCommandBuilder();

		$sql = $builder->applyLimitOffset(self::$sql['simple'], -1, -1);
		$this->assertEquals(self::$sql['simple'], $sql);
	}

	public function test_ibm_limit_only()
	{
		$builder = new TIbmCommandBuilder();

		$sql = $builder->applyLimitOffset(self::$sql['simple'], 5, -1);
		$this->assertEquals(self::$sql['simple'] . ' FETCH FIRST 5 ROWS ONLY', $sql);
	}

	public function test_ibm_limit_with_zero_offset()
	{
		$builder = new TIbmCommandBuilder();

		// offset=0 has no rows to skip: uses FETCH FIRST
		$sql = $builder->applyLimitOffset(self::$sql['simple'], 10, 0);
		$this->assertEquals(self::$sql['simple'] . ' FETCH FIRST 10 ROWS ONLY', $sql);
	}

	public function test_ibm_limit_and_offset()
	{
		$builder = new TIbmCommandBuilder();

		$sql = $builder->applyLimitOffset(self::$sql['simple'], 10, 5);
		// DB2 LUW 11.1+ native syntax: OFFSET 5 ROWS FETCH NEXT 10 ROWS ONLY
		$expect = self::$sql['simple'] . ' OFFSET 5 ROWS FETCH NEXT 10 ROWS ONLY';
		$this->assertEquals($expect, $sql);
	}

	public function test_ibm_limit_and_offset_page_two()
	{
		$builder = new TIbmCommandBuilder();

		// page 2 of 3 results per page
		$sql = $builder->applyLimitOffset(self::$sql['multiple'], 3, 3);
		// DB2 LUW 11.1+ native syntax: OFFSET 3 ROWS FETCH NEXT 3 ROWS ONLY
		$expect = self::$sql['multiple'] . ' OFFSET 3 ROWS FETCH NEXT 3 ROWS ONLY';
		$this->assertEquals($expect, $sql);
	}

	public function test_ibm_limit_and_offset_with_ordering()
	{
		$builder = new TIbmCommandBuilder();

		$sql = $builder->applyLimitOffset(self::$sql['ordering'], 5, 10);
		// DB2 LUW 11.1+ native syntax: OFFSET 10 ROWS FETCH NEXT 5 ROWS ONLY
		$expect = self::$sql['ordering'] . ' OFFSET 10 ROWS FETCH NEXT 5 ROWS ONLY';
		$this->assertEquals($expect, $sql);
	}

	public function test_ibm_first_page_offset_one()
	{
		$builder = new TIbmCommandBuilder();

		$sql = $builder->applyLimitOffset(self::$sql['simple'], 20, 1);
		// DB2 LUW 11.1+ native syntax: OFFSET 1 ROWS FETCH NEXT 20 ROWS ONLY
		$expect = self::$sql['simple'] . ' OFFSET 1 ROWS FETCH NEXT 20 ROWS ONLY';
		$this->assertEquals($expect, $sql);
	}
}
