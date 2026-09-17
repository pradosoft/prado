<?php

namespace Prado\Test\Unit\Data\SqlMap;

use Prado\Test\Unit\Data\SqlMap\Domain\A;
use Prado\Test\Unit\Data\SqlMap\Domain\Account;
use Prado\Test\Unit\Data\SqlMap\Domain\B;
use Prado\Test\Unit\Data\SqlMap\Domain\C;
use Prado\Test\Unit\Data\SqlMap\Domain\D;
use Prado\Test\Unit\Data\SqlMap\Domain\E;
use Prado\Test\Unit\Data\SqlMap\Domain\F;
use Prado\Test\Unit\Data\SqlMap\Domain\LineItem;
use Prado\Test\Unit\Data\SqlMap\Domain\LineItemCollection;
use Prado\Test\Unit\Data\SqlMap\Domain\Order;

class SelectKeyTest extends BaseCase
{
	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();
		self::initSqlMap();

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

		$key = self::$sqlmap->Insert("InsertLineItemPostKey", $item);

		$this->assertSame(99, $key);
		$this->assertSame(99, $item->getId());

		$param["Order_ID"] = 9;
		$param["LineItem_ID"] = 10;
		$testItem = self::$sqlmap->QueryForObject("GetSpecificLineItem", $param);

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

		$key = self::$sqlmap->Insert("InsertLineItemPreKey", $item);

		$this->assertSame(99, $key);
		$this->assertSame(99, $item->getId());

		$param["Order_ID"] = 9;
		$param["LineItem_ID"] = 99;

		$testItem = self::$sqlmap->QueryForObject("GetSpecificLineItem", $param);

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


		$key = self::$sqlmap->Insert("InsertLineItemNoKey", $item);

		$this->assertNull($key);
		$this->assertSame(100, $item->getId());

		$param["Order_ID"] = 9;
		$param["LineItem_ID"] = 100;

		$testItem = self::$sqlmap->QueryForObject("GetSpecificLineItem", $param);

		$this->assertNotNull($testItem);
		$this->assertSame(100, $testItem->getId());

		$this->initScript('line-item-init.sql');
	}
}
