<?php

Prado::using('System.Data.SqlMap.TSqlMapConfig');
class StatementExtendsTest extends UnitTestCase
{
	protected $sqlmap;

	function setup()
	{
		$config = new TSqlMapConfig();
		$config->ConfigFile = dirname(__FILE__).'/maps/tests.xml';
		$this->sqlmap = $config->getClient();
	}

	function test_extends1()
	{
		$manager = $this->sqlmap->SqlMapManager;
		$sql = $manager->getMappedStatement('test')->getSqlString();

		$this->assertPattern('/img_request/', $sql);
		$this->assertNoPattern('/img_progress/', $sql);

		$sql2 = $manager->getMappedStatement('GetAllProgress')->getSqlString();
		$this->assertPattern('/img_request/', $sql2);
		$this->assertPattern('/img_progress/', $sql2);
	}
}

?>