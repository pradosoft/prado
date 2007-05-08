<?php

require_once(dirname(__FILE__).'/BaseCase.php');

/**
 * @package System.DataAccess.SQLMap
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
		$this->assertEqual(count($list1),2);

		$this->assertEqual($list1[0][0],'2');
		$this->assertEqual($list1[0][1],'Averel');
		$this->assertEqual($list1[0][2],'Dalton');

		$this->assertEqual($list1[1][0],'3');
		$this->assertEqual($list1[1][1],'William');
		$this->assertEqual($list1[1][2],'Dalton');
	}
}

?>