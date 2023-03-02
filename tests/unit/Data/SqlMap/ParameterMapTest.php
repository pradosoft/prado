<?php

require_once(__DIR__ . '/BaseCase.php');

class ParameterMapTest extends BaseCase
{
	public function __construct()
	{
		parent::__construct();
		$this->initSqlMap();
	}

	protected function setUp(): void
	{
		$this->initScript('account-init.sql');
//		$this->initScript('account-procedure.sql');
		$this->initScript('order-init.sql');
//		$this->initScript('line-item-init.sql');
		$this->initScript('category-init.sql');
	}

	/// Test null replacement in ParameterMap property
	public function testNullValueReplacement()
	{
		$account = $this->newAccount6();

		$this->sqlmap->insert("InsertAccountViaParameterMap", $account);
		$account = $this->sqlmap->queryForObject("GetAccountNullableEmail", 6);

		$this->assertNull($account->getEmailAddress(), 'no_email@provided.com');

		$this->assertAccount6($account);
	}

	/// Test Test Null Value Replacement Inline
	public function testNullValueReplacementInline()
	{
		$account = $this->newAccount6();

		$this->sqlmap->insert("InsertAccountViaInlineParameters", $account);
		$account = $this->sqlmap->queryForObject("GetAccountNullableEmail", 6);
		$this->assertNull($account->getEmailAddress());

		$this->assertAccount6($account);
	}

	/// Test Test Null Value Replacement Inline
	public function testSpecifiedType()
	{
		$account = $this->newAccount6();
		$account->setEmailAddress(null);
		$this->sqlmap->insert("InsertAccountNullableEmail", $account);
		$account = $this->sqlmap->queryForObject("GetAccountNullableEmail", 6);
		$this->assertAccount6($account);
	}


	/// Test Test Null Value Replacement Inline
	public function testUnknownParameterClass()
	{
		$account = $this->newAccount6();
		$account->setEmailAddress(null);
		$this->sqlmap->insert("InsertAccountUknownParameterClass", $account);
		$account = $this->sqlmap->queryForObject("GetAccountNullableEmail", 6);
		$this->assertAccount6($account);
	}


	/// Test null replacement in ParameterMap property
	/// for System.DateTime.MinValue
	public function testNullValueReplacementForDateTimeMinValue()
	{
		$account = $this->newAccount6();
		$this->sqlmap->insert("InsertAccountViaParameterMap", $account);
		$order = new Order();
		$order->setId(99);
		$order->setCardExpiry("09/11");
		$order->setAccount($account);
		$order->setCardNumber("154564656");
		$order->setCardType("Visa");
		$order->setCity("Lyon");
		$order->setDate(null);
		$order->setPostalCode("69004");
		$order->setProvince("Rhone");
		$order->setStreet("rue Durand");

		$this->sqlmap->insert("InsertOrderViaParameterMap", $order);

		$orderTest = $this->sqlmap->queryForObject("GetOrderLiteByColumnName", 99);

		$this->assertSame($order->getCity(), $orderTest->getCity());
	}

	/// Test null replacement in ParameterMap/Hahstable property
	/// for System.DateTime.MinValue
	public function testNullValueReplacementForDateTimeWithHashtable()
	{
		$account = $this->newAccount6();

		$this->sqlmap->insert("InsertAccountViaParameterMap", $account);

		$order = new Order();
		$order->setId(99);
		$order->setCardExpiry("09/11");
		$order->setAccount($account);
		$order->setCardNumber("154564656");
		$order->setCardType("Visa");
		$order->setCity("Lyon");
		$order->setDate('0001-01-01 00:00:00'); //<-- null replacement
		$order->setPostalCode("69004");
		$order->setProvince("Rhone");
		$order->setStreet("rue Durand");

		$this->sqlmap->insert("InsertOrderViaParameterMap", $order);

		$orderTest = $this->sqlmap->queryForObject("GetOrderByHashTable", 99);

		$this->assertSame($orderTest["Date"], '0001-01-01 00:00:00');
	}

	/// Test null replacement in ParameterMap property
	/// for Guid
	public function testNullValueReplacementForGuidValue()
	{
		if ($this->hasSupportFor('last_insert_id')) {
			$category = new Category();
			$category->setName("Totoasdasd");
			$category->setGuidString('00000000-0000-0000-0000-000000000000');

			$key = $this->sqlmap->insert("InsertCategoryNull", $category);

			$categoryRead = $this->sqlmap->queryForObject("GetCategory", $key);

			$this->assertSame($category->getName(), $categoryRead->getName());
			$this->assertSame('', $categoryRead->getGuidString());
		} else {
			throw new PHPUnit\Framework\IncompleteTestError();
		}
	}



	/// Test complex mapping Via hasTable
	/// <example>
	///
	/// map.Add("Item", Item);
	/// map.Add("Order", Order);
	///
	/// <statement>
	/// ... #Item.prop1#...#Order.prop2#
	/// </statement>
	///
	/// </example>
	public function testComplexMappingViaHasTable()
	{
		$a = new Account();
		$a->setFirstName("Joe");

		$param["Account"] = $a;

		$o = new Order();
		$o->setCity("Dalton");
		$param["Order"] = $o;

		$accountTest = $this->sqlmap->queryForObject("GetAccountComplexMapping", $param);

		$this->assertAccount1($accountTest);
	}

	/*
		/// Test ByteArrayTypeHandler via Picture Property
		function testByteArrayTypeHandler()
		{
			$account = $this->newAccount6();

			$this->sqlmap->insert("InsertAccountViaParameterMap", $account);

			$order = new Order();
			$order->setId(99);
			$order->setCardExpiry("09/11");
			$order->setAccount($account);
			$order->setCardNumber("154564656");
			$order->setCardType("Visa");
			$order->setCity("Lyon");
			$order->setDate(0);
			$order->setPostalCode("69004");
			$order->setProvince("Rhone");
			$order->setStreet("rue Durand");

			$this->sqlmap->insert("InsertOrderViaParameterMap", $order);

			$item = new LineItem();
			$item->setId(99);
			$item->setCode("test");
			$item->setPrice(-99.99);
			$item->setQuantity(99);
			$item->setOrder($order);
			$item->setPicture(null);

			// Check insert
			$this->sqlmap->insert("InsertLineItemWithPicture", $item);

			// select
			$item = null;

			$param["LineItem_ID"] = 99;
			$param["Order_ID"] = 99;

			$item = $this->sqlmap->queryForObject("GetSpecificLineItemWithPicture", $param);

			$this->assertNotNull($item->getId());
	//		$this->assertNotNull($item->getPicture());
	//		$this->assertSame( GetSize(item.Picture), this.GetSize( this.GetPicture() ));
		}
	*/

	/// Test extend parameter map capacity
	/// (Support Requests 1043181)
	public function testInsertOrderViaExtendParameterMap()
	{
		$this->sqlmap->getSqlMapManager()->getTypeHandlers()->registerTypeHandler(new HundredsBool());

		$account = $this->newAccount6();
		$this->sqlmap->insert("InsertAccountViaParameterMap", $account);

		$order = new Order();
		$order->setId(99);
		$order->setCardExpiry("09/11");
		$order->setAccount($account);
		$order->setCardNumber("154564656");
		$order->setCardType("Visa");
		$order->setCity("Lyon");
		$order->setDate(null); //<-- null replacement
		$order->setPostalCode("69004");
		$order->setProvince("Rhone");
		$order->setStreet("rue Durand");

		$this->sqlmap->insert("InsertOrderViaExtendParameterMap", $order);

		$orderTest = $this->sqlmap->queryForObject("GetOrderLiteByColumnName", 99);

		$this->assertSame($order->getCity(), $orderTest->getCity());
	}
	/**/
}
