<?php
require_once(dirname(__FILE__).'/BaseTest.php');

/**
 * @package System.DataAccess.SQLMap
 */
class StatementTest extends BaseTest
{
	function __construct()
	{
		parent::__construct();
		$this->initSqlMap();

		//force autoload
		new Account;
		new Order;
		new LineItem;
		new LineItemCollection;
		new A; new B; new C; new D; new E; new F;
	}

	public function setup()
	{
		
	}

	function resetDatabase()
	{
		$this->initScript('account-init.sql');
		$this->initScript('order-init.sql');
		$this->initScript('line-item-init.sql');
//		$this->initScript('enumeration-init.sql');
		$this->initScript('other-init.sql');
	}


	#region Object Query tests

	/**
	 * Test Open connection with a connection string
	 */
	function testOpenConnection()
	{
		$this->sqlmap->OpenConnection($this->sqlmap->getDataProvider()->getConnectionString());
		$account= $this->sqlmap->QueryForObject("SelectWithProperty");
		$this->sqlmap->CloseConnection();
		$this->assertAccount1($account);
	}

	/**
	 * Test use a statement with property subtitution
	 * (JIRA 22)
	 */
	function testSelectWithProperty()
	{
		$account= $this->sqlmap->QueryForObject("SelectWithProperty");
		$this->assertAccount1($account);
	}

	/**
	 * Test ExecuteQueryForObject Via ColumnName
	 */
	function testExecuteQueryForObjectViaColumnName()
	{
		$account= $this->sqlmap->QueryForObject("GetAccountViaColumnName", 1);
		$this->assertAccount1($account);
	}

	/**
	 * Test ExecuteQueryForObject Via ColumnIndex
	 */
	function testExecuteQueryForObjectViaColumnIndex()
	{
		$account= $this->sqlmap->QueryForObject("GetAccountViaColumnIndex", 1);
		$this->assertAccount1($account);
	}

	/**
	 * Test ExecuteQueryForObject Via ResultClass
	 */
	function testExecuteQueryForObjectViaResultClass()
	{
		$account= $this->sqlmap->QueryForObject("GetAccountViaResultClass", 1);
		$this->assertAccount1($account);
	}

	/**
	 * Test ExecuteQueryForObject With simple ResultClass : string
	 */
	function testExecuteQueryForObjectWithSimpleResultClass()
	{
		$email = $this->sqlmap->QueryForObject("GetEmailAddressViaResultClass", 1);
		$this->assertEquals("Joe.Dalton@somewhere.com", $email);
	}

	/**
	 * Test ExecuteQueryForObject With simple ResultMap : string
	 */
	function testExecuteQueryForObjectWithSimpleResultMap()
	{
		$email = $this->sqlmap->QueryForObject("GetEmailAddressViaResultMap", 1);
		$this->assertEquals("Joe.Dalton@somewhere.com", $email);
	}

	/**
	 * Test Primitive ReturnValue : TDateTime
	 */
	function testPrimitiveReturnValue()
	{
		$CardExpiry = $this->sqlmap->QueryForObject("GetOrderCardExpiryViaResultClass", 1);
		$date = @mktime(8, 15, 00, 2, 15, 2003);
		$this->assertEquals($date, $CardExpiry->getTimeStamp());
	}

	/**
	 * Test ExecuteQueryForObject with result object : Account
	 */
	function testExecuteQueryForObjectWithResultObject()
	{
		$account= new Account();
		$testAccount = $this->sqlmap->QueryForObject("GetAccountViaColumnName", 1, $account);
		$this->assertAccount1($account);
		$this->assertTrue($account == $testAccount);
	}

	/**
	 * Test ExecuteQueryForObject as array
	 */
	function testExecuteQueryForObjectAsHashArray()
	{
		$account = $this->sqlmap->QueryForObject("GetAccountAsHashtable", 1);
		$this->assertAccount1AsHashArray($account);
	}

	/**
	 * Test ExecuteQueryForObject as Hashtable ResultClass
	 */
	function testExecuteQueryForObjectAsHashtableResultClass()
	{
		$account = $this->sqlmap->QueryForObject("GetAccountAsHashtableResultClass", 1);
		$this->assertAccount1AsHashArray($account);
	}
	
	/**
	 * Test ExecuteQueryForObject via Hashtable
	 */
	function testExecuteQueryForObjectViaHashtable()
	{
		$param["LineItem_ID"] = 2;
		$param["Order_ID"] = 9;

		$testItem = $this->sqlmap->QueryForObject("GetSpecificLineItem", $param);

		$this->assertNotNull($testItem);
		$this->assertEquals("TSM-12", $testItem->getCode());
	}
	/**/

	//TODO: Test Query Dynamic Sql Element
	function testQueryDynamicSqlElement()
	{
		//$list = $this->sqlmap->QueryForList("GetDynamicOrderedEmailAddressesViaResultMap", "Account_ID");

		//$this->assertEquals("Joe.Dalton@somewhere.com", $list[0]);

		//list = $this->sqlmap->QueryForList("GetDynamicOrderedEmailAddressesViaResultMap", "Account_FirstName");

		//$this->assertEquals("Averel.Dalton@somewhere.com", $list[0]);

	}

	// TODO: Test Execute QueryForList With ResultMap With Dynamic Element
	function testExecuteQueryForListWithResultMapWithDynamicElement()
	{
		//$list = $this->sqlmap->QueryForList("GetAllAccountsViaResultMapWithDynamicElement", "LIKE");

		//$this->assertAccount1$list[0]);
		//$this->assertEquals(3, $list->getCount());
		//$this->assertEquals(1, $list[0]->getID());
		//$this->assertEquals(2, $list[1]->getID());
		//$this->assertEquals(4, $list[2]->getID());

		//list = $this->sqlmap->QueryForList("GetAllAccountsViaResultMapWithDynamicElement", "=");

		//$this->assertEquals(0, $list->getCount());
	}

	

	/**
	 * Test Get Account Via Inline Parameters
	 */
	function testExecuteQueryForObjectViaInlineParameters()
	{
		$account= new Account();
		$account->setID(1);

		$testAccount = $this->sqlmap->QueryForObject("GetAccountViaInlineParameters", $account);

		$this->assertAccount1($testAccount);
	}
	/**/

	// TODO: Test ExecuteQuery For Object With Enum property

	function testExecuteQueryForObjectWithEnum()
	{
		//$enumClass = $this->sqlmap->QueryForObject("GetEnumeration", 1);

		//$this->assertEquals(enumClass.Day, Days.Sat);
		//$this->assertEquals(enumClass.Color, Colors.Red);
		//$this->assertEquals(enumClass.Month, Months.August);

		//enumClass = $this->sqlmap->QueryForObject("GetEnumeration", 3) as Enumeration;

		//$this->assertEquals(enumClass.Day, Days.Mon);
		//$this->assertEquals(enumClass.Color, Colors.Blue);
		//$this->assertEquals(enumClass.Month, Months.September);*/
	}

	#endregion

	#region  List Query tests

	/**
	 * Test QueryForList with Hashtable ResultMap
	 */
	function testQueryForListWithHashtableResultMap()
	{
		$this->initScript('account-init.sql');
		$list = $this->sqlmap->QueryForList("GetAllAccountsAsHashMapViaResultMap");

		$this->assertAccount1AsHashArray($list[0]);
		$this->assertEquals(5, count($list));

		$this->assertEquals(1, (int)$list[0]["Id"]);
		$this->assertEquals(2, (int)$list[1]["Id"]);
		$this->assertEquals(3, (int)$list[2]["Id"]);
		$this->assertEquals(4, (int)$list[3]["Id"]);
		$this->assertEquals(5, (int)$list[4]["Id"]);
	}

	/**
	 * Test QueryForList with Hashtable ResultClass
	 */
	function testQueryForListWithHashtableResultClass()
	{
		$list = $this->sqlmap->QueryForList("GetAllAccountsAsHashtableViaResultClass");

		$this->assertAccount1AsHashArray($list[0]);
		$this->assertEquals(5, count($list));

		$this->assertEquals(1, (int)$list[0]["Id"]);
		$this->assertEquals(2, (int)$list[1]["Id"]);
		$this->assertEquals(3, (int)$list[2]["Id"]);
		$this->assertEquals(4, (int)$list[3]["Id"]);
		$this->assertEquals(5, (int)$list[4]["Id"]);
	}

	/**
	 * Test QueryForList with IList ResultClass
	 */
	function testQueryForListWithIListResultClass()
	{
		$list = $this->sqlmap->QueryForList("GetAllAccountsAsArrayListViaResultClass");

		$listAccount = $list[0];

		$this->assertEquals(1,(int)$listAccount[0]);
		$this->assertEquals("Joe",$listAccount[1]);
		$this->assertEquals("Dalton",$listAccount[2]);
		$this->assertEquals("Joe.Dalton@somewhere.com",$listAccount[3]);

		$this->assertEquals(5, count($list));

		$listAccount = $list[0];
		$this->assertEquals(1, (int)$listAccount[0]);
		$listAccount = $list[1];
		$this->assertEquals(2, (int)$listAccount[0]);
		$listAccount = $list[2];
		$this->assertEquals(3, (int)$listAccount[0]);
		$listAccount = $list[3];
		$this->assertEquals(4, (int)$listAccount[0]);
		$listAccount = $list[4];
		$this->assertEquals(5, (int)$listAccount[0]);
	}

	/**
	 * Test QueryForList With ResultMap, result collection as ArrayList
	 */
	function testQueryForListWithResultMap()
	{
		$list = $this->sqlmap->QueryForList("GetAllAccountsViaResultMap");

		$this->assertAccount1($list[0]);
		$this->assertEquals(5, count($list));
		$this->assertEquals(1, $list[0]->getID());
		$this->assertEquals(2, $list[1]->getID());
		$this->assertEquals(3, $list[2]->getID());
		$this->assertEquals(4, $list[3]->getID());
		$this->assertEquals(5, $list[4]->getID());
	}

	/**
	 * Test ExecuteQueryForPaginatedList
	 */
	function testExecuteQueryForPaginatedList()
	{
		// Get List of all 5
		$list = $this->sqlmap->QueryForPagedList("GetAllAccountsViaResultMap", null, 2);

		// Test initial state (page 0)
		$this->assertFalse($list->getIsPreviousPageAvailable());
		$this->assertTrue($list->getIsNextPageAvailable());
		$this->assertAccount1($list[0]);
		$this->assertEquals(2, $list->getCount());
		$this->assertEquals(1, $list[0]->getID());
		$this->assertEquals(2, $list[1]->getID());

		// Test illegal previous page (no effect, state should be same)
		$list->PreviousPage();
		$this->assertFalse($list->getIsPreviousPageAvailable());
		$this->assertTrue($list->getIsNextPageAvailable());
		$this->assertAccount1($list[0]);
		$this->assertEquals(2, $list->getCount());
		$this->assertEquals(1, $list[0]->getID());
		$this->assertEquals(2, $list[1]->getID());

		// Test next (page 1)
		$list->NextPage();
		$this->assertTrue($list->getIsPreviousPageAvailable());
		$this->assertTrue($list->getIsNextPageAvailable());
		$this->assertEquals(2, $list->getCount());
		$this->assertEquals(3, $list[0]->getID());
		$this->assertEquals(4, $list[1]->getID());

		// Test next (page 2 -last)
		$list->NextPage();
		$this->assertTrue($list->getIsPreviousPageAvailable());
		$this->assertFalse($list->getIsNextPageAvailable());
		$this->assertEquals(1, $list->getCount());
		$this->assertEquals(5, $list[0]->getID());

		// Test previous (page 1)
		$list->PreviousPage();
		$this->assertTrue($list->getIsPreviousPageAvailable());
		$this->assertTrue($list->getIsNextPageAvailable());
		$this->assertEquals(2, $list->getCount());
		$this->assertEquals(3, $list[0]->getID());
		$this->assertEquals(4, $list[1]->getID());

		// Test previous (page 0 -first)
		$list->PreviousPage();
		$this->assertFalse($list->getIsPreviousPageAvailable());
		$this->assertTrue($list->getIsNextPageAvailable());
		$this->assertAccount1($list[0]);
		$this->assertEquals(2, $list->getCount());
		$this->assertEquals(1, $list[0]->getID());
		$this->assertEquals(2, $list[1]->getID());

		// Test goto (page 0)
		$list->GotoPage(0);
		$this->assertFalse($list->getIsPreviousPageAvailable());
		$this->assertTrue($list->getIsNextPageAvailable());
		$this->assertEquals(2, $list->getCount());
		$this->assertEquals(1, $list[0]->getID());
		$this->assertEquals(2, $list[1]->getID());

		// Test goto (page 1)
		$list->GotoPage(1);
		$this->assertTrue($list->getIsPreviousPageAvailable());
		$this->assertTrue($list->getIsNextPageAvailable());
		$this->assertEquals(2, $list->getCount());
		$this->assertEquals(3, $list[0]->getID());
		$this->assertEquals(4, $list[1]->getID());

		// Test goto (page 2)
		$list->GotoPage(2);
		$this->assertTrue($list->getIsPreviousPageAvailable());
		$this->assertFalse($list->getIsNextPageAvailable());
		$this->assertEquals(1, $list->getCount());
		$this->assertEquals(5, $list[0]->getID());

		// Test illegal goto (page 0)
		$list->GotoPage(3);
		$this->assertTrue($list->getIsPreviousPageAvailable());
		$this->assertFalse($list->getIsNextPageAvailable());
		$this->assertEquals(0, $list->getCount());

		$list = $this->sqlmap->QueryForPagedList("GetNoAccountsViaResultMap", null, 2);

		// Test empty list
		$this->assertFalse($list->getIsPreviousPageAvailable());
		$this->assertFalse($list->getIsNextPageAvailable());
		$this->assertEquals(0, $list->getCount());

		// Test next
		$list->NextPage();
		$this->assertFalse($list->getIsPreviousPageAvailable());
		$this->assertFalse($list->getIsNextPageAvailable());
		$this->assertEquals(0, $list->getCount());

		// Test previous
		$list->PreviousPage();
		$this->assertFalse($list->getIsPreviousPageAvailable());
		$this->assertFalse($list->getIsNextPageAvailable());
		$this->assertEquals(0, $list->getCount());

		// Test previous
		$list->GotoPage(0);
		$this->assertFalse($list->getIsPreviousPageAvailable());
		$this->assertFalse($list->getIsNextPageAvailable());
		$this->assertEquals(0, $list->getCount());
		$list = $this->sqlmap->QueryForPagedList("GetFewAccountsViaResultMap", null, 2);

		$this->assertFalse($list->getIsPreviousPageAvailable());
		$this->assertFalse($list->getIsNextPageAvailable());
		$this->assertEquals(1, $list->getCount());

		// Test next
		$list->NextPage();
		$this->assertFalse($list->getIsPreviousPageAvailable());
		$this->assertFalse($list->getIsNextPageAvailable());
		$this->assertEquals(1, $list->getCount());
		// Test previous
		$list->PreviousPage();
		$this->assertFalse($list->getIsPreviousPageAvailable());
		$this->assertFalse($list->getIsNextPageAvailable());
		$this->assertEquals(1, $list->getCount());

		// Test previous
		$list->GotoPage(0);
		$this->assertFalse($list->getIsPreviousPageAvailable());
		$this->assertFalse($list->getIsNextPageAvailable());
		$this->assertEquals(1, $list->getCount());


		$list = $this->sqlmap->QueryForPagedList("GetAllAccountsViaResultMap", null, 5);

		$this->assertEquals(5, $list->getCount());

		$list->NextPage();
		$this->assertEquals(5, $list->getCount());

		$b = $list->getIsPreviousPageAvailable();
		$list->PreviousPage();
		$this->assertEquals(5, $list->getCount());
	}

	/**
	 * Test QueryForList with ResultObject : 
	 * AccountCollection strongly typed collection
	 */
	function testQueryForListWithResultObject()
	{
		$accounts = new AccountCollection();

		$this->sqlmap->QueryForList("GetAllAccountsViaResultMap", null, $accounts);
		$this->assertAccount1($accounts[0]);
		$this->assertEquals(5, $accounts->getCount());
		$this->assertEquals(1, $accounts[0]->getID());
		$this->assertEquals(2, $accounts[1]->getID());
		$this->assertEquals(3, $accounts[2]->getID());
		$this->assertEquals(4, $accounts[3]->getID());
		$this->assertEquals(5, $accounts[4]->GetId());
	}

	/**
	 * Test QueryForList with ListClass : LineItemCollection
	 */
	function testQueryForListWithListClass()
	{
		$linesItem = $this->sqlmap->QueryForList("GetLineItemsForOrderWithListClass", 10);

		$this->assertNotNull($linesItem);
		$this->assertEquals(2, $linesItem->getCount());
		$this->assertEquals("ESM-34", $linesItem[0]->getCode());
		$this->assertEquals("QSM-98", $linesItem[1]->getCode());
	}

	/**
	 * Test QueryForList with no result.
	 */
	function testQueryForListWithNoResult()
	{
		$list = $this->sqlmap->QueryForList("GetNoAccountsViaResultMap");

		$this->assertEquals(0, count($list));
	}

	/**
	 * Test QueryForList with ResultClass : Account.
	 */
	function testQueryForListResultClass()
	{
		$list = $this->sqlmap->QueryForList("GetAllAccountsViaResultClass");

		$this->assertAccount1($list[0]);
		$this->assertEquals(5, count($list));
		$this->assertEquals(1, $list[0]->getID());
		$this->assertEquals(2, $list[1]->getID());
		$this->assertEquals(3, $list[2]->getID());
		$this->assertEquals(4, $list[3]->getID());
		$this->assertEquals(5, $list[4]->getID());
	}

	/**
	 * Test QueryForList with simple resultClass : string
	 */
	function testQueryForListWithSimpleResultClass()
	{
		$list = $this->sqlmap->QueryForList("GetAllEmailAddressesViaResultClass");

		$this->assertEquals("Joe.Dalton@somewhere.com", $list[0]);
		$this->assertEquals("Averel.Dalton@somewhere.com", $list[1]);
		$this->assertEquals('', $list[2]);
		$this->assertEquals("Jack.Dalton@somewhere.com", $list[3]);
		$this->assertEquals('', $list[4]);
	}

	/**
	 * Test  QueryForList with simple ResultMap : string
	 */
	function testQueryForListWithSimpleResultMap()
	{
		$list = $this->sqlmap->QueryForList("GetAllEmailAddressesViaResultMap");

		$this->assertEquals("Joe.Dalton@somewhere.com", $list[0]);
		$this->assertEquals("Averel.Dalton@somewhere.com", $list[1]);
		$this->assertEquals('', $list[2]);
		$this->assertEquals("Jack.Dalton@somewhere.com", $list[3]);
		$this->assertEquals('', $list[4]);
	}

	/**
	 * Test QueryForListWithSkipAndMax
	 */
	function testQueryForListWithSkipAndMax()
	{
		$list = $this->sqlmap->QueryForList("GetAllAccountsViaResultMap", null, null, 2, 2);

		$this->assertEquals(2, count($list));
		$this->assertEquals(3, $list[0]->getID());
		$this->assertEquals(4, $list[1]->getID());
	}


	/**
	 * Test row delegate
	 */
	function testQueryWithRowDelegate()
	{
		//$handler = new SqlMapper.RowDelegate(this.RowHandler);

		//$list = $this->sqlmap->QueryWithRowDelegate("GetAllAccountsViaResultMap", null, handler);

		//$this->assertEquals(5, _index);
		//$this->assertEquals(5, $list->getCount());
		//$this->assertAccount1$list[0]);
		//$this->assertEquals(1, $list[0]->getID());
		//$this->assertEquals(2, $list[1]->getID());
		//$this->assertEquals(3, $list[2]->getID());
		//$this->assertEquals(4, $list[3]->getID());
		//$this->assertEquals(5, $list[4]->getID());
	}

	#endregion

	#region  Map Tests

	/**
	 * Test ExecuteQueryForMap : Hashtable.
	 */
	function testExecuteQueryForMap()
	{
		$map = $this->sqlmap->QueryForMap("GetAllAccountsViaResultClass", null, "FirstName");

		$this->assertEquals(5, count($map));
		$this->assertAccount1($map["Joe"]);

		$this->assertEquals(1, $map["Joe"]->getID());
		$this->assertEquals(2, $map["Averel"]->getID());
		$this->assertEquals(3, $map["William"]->getID());
		$this->assertEquals(4, $map["Jack"]->getID());
		$this->assertEquals(5, $map["Gilles"]->getID());
	}

	/**
	 * Test ExecuteQueryForMap : Hashtable.
	 *
	 * If the keyProperty is an integer, you must acces the map
	 * by map[integer] and not by map["integer"]
	 */
	function testExecuteQueryForMap2()
	{
		$map = $this->sqlmap->QueryForMap("GetAllOrderWithLineItems", null, "PostalCode");

		$this->assertEquals(11, count($map));
		$order = $map["T4H 9G4"];

		$this->assertEquals(2, $order->getLineItemsList()->getCount());
	}

	/**
	 * Test ExecuteQueryForMap with value property :
	 * "FirstName" as key, "EmailAddress" as value
	 */
	function testExecuteQueryForMapWithValueProperty()
	{
		$map = $this->sqlmap->QueryForMap("GetAllAccountsViaResultClass", null, 
						"FirstName", "EmailAddress");

		$this->assertEquals(5, count($map));

		$this->assertEquals("Joe.Dalton@somewhere.com", $map["Joe"]);
		$this->assertEquals("Averel.Dalton@somewhere.com", $map["Averel"]);
		$this->assertNull($map["William"]);
		$this->assertEquals("Jack.Dalton@somewhere.com", $map["Jack"]);
		$this->assertNull($map["Gilles"]);
	}

	/**
	 * Test ExecuteQueryForWithJoined
	 */
	function testExecuteQueryForWithJoined()
	{
		$order = $this->sqlmap->QueryForObject("GetOrderJoinWithAccount",10);

		$this->assertNotNull($order->getAccount());

		$order = $this->sqlmap->QueryForObject("GetOrderJoinWithAccount",11);

		$this->assertNull($order->getAccount());
	}

	/**
	 * Test ExecuteQueryFor With Complex Joined
	 *
	 * A->B->C
	 *  ->E
	 *  ->F
	 */
	function testExecuteQueryForWithComplexJoined()
	{
		$a = $this->sqlmap->QueryForObject("SelectComplexJoined",null);
		$this->assertNotNull($a);
		$this->assertNotNull($a->getB());
		$this->assertNotNull($a->getB()->getC());
		$this->assertNull($a->getB()->getD());
		$this->assertNotNull($a->getE());
		$this->assertNull($a->getF());
	}
	#endregion

	#region Extends statement

	/**
	 * Test base Extends statement
	 */
	function testExtendsGetAllAccounts()
	{
		$list = $this->sqlmap->QueryForList("GetAllAccounts");

		$this->assertAccount1($list[0]);
		$this->assertEquals(5, count($list));
		$this->assertEquals(1, $list[0]->getID());
		$this->assertEquals(2, $list[1]->getID());
		$this->assertEquals(3, $list[2]->getID());
		$this->assertEquals(4, $list[3]->getID());
		$this->assertEquals(5, $list[4]->getID());
	}

	/**
	 * Test Extends statement GetAllAccountsOrderByName extends GetAllAccounts
	 */
	function testExtendsGetAllAccountsOrderByName()
	{
		$list = $this->sqlmap->QueryForList("GetAllAccountsOrderByName");

		$this->assertAccount1($list[3]);
		$this->assertEquals(5, count($list));

		$this->assertEquals(2, $list[0]->getID());
		$this->assertEquals(5, $list[1]->getID());
		$this->assertEquals(4, $list[2]->getID());
		$this->assertEquals(1, $list[3]->getID());
		$this->assertEquals(3, $list[4]->getID());
	}

	/**
	 * Test Extends statement GetOneAccount extends GetAllAccounts
	 */
	function testExtendsGetOneAccount()
	{
		$account= $this->sqlmap->QueryForObject("GetOneAccount", 1);
		$this->assertAccount1($account);
	}

	/**
	 * Test Extends statement GetSomeAccount extends GetAllAccounts
	 */
	function testExtendsGetSomeAccount()
	{
		$param["lowID"] = 2;
		$param["hightID"] = 4;

		$list = $this->sqlmap->QueryForList("GetSomeAccount", $param);

		$this->assertEquals(3, count($list));

		$this->assertEquals(2, $list[0]->getID());
		$this->assertEquals(3, $list[1]->getID());
		$this->assertEquals(4, $list[2]->getID());
	}

	#endregion

	#region Update tests


	/**
	 * Test Insert account via public fields
	 */	
	function testInsertAccountViaPublicFields()
	{
		$this->initScript('account-init.sql');

		$account = new AccountBis();

		$account->Id = 10;
		$account->FirstName = "Luky";
		$account->LastName = "Luke";
		$account->EmailAddress = "luly.luke@somewhere.com";

		$this->sqlmap->Insert("InsertAccountViaPublicFields", $account);

		$testAccount = $this->sqlmap->QueryForObject("GetAccountViaColumnName", 10);

		$this->assertNotNull($testAccount);

		$this->assertEquals(10, $testAccount->getID());

		$this->initScript('account-init.sql');
	}

	/**
	 *
	 */
	function testInsertOrderViaProperties()
	{
		$this->initScript('account-init.sql');
		$this->initScript('order-init.sql');
		$account= $this->NewAccount6();

		$this->sqlmap->Insert("InsertAccountViaParameterMap", $account);

		$order = new Order();
		$order->setId(99);
		$order->setCardExpiry("09/11");
		$order->setAccount($account);
		$order->setCardNumber("154564656");
		$order->setCardType("Visa");
		$order->setCity("Lyon");
		$order->setDate('2005-05-20');
		$order->setPostalCode("69004");
		$order->setProvince("Rhone");
		$order->setStreet("rue Durand");

		$this->sqlmap->Insert("InsertOrderViaPublicFields", $order);

		$this->initScript('account-init.sql');
		$this->initScript('order-init.sql');
	}


	/**
	 * Test Insert account via inline parameters
	 */
	function testInsertAccountViaInlineParameters()
	{
		$this->initScript('account-init.sql');
		$account= new Account();

		$account->setId(10);
		$account->setFirstName("Luky");
		$account->setLastName("Luke");
		$account->setEmailAddress("luly.luke@somewhere.com");

		$this->sqlmap->Insert("InsertAccountViaInlineParameters", $account);

		$testAccount = $this->sqlmap->QueryForObject("GetAccountViaColumnIndex", 10);

		$this->assertNotNull($testAccount);
		$this->assertEquals(10, $testAccount->getId());
		$this->initScript('account-init.sql');
	}

	/**
	 * Test Insert account via parameterMap
	 */
	function testInsertAccountViaParameterMap()
	{
		$this->initScript('account-init.sql');
		$account= $this->NewAccount6();
		$this->sqlmap->Insert("InsertAccountViaParameterMap", $account);

		$account = $this->sqlmap->QueryForObject("GetAccountNullableEmail", 6);
		$this->AssertAccount6($account);

		$this->initScript('account-init.sql');
	}

	/**
	 * Test Update via parameterMap
	 */
	function testUpdateViaParameterMap()
	{
		$this->initScript('account-init.sql');
		$account= $this->sqlmap->QueryForObject("GetAccountViaColumnName", 1);

		$account->setEmailAddress("new@somewhere.com");
		$this->sqlmap->Update("UpdateAccountViaParameterMap", $account);

		$account = $this->sqlmap->QueryForObject("GetAccountViaColumnName", 1);

		$this->assertEquals("new@somewhere.com", $account->getEmailAddress());
		$this->initScript('account-init.sql');
	}

	/**
	 * Test Update via parameterMap V2
	 */
	function testUpdateViaParameterMap2()
	{
		$this->initScript('account-init.sql');
		$account= $this->sqlmap->QueryForObject("GetAccountViaColumnName", 1);

		$account->setEmailAddress("new@somewhere.com");
		$this->sqlmap->Update("UpdateAccountViaParameterMap2", $account);

		$account = $this->sqlmap->QueryForObject("GetAccountViaColumnName", 1);

		$this->assertEquals("new@somewhere.com", $account->getEmailAddress());
		$this->initScript('account-init.sql');
	}

	/**
	 * Test Update with inline parameters
	 */
	function testUpdateWithInlineParameters()
	{
		$this->initScript('account-init.sql');
		$account= $this->sqlmap->QueryForObject("GetAccountViaColumnName", 1);

		$account->setEmailAddress("new@somewhere.com");
		$this->sqlmap->Update("UpdateAccountViaInlineParameters", $account);

		$account = $this->sqlmap->QueryForObject("GetAccountViaColumnName", 1);

		$this->assertEquals("new@somewhere.com", $account->getEmailAddress());
		$this->initScript('account-init.sql');
	}

	/**
	 * Test Execute Update With Parameter Class
	 */
	function testExecuteUpdateWithParameterClass()
	{
		$this->initScript('account-init.sql');
		$account= $this->NewAccount6();

		$this->sqlmap->Insert("InsertAccountViaParameterMap", $account);

		$noRowsDeleted = $this->sqlmap->Update("DeleteAccount", null);
		
		$this->sqlmap->Update("DeleteAccount", $account);

		$account = $this->sqlmap->QueryForObject("GetAccountViaColumnName", 6);

		$this->assertNull($account);
		$this->assertEquals(0, $noRowsDeleted);
		$this->initScript('account-init.sql');
	}

	/**
	 * Test Execute Delete
	 */
	function testExecuteDelete()
	{
		$this->initScript('account-init.sql');
		$account= $this->NewAccount6();

		$this->sqlmap->Insert("InsertAccountViaParameterMap", $account);

		$account = null;
		$account = $this->sqlmap->QueryForObject("GetAccountViaColumnName", 6);

		$this->assertTrue($account->getId() == 6);

		$rowNumber = $this->sqlmap->Delete("DeleteAccount", $account);
		$this->assertTrue($rowNumber == 1);

		$account = $this->sqlmap->QueryForObject("GetAccountViaColumnName", 6);

		$this->assertNull($account);
		$this->initScript('account-init.sql');
	}

	/**
	 * Test Execute Delete
	 */
	function testDeleteWithComments()
	{
		$this->initScript('line-item-init.sql');
		$rowNumber = $this->sqlmap->Delete("DeleteWithComments");

		$this->assertEquals($rowNumber, 2);
		$this->initScript('line-item-init.sql');
	}



	#endregion

	#region Row delegate

	private $_index = 0;

	function RowHandler($sender, $paramterObject, $list)
	{
		//_index++;
		//$this->assertEquals(_index, (($account) obj).Id);
		//$list->Add(obj);
	}

	#endregion

	#region JIRA Tests

	/**
	 * Test JIRA 30 (repeating property)
	 */ 
	function testJIRA30()
	{
		$account= new Account();
		$account->setId(1);
		$account->setFirstName("Joe");
		$account->setLastName("Dalton");
		$account->setEmailAddress("Joe.Dalton@somewhere.com");

		$result = $this->sqlmap->QueryForObject("GetAccountWithRepeatingProperty", $account);

		$this->assertAccount1($result);
	}

	/**
	 * Test Bit column 
	 */
	function testJIRA42()
	{
		$other = new Other();

		$other->setInt(100);
		$other->setBool(true);
		$other->setLong(789456321);

		$this->sqlmap->Insert("InsertBool", $other);
	}

	/**
	 * Test for access a result map in a different namespace 
	 */
	function testJIRA45()
	{
		$account= $this->sqlmap->QueryForObject("GetAccountJIRA45", 1);
		$this->assertAccount1($account);
	}

	/**
	 * Test : Whitespace is not maintained properly when CDATA tags are used
	 */
	function testJIRA110()
	{
		$account= $this->sqlmap->QueryForObject("Get1Account");
		$this->assertAccount1($account);
	}

	/**
	 * Test : Whitespace is not maintained properly when CDATA tags are used
	 */
	function testJIRA110Bis()
	{
		$list = $this->sqlmap->QueryForList("GetAccounts");

		$this->assertAccount1($list[0]);
		$this->assertEquals(5, count($list));
	}

	/**
	 * Test for cache stats only being calculated on CachingStatments
	 */
	function testJIRA113()
	{
	  //	$this->sqlmap->FlushCaches();

		// taken from TestFlushDataCache()
		// first query is not cached, second query is: 50% cache hit
		/*$list = $this->sqlmap->QueryForList("GetCachedAccountsViaResultMap");
		$firstId = HashCodeProvider.GetIdentityHashCode(list);
		list = $this->sqlmap->QueryForList("GetCachedAccountsViaResultMap");
		int secondId = HashCodeProvider.GetIdentityHashCode(list);
		$this->assertEquals(firstId, secondId);

		string cacheStats = $this->sqlmap->GetDataCacheStats();

		$this->assertNotNull(cacheStats);*/
	}

	#endregion 

	#region CustomTypeHandler tests

	/**
	 * Test CustomTypeHandler 
	 */
	function testExecuteQueryWithCustomTypeHandler() 
	{
		$this->sqlmap->getTypeHandlerFactory()->register('HundredsBool', new HundredsBool());
		$this->sqlmap->getTypeHandlerFactory()->register('OuiNonBool', new OuiNonBool());
	
		$list = $this->sqlmap->QueryForList("GetAllAccountsViaCustomTypeHandler");

		$this->assertAccount1($list[0]);
		$this->assertEquals(5, count($list));
		$this->assertEquals(1, $list[0]->getID());
		$this->assertEquals(2, $list[1]->getID());
		$this->assertEquals(3, $list[2]->getID());
		$this->assertEquals(4, $list[3]->getID());
		$this->assertEquals(5, $list[4]->getID());

		$this->assertFalse($list[0]->getCartOptions());
		$this->assertFalse($list[1]->getCartOptions());
		$this->assertTrue($list[2]->getCartOptions());
		$this->assertTrue($list[3]->getCartOptions());
		$this->assertTrue($list[4]->getCartOptions());

		$this->assertTrue($list[0]->getBannerOptions());
		$this->assertTrue($list[1]->getBannerOptions());
		$this->assertFalse($list[2]->getBannerOptions());
		$this->assertFalse($list[3]->getBannerOptions());
		$this->assertTrue($list[4]->getBannerOptions());
	}

	/**
	 * Test CustomTypeHandler Oui/Non
	 */
	function testCustomTypeHandler()
	{
		$this->initScript('other-init.sql');
		$this->initScript('account-init.sql');

		$this->sqlmap->getTypeHandlerFactory()->register('OuiNonBool', new OuiNonBool());

		$other = new Other();
		$other->setInt(99);
		$other->setLong(1966);
		$other->setBool(true);
		$other->setBool2(false);
		$this->sqlmap->Insert("InsertCustomTypeHandler", $other);

		$anOther = $this->sqlmap->QueryForObject("SelectByInt", 99);
		$this->assertNotNull( $anOther );
		$this->assertEquals(99, (int)$anOther->getInt());
		$this->assertEquals(1966, (int)$anOther->getLong());
		$this->assertEquals(true, (boolean)$anOther->getBool());
		$this->assertEquals(false, (boolean)$anOther->getBool2());

	}

	/**
	 * Test CustomTypeHandler Oui/Non
	 */
	function testInsertInlineCustomTypeHandlerV1()
	{
		$this->initScript('other-init.sql');
		$this->initScript('account-init.sql');

		$other = new Other();
		$other->setInt(99);
		$other->setLong(1966);
		$other->setBool(true);
		$other->setBool2(false);

		$this->sqlmap->Insert("InsertInlineCustomTypeHandlerV1", $other);

		$anOther = $this->sqlmap->QueryForObject("SelectByIntV1", 99);

		$this->assertNotNull( $anOther );
		$this->assertEquals(99, (int)$anOther->getInt());
		$this->assertEquals(1966, (int)$anOther->getLong());
		$this->assertEquals(true, (boolean)$anOther->getBool());
		$this->assertEquals(false, (boolean)$anOther->getBool2());

	}

	/**
	 * Test CustomTypeHandler Oui/Non
	 */
	function testInsertInlineCustomTypeHandlerV2()
	{
		$this->initScript('other-init.sql');
		$this->initScript('account-init.sql');

		$other = new Other();
		$other->setInt(99);
		$other->setLong(1966);
		$other->setBool(true);
		$other->setBool2(false);

		$this->sqlmap->Insert("InsertInlineCustomTypeHandlerV2", $other);

		$anOther = $this->sqlmap->QueryForObject("SelectByInt", 99);

		$this->assertNotNull( $anOther );
		$this->assertEquals(99, (int)$anOther->getInt());
		$this->assertEquals(1966, (int)$anOther->getLong());
		$this->assertEquals(true, (boolean)$anOther->getBool());
		$this->assertEquals(false, (boolean)$anOther->getBool2());
	}
	#endregion 
	/**/
}

?>