<?php

require_once __DIR__ . '/TComponentTestBase.php';

use Prado\TComponent;

/**
 * Tests for TComponent global-event listening: listen(), unlisten(),
 * getListeningToGlobalEvents(), and the constructor auto-listen behaviour.
 */
class TComponentGlobalEventsTest extends TComponentTestBase
{
	public function testGetListeningToGlobalEvents()
	{
		$this->assertEquals(true, $this->component->getListeningToGlobalEvents());
		$this->component->unlisten();
		$this->assertEquals(false, $this->component->getListeningToGlobalEvents());
	}


	public function testConstructorAutoListen()
	{
		// the default object auto installs class behavior hooks
		$this->assertEquals(1, $this->component->getEventHandlers('fxAttachClassBehavior')->getCount());
		$this->assertEquals(1, $this->component->getEventHandlers('fxDetachClassBehavior')->getCount());
		$this->assertTrue($this->component->getListeningToGlobalEvents());

		// this object does not auto install class behavior hooks, thus not changing the global event structure.
		//	Creating a new instance should _not_ influence the fxAttachClassBehavior and fxDetachClassBehavior
		//	count.
		$component_nolisten = new NewComponentNoListen();
		$this->assertEquals(1, $this->component->getEventHandlers('fxAttachClassBehavior')->getCount());
		$this->assertEquals(1, $this->component->getEventHandlers('fxDetachClassBehavior')->getCount());
		$this->assertEquals(1, $component_nolisten->getEventHandlers('fxAttachClassBehavior')->getCount());
		$this->assertEquals(1, $component_nolisten->getEventHandlers('fxDetachClassBehavior')->getCount());

		// tests order of class behaviors when a parent and class have class behavior.
		//	The child should override the parent object-oriented programming style
		$this->component->attachClassBehavior('Bar', 'BarBehavior', 'NewComponentNoListen');
		$this->component->attachClassBehavior('FooBar', 'FooBarBehavior', 'NewComponent');

		//create new object with new class behaviors built in, defined in the two lines above
		$component = new NewComponentNoListen;

		$this->assertEquals(25, $component->moreFunction(2, 2));

		$this->assertEquals(25, $component->Bar->moreFunction(2, 2));
		$this->assertEquals(8, $component->FooBar->moreFunction(2, 2));

		$component->unlisten();// unwind object and class behaviors
		$this->component->detachClassBehavior('FooBar', 'NewComponent');
		$this->component->detachClassBehavior('Bar', 'NewComponentNoListen');
	}


	public function testListenAndUnlisten()
	{
		$component = new NewComponentNoListen();

		$this->assertEquals(false, $component->getListeningToGlobalEvents());

		//This is from $this->component being instanced and listening.  $component is accessing the global event structure
		$this->assertEquals(1, $component->getEventHandlers('fxAttachClassBehavior')->getCount());

		$this->assertEquals(2, $component->listen());

		$this->assertEquals(true, $component->getListeningToGlobalEvents());

		//This is from $this->component being instanced and listening.  $component is accessing the global event structure
		$this->assertEquals(2, $component->getEventHandlers('fxAttachClassBehavior')->getCount());

		$this->assertEquals(2, $component->unlisten());

		$this->assertEquals(false, $component->getListeningToGlobalEvents());

		//This is from $this->component being instanced and listening.  $component is accessing the global event structure
		$this->assertEquals(1, $component->getEventHandlers('fxAttachClassBehavior')->getCount());
	}


	public function testListenAndUnlistenWithDynamicEventCatching()
	{
		$component = new DynamicCatchingComponent();

		$this->assertEquals(false, $component->getListeningToGlobalEvents());

		//This is from $this->component being instanced and listening.  $component is accessing the global event structure
		$this->assertEquals(0, $component->getEventHandlers(TComponent::GLOBAL_RAISE_EVENT_LISTENER)->getCount());

		// this adds the fxAttachClassBehavior, fxDetachClassBehavior, and __dycall of the component
		$this->assertEquals(3, $component->listen());

		$this->assertEquals(true, $component->getListeningToGlobalEvents());

		//This is from $this->component being instanced and listening.  $component is accessing the global event structure
		$this->assertEquals(1, $component->getEventHandlers(TComponent::GLOBAL_RAISE_EVENT_LISTENER)->getCount());

		$this->assertEquals(3, $component->unlisten());

		$this->assertEquals(false, $component->getListeningToGlobalEvents());

		//This is from $this->component being instanced and listening.  $component is accessing the global event structure
		$this->assertEquals(0, $component->getEventHandlers(TComponent::GLOBAL_RAISE_EVENT_LISTENER)->getCount());
	}

	/**
	 * Regression test for listen() / unlisten() return-type correctness.
	 *
	 * Both methods return the count of fx events registered/unregistered on a
	 * successful state change, but must return null (not 0 or false) when the
	 * object is already in the requested state — i.e., listen() when already
	 * listening, and unlisten() when already not listening.
	 */
	public function testListenUnlistenReturnNullWhenNoStateChange()
	{
		$component = new NewComponentNoListen();

		// Not yet listening — listen() should return a positive int.
		$count = $component->listen();
		$this->assertIsInt($count);
		$this->assertGreaterThan(0, $count);

		// Already listening — second listen() must return null, not 0 or false.
		$second = $component->listen();
		$this->assertNull($second, 'listen() must return null when already listening');

		// Unlisten — first call should return a positive int.
		$count = $component->unlisten();
		$this->assertIsInt($count);
		$this->assertGreaterThan(0, $count);

		// Already not listening — second unlisten() must return null.
		$second = $component->unlisten();
		$this->assertNull($second, 'unlisten() must return null when not listening');
	}
}
