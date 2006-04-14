<?php
require_once(dirname(__FILE__).'/BaseTest.php');

/**
 * @package System.DataAccess.SQLMap
 */
class DelegateTest extends BaseTest
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

		$this->assertEquals(5, count($list));
		$this->assertAccount1($list[0]);
		$this->assertEquals(1, $list[0]->getID());
		$this->assertEquals(2, $list[1]->getID());
		$this->assertEquals(3, $list[2]->getID());
		$this->assertEquals(4, $list[3]->getID());
		$this->assertEquals(5, $list[4]->getID());
	}

	/**
	 * Test ExecuteQueryForMap : Hashtable.
	 */
	function testExecuteQueryForMap()
	{
		$map = $this->sqlmap->QueryForMapWithRowDelegate(
				"GetAllAccountsViaResultClass", array($this, 'mapHandler'), null, "FirstName");

		$this->assertEquals(5, count($map));
		$this->assertAccount1($map["Joe"]);

		$this->assertEquals(1, $map["Joe"]->getID());
		$this->assertEquals(2, $map["Averel"]->getID());
		$this->assertEquals(3, $map["William"]->getID());
		$this->assertEquals(4, $map["Jack"]->getID());
		$this->assertEquals(5, $map["Gilles"]->getID());
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