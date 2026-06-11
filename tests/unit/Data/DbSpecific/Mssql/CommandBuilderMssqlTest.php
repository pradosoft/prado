<?php

use Prado\Data\Common\Mssql\TMssqlCommandBuilder;

class CommandBuilderMssqlTest extends PHPUnit\Framework\TestCase
{
	protected static $sql = [
		'simple' => 'SELECT username, age FROM accounts',
		'multiple' => 'select a.username, b.name from accounts a, table1 b where a.age = b.id1',
		'ordering' => 'select a.username, b.name, a.age from accounts a, table1 b where a.age = b.id1 order by age DESC, name',
		'index' => 'select a.username, b.name, a.age from accounts a, table1 b where a.age = b.id1 ORDER BY 1 DESC, 2 ASC',
		//'compute' => 'SELECT username, age FROM accounts order by age compute avg(age)',
	];

	public function test_limit()
	{
		$builder = new TMssqlCommandBuilder();

		$sql = $builder->applyLimitOffset(self::$sql['simple'], 3);
		$expect = 'SELECT TOP 3 username, age FROM accounts';
		$this->assertEquals($expect, $sql);


		// SQL Server 2012+ OFFSET/FETCH. A no-op ORDER BY is injected when the
		// source query has none (OFFSET/FETCH requires an ORDER BY).
		$sql = $builder->applyLimitOffset(self::$sql['simple'], 3, 2);
		$expect = 'SELECT username, age FROM accounts ORDER BY (SELECT NULL) OFFSET 2 ROWS FETCH NEXT 3 ROWS ONLY';
		$this->assertEquals($expect, $sql);

		$sql = $builder->applyLimitOffset(self::$sql['multiple'], 3, 2);
		$expect = 'select a.username, b.name from accounts a, table1 b where a.age = b.id1 ORDER BY (SELECT NULL) OFFSET 2 ROWS FETCH NEXT 3 ROWS ONLY';
		$this->assertEquals($sql, $expect);

		$sql = $builder->applyLimitOffset(self::$sql['ordering'], 3, 2);
		$expect = 'select a.username, b.name, a.age from accounts a, table1 b where a.age = b.id1 order by age DESC, name OFFSET 2 ROWS FETCH NEXT 3 ROWS ONLY';
		$this->assertEquals($sql, $expect);

		$sql = $builder->applyLimitOffset(self::$sql['index'], 3, 2);
		$expect = 'select a.username, b.name, a.age from accounts a, table1 b where a.age = b.id1 ORDER BY 1 DESC, 2 ASC OFFSET 2 ROWS FETCH NEXT 3 ROWS ONLY';
		$this->assertEquals($expect, $sql);

		//	$sql = $builder->applyLimitOffset(self::$sql['compute'], 3, 2);
	//	var_dump($sql);
	}
}
