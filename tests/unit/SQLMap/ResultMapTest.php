<?php

require_once(dirname(__FILE__).'/BaseTest.php');

/**
 * @package System.DataAccess.SQLMap
 */
class ResultMapTest extends BaseTest
{
	function __construct()
	{
		parent::__construct();
		$this->initSqlMap();
		new Order;
		new LineItemCollection;
		new Account;
	}

	function resetDatabase()
	{
		$this->initScript('account-init.sql');
		$this->initScript('order-init.sql');
		$this->initScript('line-item-init.sql');
//		$this->initScript('enumeration-init.sql');
	}

	function testColumnsByName()
	{
		$order = $this->sqlmap->QueryForObject('GetOrderLiteByColumnName', 1);
		$this->assertOrder1($order);
	}

	function testColumnsByIndex() 
	{
		$order = $this->sqlmap->QueryForObject("GetOrderLiteByColumnIndex", 1);
		$this->assertOrder1($order);
	}


	function testExtendedResultMap()
	{
		$order = $this->sqlmap->queryForObject("GetOrderWithLineItemsNoLazyLoad", 1);
		$this->assertOrder1($order);
		$this->assertTrue($order->getLineItemsList() instanceof TList);
		$this->assertEquals(2, $order->getLineItemsList()->getCount());
	}

	function testLazyLoad()
	{
		$order = $this->sqlmap->QueryForObject("GetOrderWithLineItems", 1);
		$this->assertOrder1($order);
		$this->assertNotNull($order->getLineItemsList());
		$this->assertFalse($order->getLineItemsList() instanceof TList);
		$this->assertEquals(2, $order->getLineItemsList()->getCount());

		// After a call to a method from a proxy object,
		// the proxy object is replaced by the real object.
		$this->assertTrue($order->getLineItemsList() instanceof TList);
		$this->assertEquals(2, $order->getLineItemsList()->getCount());
	}

	function testLazyLoadWithOpenConnection() 
	{
		$this->sqlmap->openConnection();

		$this->testLazyLoad();
		$this->sqlmap->closeConnection(); 
	}

	function testLazyWithTypedCollectionMapping() 
	{
		$order = $this->sqlmap->queryForObject("GetOrderWithLineItemCollection", 1);
		$this->assertOrder1($order);
		$this->assertNotNull($order->getLineItems());
		$this->assertFalse($order->getLineItemsList() instanceof LineItemCollection);

		$this->assertEquals(2, $order->getLineItems()->getCount());

		// After a call to a method from a proxy object,
		// the proxy object is replaced by the real object.
		$this->assertTrue($order->getLineItems() instanceof LineItemCollection);
		foreach($order->getLineItems() as $item)
		{
			$this->assertNotNull($item);
			$this->assertTrue($item instanceof LineItem);
		}
	}

	function testNullValueReplacementOnString() 
	{
		$account = $this->sqlmap->queryForObject("GetAccountViaColumnName", 5);
		$this->assertEquals("no_email@provided.com", $account->getEmailAddress());
	}

	function testTypeSpecified()
	{
		$order = $this->sqlmap->queryForObject("GetOrderWithTypes", 1);
		$this->assertOrder1($order);
	}


	function testComplexObjectMapping()
	{
		$order = $this->sqlmap->queryForObject("GetOrderWithAccount", 1);
		$this->assertOrder1($order);
		$this->assertAccount1($order->getAccount());
	}

	function testCollectionMappingAndExtends() 
	{
		$order = $this->sqlmap->queryForObject("GetOrderWithLineItemsCollection", 1);
		$this->assertOrder1($order);

		// Check strongly typed collection
		$this->assertNotNull($order->getLineItems());
		$this->assertEquals(2, $order->getLineItems()->getCount());
	}

	function testListMapping() 
	{
		$order = $this->sqlmap->queryForObject("GetOrderWithLineItems", 1);
		$this->assertOrder1($order);

		// Check TList collection
		$this->assertNotNull($order->getLineItemsList());
		$this->assertEquals(2, $order->getLineItemsList()->getCount());
	}

	function testArrayMapping() 
	{
		$order = $this->sqlmap->queryForObject("GetOrderWithLineItemArray", 1);
		$this->assertOrder1($order);
		$this->assertNotNull($order->getLineItemsArray());
		$this->assertTrue(is_array($order->getLineItemsArray()));
		$this->assertEquals(2, count($order->getLineItemsArray()));
	}

	function testTypedCollectionMapping() 
	{
		$order = $this->sqlmap->queryForObject("GetOrderWithLineItemCollectionNoLazy", 1);
		$this->assertOrder1($order);
		$this->assertNotNull($order->getLineItems());
		$this->assertTrue($order->getLineItems() instanceof LineItemCollection);
		$this->assertEquals(2, $order->getLineItems()->getCount());
		foreach($order->getLineItems() as $item)
		{
			$this->assertNotNull($item);
			$this->assertTrue($item instanceof LineItem);
		}
	}

	function testHashArrayMapping() 
	{
		$order = $this->sqlmap->queryForObject("GetOrderAsHastable", 1);
		$this->assertOrder1AsHashArray($order);
	}

	function testNestedObjects() 
	{
		$order = $this->sqlmap->queryForObject("GetOrderJoinedFavourite", 1);

		$this->assertOrder1($order);
		$this->assertNotNull($order->getFavouriteLineItem());
		$this->assertEquals(2, (int)$order->getFavouriteLineItem()->getID());
		$this->assertEquals("ESM-23", $order->getFavouriteLineItem()->getCode());

	}
	function testNestedObjects2() 
	{
		$order = $this->sqlmap->queryForObject("GetOrderJoinedFavourite2", 1);
		$this->assertOrder1($order);

		$this->assertNotNull($order->getFavouriteLineItem());
		$this->assertEquals(2, (int)$order->getFavouriteLineItem()->getID());
		$this->assertEquals("ESM-23", $order->getFavouriteLineItem()->getCode());
	}

	function testImplicitResultMaps() 
	{
		$order = $this->sqlmap->queryForObject("GetOrderJoinedFavourite3", 1);
		
		// *** force date to timestamp since data type can't be 
		// *** explicity known without mapping
		$order->setDate(new TDateTime($order->getDate()));

		$this->assertOrder1($order);

		$this->assertNotNull($order->getFavouriteLineItem());
		$this->assertEquals(2, $order->getFavouriteLineItem()->getID());
		$this->assertEquals("ESM-23", $order->getFavouriteLineItem()->getCode());
	}

	function testCompositeKeyMapping() 
	{
		$this->resetDatabase();

		$order1 = $this->sqlmap->queryForObject("GetOrderWithFavouriteLineItem", 1);
		$order2 = $this->sqlmap->queryForObject("GetOrderWithFavouriteLineItem", 2);

		$this->assertNotNull($order1);
		$this->assertNotNull($order1->getFavouriteLineItem());
		$this->assertEquals(2, $order1->getFavouriteLineItem()->getID());

		$this->assertNotNull($order2);
		$this->assertNotNull($order2->getFavouriteLineItem());
		$this->assertEquals(1, $order2->getFavouriteLineItem()->getID());
	}

	function testSimpleTypeMapping() 
	{
		$this->resetDatabase();

		$list = $this->sqlmap->QueryForList("GetAllCreditCardNumbersFromOrders", null);

		$this->assertEquals(5, count($list));
		$this->assertEquals("555555555555", $list[0]);
	}


	function testDecimalTypeMapping() 
	{
		$this->resetDatabase();

		$param["LineItem_ID"] = 1;
		$param["Order_ID"] = 10;
		$price = $this->sqlmap->queryForObject("GetLineItemPrice", $param);
		$this->assertEquals(gettype($price), 'double');
		$this->assertEquals(45.43, $price);
	}

//todo 
/*
	function testNullValueReplacementOnEnum() 
	{
		$enum['Id'] = 99;
		$enum['Day'] = 'Days.Thu';
		$enum['Color'] = 'Colors.Blue';
		$enum['Month'] = 'Months.All';

		$this->sqlmap->insert("InsertEnumViaParameterMap", $enum);

		$enumClass = $this->sqlmap->queryForObject("GetEnumerationNullValue", 99);

		$this->assertEquals($enumClass['Day'], 'Days.Thu');
		$this->asserEquals($enumClass['Color'], 'Colors.Blue');
		$this->assertEquals($enumClass['Month'], 'Months.All');
	}


	function testByteArrayMapping()
	{
	}

	function testNullValueReplacementOnDecimal()
	{
	}

	function testNullValueReplacementOnDateTime()
	{
	}
*/

//future work

/*	
	//requires dynamic SQL
	function testDynamiqueCompositeKeyMapping() 
	{
		$order1 = $this->sqlmap->queryForObject("GetOrderWithDynFavouriteLineItem", 1);

		$this->assertNotNull($order1);
		$this->assertNotNull($order1->getFavouriteLineItem());
		var_dump($order1);
		$this->assertEquals(2, $order1->getFavouriteLineItem()->getID());
	}
*/

}

?>