<?php

namespace Prado\Test\Unit;

use Prado\Prado;
use Prado\TEventSubscription;
use Prado\TComponent;
use Prado\Collections\TMap;
use Prado\Exceptions\TInvalidOperationException;


class EventSubscriptionComponent extends TComponent
{
	public function onEvent()
	{
	}	
}

class TTestEventSubscription extends TEventSubscription
{
}


class TEventSubscriptionTest extends \PHPUnit\Framework\TestCase
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
		self::assertInstanceOf(\WeakReference::class, $this->subscription->getComponent(true));
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

	public function testGetArray_UnknownEvent_ReturnsNull()
	{
		$this->subscription = new $this->_baseClass($this->component, 'onNoSuchEvent');
		self::assertNull($this->subscription->getArray());
	}

	public function testSetComponentAndEvent_WhileSubscribed_Throws()
	{
		$this->subscription = new $this->_baseClass($this->component, 'onEvent', function ($sender, $param) {});
		self::assertTrue($this->subscription->getIsSubscribed());

		try {
			$this->subscription->setComponent(new EventSubscriptionComponent());
			self::fail('setComponent() did not throw while subscribed.');
		} catch (TInvalidOperationException $e) {
			self::assertEquals('eventsubscription_no_change', $e->getErrorCode());
		}

		self::expectException(TInvalidOperationException::class);
		$this->subscription->setEvent('onEvent');
	}

	public function testConstruct_ComponentWithoutEvent_DoesNotSubscribe()
	{
		$this->subscription = new $this->_baseClass($this->component, null, function ($sender, $param) {});

		self::assertSame($this->component, $this->subscription->getComponent());
		self::assertNull($this->subscription->getEvent());
		self::assertNull($this->subscription->getArray());
		self::assertFalse($this->subscription->getIsSubscribed());
	}

	public function testConstruct_EventWithoutComponent_DoesNotSubscribe()
	{
		$this->subscription = new $this->_baseClass(null, 'onEvent', function ($sender, $param) {});

		self::assertNull($this->subscription->getComponent());
		self::assertSame('onevent', $this->subscription->getEvent());
		self::assertNull($this->subscription->getArray());
		self::assertFalse($this->subscription->getIsSubscribed());
	}

	public function testGlobalEvent_WithoutComponent_SubscribesThroughApplication()
	{
		$received = [];
		$handler = function ($sender, $param) use (&$received) {
			$received[] = $param;
		};
		$globalHandlers = PradoUnit::getStaticProp(TComponent::class, '_ue');
		try {
			$this->subscription = new $this->_baseClass(null, 'fxEventSubscriptionGlobalTest', $handler);

			self::assertNull($this->subscription->getComponent());
			self::assertSame('fxeventsubscriptionglobaltest', $this->subscription->getEvent());
			self::assertSame(Prado::getApplication()->getEventHandlers('fxEventSubscriptionGlobalTest'), $this->subscription->getArray());
			self::assertTrue($this->subscription->getIsSubscribed());

			$this->component->raiseEvent('fxEventSubscriptionGlobalTest', $this->component, 'first');
			self::assertSame(['first'], $received);

			self::assertTrue($this->subscription->unsubscribe());
			$this->component->raiseEvent('fxEventSubscriptionGlobalTest', $this->component, 'second');
			self::assertSame(['first'], $received);
		} finally {
			$this->subscription = null;
			PradoUnit::setStaticProp(TComponent::class, '_ue', $globalHandlers);
		}
	}

	public function testGlobalEvent_WithoutComponentOrApplication_HasNoHandlers()
	{
		$app = Prado::getApplication();
		Prado::setApplication(null);
		try {
			$this->subscription = new $this->_baseClass(null, 'fxEventSubscriptionGlobalTest', function ($sender, $param) {});

			self::assertNull($this->subscription->getArray());
			self::assertFalse($this->subscription->getIsSubscribed());
		} finally {
			Prado::setApplication($app);
		}
	}
}
