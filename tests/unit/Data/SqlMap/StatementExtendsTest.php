<?php

Prado::using('System.Data.SqlMap.TSqlMapConfig');

/**
 * @package System.Data.SqlMap
 */
class StatementExtendsTest extends PHPUnit_Framework_TestCase
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

		$this->assertRegExp('/img_request/', $sql);
//		$this->assertNoPattern('/img_progress/', $sql);

		$sql2 = $manager->getMappedStatement('GetAllProgress')->getSqlString();
		$this->assertRegExp('/img_request/', $sql2);
		$this->assertRegExp('/img_progress/', $sql2);
	}
}
