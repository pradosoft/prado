<?php

require_once(__DIR__ . '/BaseCase.php');

class SelectKeyTest extends BaseCase
{
	public function __construct()
	{
		parent::__construct();
		$this->initSqlMap();

		//force autoload
		new Account;
		new Order;
		new LineItem;
		new LineItemCollection;
		new A;
		new B;
		new C;
		new D;
		new E;
		new F;
	}

	/**
	 * Test Insert with post GeneratedKey
	 */
	public function testInsertPostKey()
	{
		$this->initScript('line-item-init.sql');

		$item = new LineItem();

		$item->setId(10);
		$item->setCode("blah");
		$item->setOrder(new Order());
		$item->getOrder()->setId(9);
		$item->setPrice(44.00);
		$item->setQuantity(1);

		$key = $this->sqlmap->Insert("InsertLineItemPostKey", $item);

		$this->assertSame(99, $key);
		$this->assertSame(99, $item->getId());

		$param["Order_ID"] = 9;
		$param["LineItem_ID"] = 10;
		$testItem = $this->sqlmap->QueryForObject("GetSpecificLineItem", $param);

		$this->assertNotNull($testItem);
		$this->assertSame(10, $testItem->getId());

		$this->initScript('line-item-init.sql');
	}

	/**
	 * Test Insert pre GeneratedKey
	 */
	public function testInsertPreKey()
	{
		$this->initScript('line-item-init.sql');

		$item = new LineItem();

		$item->setId(10);
		$item->setCode("blah");
		$item->setOrder(new Order());
		$item->getOrder()->setId(9);
		$item->setPrice(44.00);
		$item->setQuantity(1);

		$key = $this->sqlmap->Insert("InsertLineItemPreKey", $item);

		$this->assertSame(99, $key);
		$this->assertSame(99, $item->getId());

		$param["Order_ID"] = 9;
		$param["LineItem_ID"] = 99;

		$testItem = $this->sqlmap->QueryForObject("GetSpecificLineItem", $param);

		$this->assertNotNull($testItem);
		$this->assertSame(99, $testItem->getId());

		$this->initScript('line-item-init.sql');
	}

	/**
	 * Test Test Insert No Key
	 */
	public function testInsertNoKey()
	{
		$this->initScript('line-item-init.sql');

		$item = new LineItem();

		$item->setId(100);
		$item->setCode("blah");
		$item->setOrder(new Order());
		$item->getOrder()->setId(9);
		$item->setPrice(44.00);
		$item->setQuantity(1);


		$key = $this->sqlmap->Insert("InsertLineItemNoKey", $item);

		$this->assertNull($key);
		$this->assertSame(100, $item->getId());

		$param["Order_ID"] = 9;
		$param["LineItem_ID"] = 100;

		$testItem = $this->sqlmap->QueryForObject("GetSpecificLineItem", $param);

		$this->assertNotNull($testItem);
		$this->assertSame(100, $testItem->getId());

		$this->initScript('line-item-init.sql');
	}
}
