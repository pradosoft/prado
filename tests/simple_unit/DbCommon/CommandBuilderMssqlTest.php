<?php

Prado::using('System.Data.*');
Prado::using('System.Data.Common.Mssql.TMssqlCommandBuilder');

class CommandBuilderMssqlTest extends UnitTestCase
{
	protected static $sql = array(
		'simple' => 'SELECT username, age FROM accounts',
		'multiple' => 'select a.username, b.name from accounts a, table1 b where a.age = b.id1',
		'ordering' => 'select a.username, b.name, a.age from accounts a, table1 b where a.age = b.id1 order by age DESC, name',
		'index' => 'select a.username, b.name, a.age from accounts a, table1 b where a.age = b.id1 ORDER BY 1 DESC, 2 ASC',
		//'compute' => 'SELECT username, age FROM accounts order by age compute avg(age)',
	);

	function test_limit()
	{
		$builder = new TMssqlCommandBuilder();

		$sql = $builder->applyLimitOffset(self::$sql['simple'], 3);
		$expect = 'SELECT TOP 3 username, age FROM accounts';
		$this->assertEqual($expect, $sql);


		$sql = $builder->applyLimitOffset(self::$sql['simple'], 3, 2);
		$expect = 'SELECT * FROM (SELECT TOP 3 * FROM (SELECT TOP 5 username, age FROM accounts) as [__inner top table__] ) as [__outer top table__] ';
		$this->assertEqual($expect, $sql);

		$sql = $builder->applyLimitOffset(self::$sql['multiple'], 3, 2);
		$expect = 'SELECT * FROM (SELECT TOP 3 * FROM (SELECT TOP 5 a.username, b.name from accounts a, table1 b where a.age = b.id1) as [__inner top table__] ) as [__outer top table__] ';
		$this->assertEqual($sql, $expect);

		$sql = $builder->applyLimitOffset(self::$sql['ordering'], 3, 2);
		$expect = 'SELECT * FROM (SELECT TOP 3 * FROM (SELECT TOP 5 a.username, b.name, a.age from accounts a, table1 b where a.age = b.id1 order by age DESC, name) as [__inner top table__] ORDER BY age ASC, name DESC) as [__outer top table__] ORDER BY age DESC, name ASC';
		$this->assertEqual($sql, $expect);

		$sql = $builder->applyLimitOffset(self::$sql['index'], 3, 2);
		$expect = 'SELECT * FROM (SELECT TOP 3 * FROM (SELECT TOP 5 a.username, b.name, a.age from accounts a, table1 b where a.age = b.id1 ORDER BY 1 DESC, 2 ASC) as [__inner top table__] ORDER BY 1 ASC, 2 DESC) as [__outer top table__] ORDER BY 1 DESC, 2 ASC';
		$this->assertEqual($expect, $sql);

	//	$sql = $builder->applyLimitOffset(self::$sql['compute'], 3, 2);
	//	var_dump($sql);
	}
}

?>