<?php

use Prado\TEventSubscription;
use Prado\TComponent;


class EventSubscriptionComponent extends TComponent
{
	public function onEvent()
	{
	}	
}

class TTestEventSubscription extends TEventSubscription
{
}


class TEventSubscriptionTest extends PHPUnit\Framework\TestCase
{
	public $component = null;
	
	public $subscription = null;

	public $_baseClass = null;

	protected function newSubscription(): string
	{
		return  TTestEventSubscription::class;
	}
	
	protected function setUp(): void
	{
		$this->component = new EventSubscriptionComponent();
		$this->_baseClass = $this->newSubscription();
	}

	protected function tearDown(): void
	{
		$this->component = null;
		$this->subscription = null;
	}

	public function testConstruct()
	{
		$handler = function($sender, $param) {};
		// normal construct.
		$this->subscription = new $this->_baseClass($this->component, $event = 'onEvent', $handler, $priority = 5, false, $key = 0);
		$reference = $this->component->getEventHandlers('onEvent');
		self::assertEquals($this->component, $this->subscription->getComponent());
		self::assertEquals(strtolower($event), $this->subscription->getEvent());
		self::assertEquals($reference, $this->subscription->getArray());
		self::assertEquals($reference, $this->subscription->getCollection());
		self::assertEquals($key, $this->subscription->getKey());
		self::assertEquals($handler, $this->subscription->getItem());
		self::assertEquals($priority, $this->subscription->getPriority());
		self::assertFalse($this->subscription->getIsAssociative());
		self::assertFalse($this->subscription->getIsSubscribed());
		self::assertEquals([], $reference->toArray());
			
		self::assertTrue($this->subscription->subscribe());
		self::assertEquals([$key => $handler], $reference->toArray());
		self::assertTrue($this->subscription->getIsSubscribed());
		self::assertFalse($this->subscription->getIsAssociative());
		self::assertEquals(0, $this->subscription->getKey());
		self::assertTrue($this->subscription->unsubscribe());
		self::assertEquals([], $reference->toArray());
		self::assertEquals(null, $this->subscription->getKey());
			
		$this->subscription = new $this->_baseClass();
	}
	public function testComponent()
	{
		$reference = $this->component->getEventHandlers('onEvent');
		$this->subscription = new $this->_baseClass();
		
		$this->subscription->setComponent($this->component);
		self::assertInstanceOf(WeakReference::class, $this->subscription->getComponent(true));
	}

	public function testCollection_Array()
	{
		$reference = $this->component->getEventHandlers('onEvent');
		$this->subscription = new $this->_baseClass(null, null);
		
		self::assertNull($this->subscription->getComponent());
		self::assertNull($this->subscription->getEvent());
		self::assertNull($this->subscription->getCollection());
		
		$this->subscription->setComponent($this->component);
		self::assertNull($this->subscription->getCollection());
		
		$this->subscription->setComponent(null);
		$this->subscription->setEvent('onEvent');
		self::assertNull($this->subscription->getCollection());
		
		$this->subscription->setComponent($this->component);
		$this->subscription->setEvent('onEvent');
		self::assertEquals($reference, $this->subscription->getCollection());
		
		$this->subscription->setComponent(null);
		self::assertNull($this->subscription->getCollection());
		
		$this->subscription->setComponent($this->component);
		self::assertEquals($reference, $this->subscription->getCollection());
		$this->subscription->setEvent(null);
		self::assertNull($this->subscription->getCollection());
		
		self::expectException(TInvalidOperationException::class);
		$this->subscription->setCollection(new TMap());
	}

}
