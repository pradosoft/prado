<?php

require_once(dirname(__FILE__).'/BaseCase.php');

/**
 * @package System.Data.SqlMap
 */
class queryForListLimitTest extends BaseCase
{
	function __construct()
	{
		parent::__construct();

		$this->initSqlMap();

		//force autoload
		new Account;
	}

	function resetDatabase()
	{
		$this->initScript('account-init.sql');
	}

	function test_accounts_limit_2()
	{
		$list1 = $this->sqlmap->queryForList('GetAllAccountsAsArrayListViaResultClass',null,null,1,2);
		$this->assertEquals(count($list1),2);

		$this->assertEquals($list1[0][0],'2');
		$this->assertEquals($list1[0][1],'Averel');
		$this->assertEquals($list1[0][2],'Dalton');

		$this->assertEquals($list1[1][0],'3');
		$this->assertEquals($list1[1][1],'William');
		$this->assertEquals($list1[1][2],'Dalton');
	}
}
