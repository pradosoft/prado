<?php

use Prado\Data\SqlMap\TSqlMapConfig;

class StatementExtendsTest extends PHPUnit\Framework\TestCase
{
	protected $testSqlMap;

	protected function setUp(): void
	{
		$config = new TSqlMapConfig();
		$config->ConfigFile = __DIR__ . '/maps/tests.xml';
		$this->testSqlMap = $config->getClient();
	}

	public function test_extends1()
	{
		$manager = $this->testSqlMap->SqlMapManager;
		$sql = $manager->getMappedStatement('test')->getSqlString();

		$this->assertMatchesRegularExpression('/img_request/', $sql);
//		$this->assertNoPattern('/img_progress/', $sql);

		$sql2 = $manager->getMappedStatement('GetAllProgress')->getSqlString();
		$this->assertMatchesRegularExpression('/img_request/', $sql2);
		$this->assertMatchesRegularExpression('/img_progress/', $sql2);
	}
}
