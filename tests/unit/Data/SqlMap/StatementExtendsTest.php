<?php

use Prado\Data\SqlMap\TSqlMapConfig;

class StatementExtendsTest extends PHPUnit\Framework\TestCase
{
	protected $sqlmap;

	public function setup()
	{
		$config = new TSqlMapConfig();
		$config->ConfigFile = __DIR__ . '/maps/tests.xml';
		$this->sqlmap = $config->getClient();
	}

	public function test_extends1()
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
