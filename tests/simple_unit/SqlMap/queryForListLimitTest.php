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
		$list1 = $this->sqlmap->queryForList('GetAllAccountsAsArrayListViaResultClass', null, null, 2);
		//var_dump($list1);
	}
}

?>