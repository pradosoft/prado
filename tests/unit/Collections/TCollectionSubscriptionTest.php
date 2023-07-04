<?php

use Prado\Collections\TCollectionSubscription;
use Prado\Collections\TMap;

class TTestCollectionSubscription extends TCollectionSubscription
{
}


class TCollectionSubscriptionTest extends PHPUnit\Framework\TestCase
{
	public $subscription = null;

	public $_baseClass = null;

	protected function newSubscription(): string
	{
		return  TTestCollectionSubscription::class;
	}
	
	protected function setUp(): void
	{
		$this->_baseClass = $this->newSubscription();
		
	}

	protected function tearDown(): void
	{
		$this->subscription = null;
	}

	public function testConstruct()
	{
		$reference = $list = new TMap();

		// normal construct.
		$this->subscription = new $this->_baseClass($list, $key = 'key', $item = 3, $priority = 5, null, false);
		self::assertEquals($reference, $this->subscription->getCollection());
		self::assertEquals($reference, $this->subscription->getArray());
		self::assertEquals($key, $this->subscription->getKey());
		self::assertEquals($item, $this->subscription->getItem());
		self::assertEquals($priority, $this->subscription->getPriority());
		self::assertNull($this->subscription->getIsAssociative());
		self::assertFalse($this->subscription->getIsSubscribed());
		self::assertEquals([], $reference->toArray());
			
		self::assertTrue($this->subscription->subscribe());
		self::assertEquals([$key => $item], $reference->toArray());
	}
	
	public function testCollection()
	{
		$reference = $list = new TMap();
		
		$this->subscription = new $this->_baseClass($list, $key = 'key', $item = 3, $priority = 5, null, false);
		
		$list = null;
		self::assertEquals($reference, $this->subscription->getCollection());
		self::assertEquals($reference, $this->subscription->getArray());
		$list = & $this->subscription->getArray();
		$list = null;
		self::assertEquals($reference, $this->subscription->getCollection());
		self::assertEquals($reference, $this->subscription->getArray());
		
		$reference2 = $list2 = new TMap();
		$this->subscription->setCollection($list2);
		self::assertNull($list);
		self::assertEquals($reference, $this->subscription->getCollection());
		self::assertEquals($reference, $this->subscription->getArray());
	}
	

}
