<?php
require_once(dirname(__FILE__).'/BaseTest.php');

class AccountWithOrders extends Account
{
	private $_orders = array();
	
	public function setOrders($orders)
	{
		$this->_orders = $orders;
	}

	public function getOrders()
	{
		return $this->_orders;
	}
}


/**
 * @package System.DataAccess.SQLMap
 */
class GroupByTest extends BaseTest
{
	function __construct()
	{
		parent::__construct();
		$this->initSqlMap();
	}

	function testAccountWithOrders()
	{
		$this->initScript('account-init.sql');
		$accounts = $this->sqlmap->queryForList("getAccountWithOrders");
		$this->assertEquals(5, count($accounts));
		foreach($accounts as $account)
			$this->assertEquals(2, count($account->getOrders()));
	}

/**/
}

?>