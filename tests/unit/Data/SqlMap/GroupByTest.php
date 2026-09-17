<?php

namespace Prado\Test\Unit\Data\SqlMap;

class GroupByTest extends BaseCase
{
	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();
		self::initSqlMap();
	}

	public function testAccountWithOrders()
	{
		$this->initScript('account-init.sql');
		$accounts = self::$sqlmap->queryForList("getAccountWithOrders");
		$this->assertSame(5, count($accounts));
		foreach ($accounts as $account) {
			$this->assertSame(2, count($account->getOrders()));
		}
	}
}
