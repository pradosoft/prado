<?php
require_once(dirname(__FILE__).'/BaseCase.php');

/**
 * @package System.DataAccess.SQLMap
 */
class DelegateTest extends BaseCase
{
	function __construct()
	{
		parent::__construct();
		$this->initSqlMap();
	}

	function testListDelegate()
	{
		$list = $this->sqlmap->queryWithRowDelegate(
					"GetAllAccountsViaResultMap", array($this, 'listHandler'));

		$this->assertIdentical(5, count($list));
		$this->assertAccount1($list[0]);
		$this->assertIdentical(1, $list[0]->getID());
		$this->assertIdentical(2, $list[1]->getID());
		$this->assertIdentical(3, $list[2]->getID());
		$this->assertIdentical(4, $list[3]->getID());
		$this->assertIdentical(5, $list[4]->getID());
	}

	/**
	 * Test ExecuteQueryForMap : Hashtable.
	 */
	function testExecuteQueryForMap()
	{
		$map = $this->sqlmap->QueryForMapWithRowDelegate(
				"GetAllAccountsViaResultClass", array($this, 'mapHandler'), null, "FirstName");

		$this->assertIdentical(5, count($map));
		$this->assertAccount1($map["Joe"]);

		$this->assertIdentical(1, $map["Joe"]->getID());
		$this->assertIdentical(2, $map["Averel"]->getID());
		$this->assertIdentical(3, $map["William"]->getID());
		$this->assertIdentical(4, $map["Jack"]->getID());
		$this->assertIdentical(5, $map["Gilles"]->getID());
	}

	public function listHandler($sender, $param)
	{
		$list = &$param->getList();
		$list[] = $param->result;
		$this->assertTrue($param->result instanceof Account);
	}

	public function mapHandler($sender, $param)
	{
		$map = &$param->getMap();
		$map[$param->getKey()] = $param->getValue();
		$this->assertTrue($param->getValue() instanceof Account);
	}
}



?>