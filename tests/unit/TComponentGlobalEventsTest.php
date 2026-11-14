<?php

namespace Prado\Test\Unit;

use Prado\TComponent;
use Prado\Util\IDynamicMethods;

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
		$this->component->attachClassBehavior('Bar', BarBehavior::class, NewComponentNoListen::class);
		$this->component->attachClassBehavior('FooBar', FooBarBehavior::class, NewComponent::class);

		//create new object with new class behaviors built in, defined in the two lines above
		$component = new NewComponentNoListen;

		$this->assertEquals(25, $component->moreFunction(2, 2));

		$this->assertEquals(25, $component->Bar->moreFunction(2, 2));
		$this->assertEquals(8, $component->FooBar->moreFunction(2, 2));

		$component->unlisten();// unwind object and class behaviors
		$this->component->detachClassBehavior('FooBar', NewComponent::class);
		$this->component->detachClassBehavior('Bar', NewComponentNoListen::class);
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

	public function testCloneOfAListeningComponentListens()
	{
		$handlers = $this->component->getEventHandlers('fxAttachClassBehavior');
		$count = $handlers->getCount();
		$this->assertTrue($this->component->getListeningToGlobalEvents());

		$copy = clone $this->component;
		$this->assertTrue($copy->getListeningToGlobalEvents());
		$this->assertEquals($count + 1, $handlers->getCount(), 'The clone registers its own global event handlers');
		$this->assertGreaterThan(0, $copy->unlisten());
		$this->assertEquals($count, $handlers->getCount());
		$this->assertTrue($this->component->getListeningToGlobalEvents(), 'The original still listens');

		$this->component->unlisten();
		$copy = clone $this->component;
		$this->assertFalse($copy->getListeningToGlobalEvents(), 'A clone of a component that does not listen does not listen');
		$this->assertNull($copy->unlisten());
		$this->assertEquals($count - 1, $handlers->getCount());
	}

	/**
	 * An unserialized component that listened when serialized registers its own
	 * global event handlers.  One that did not listen stays out of the lists.
	 */
	public function testWakeUpOfAListeningComponentListens()
	{
		$handlers = $this->component->getEventHandlers('fxAttachClassBehavior');
		$count = $handlers->getCount();
		$this->assertTrue($this->component->getListeningToGlobalEvents());

		$copy = unserialize(serialize($this->component));
		$this->assertTrue($copy->getListeningToGlobalEvents());
		$this->assertEquals($count + 1, $handlers->getCount(), 'The unserialized copy registers its own global event handlers');
		$this->assertTrue($handlers->contains([$copy, 'fxAttachClassBehavior']));
		$this->assertGreaterThan(0, $copy->unlisten());
		$this->assertEquals($count, $handlers->getCount());
		$this->assertTrue($this->component->getListeningToGlobalEvents(), 'The original still listens');

		$this->component->unlisten();
		$copy = unserialize(serialize($this->component));
		$this->assertFalse($copy->getListeningToGlobalEvents(), 'A copy of a component that does not listen does not listen');
		$this->assertNull($copy->unlisten());
		$this->assertEquals($count - 1, $handlers->getCount());
	}

	/**
	 * listen() and unlisten() register only the events getClassFxEvents() reports.
	 * A class reporting none changes its listening state, notifies its behaviors
	 * with an empty list, and returns 0.
	 */
	public function testListenAndUnlisten_classWithoutFxEvents()
	{
		$component = new TComponentGlobalEventsTestNoFxComponent();
		$behavior = new IntraObjectExtenderBehavior();
		$component->attachBehavior('extender', $behavior);
		$attachHandlers = $component->getEventHandlers('fxAttachClassBehavior');
		$count = $attachHandlers->getCount();

		$this->assertSame(0, $component->listen());
		$this->assertTrue($component->getListeningToGlobalEvents());
		$this->assertSame($count, $attachHandlers->getCount());
		$this->assertSame(1, $behavior->getLastCall());
		$this->assertSame([], $behavior->getLastArgumentList()[0]);
		$this->assertNull($component->listen());

		$this->assertSame(0, $component->unlisten());
		$this->assertFalse($component->getListeningToGlobalEvents());
		$this->assertSame($count, $attachHandlers->getCount());
		$this->assertSame(2, $behavior->getLastCall());
		$this->assertSame([], $behavior->getLastArgumentList()[0]);
		$this->assertNull($component->unlisten());
	}

	/**
	 * An IDynamicMethods class reporting no fx events registers only its __dycall as
	 * the global raise-event listener.
	 */
	public function testListenAndUnlisten_dynamicClassWithoutFxEvents()
	{
		$component = new TComponentGlobalEventsTestNoFxDynamicComponent();
		$behavior = new IntraObjectExtenderBehavior();
		$component->attachBehavior('extender', $behavior);
		$attachHandlers = $component->getEventHandlers('fxAttachClassBehavior');
		$listeners = $component->getEventHandlers(TComponent::GLOBAL_RAISE_EVENT_LISTENER);
		$count = $attachHandlers->getCount();
		$this->assertSame(0, $listeners->getCount());

		$this->assertSame(1, $component->listen());
		$this->assertTrue($component->getListeningToGlobalEvents());
		$this->assertSame($count, $attachHandlers->getCount());
		$this->assertSame([[$component, '__dycall']], $listeners->toArray());
		$this->assertSame([TComponent::GLOBAL_RAISE_EVENT_LISTENER], $behavior->getLastArgumentList()[0]);

		$this->assertSame(1, $component->unlisten());
		$this->assertFalse($component->getListeningToGlobalEvents());
		$this->assertSame(0, $listeners->getCount());
		$this->assertSame([TComponent::GLOBAL_RAISE_EVENT_LISTENER], $behavior->getLastArgumentList()[0]);
	}
}

/**
 * Component reporting no fx events for its class.
 */
class TComponentGlobalEventsTestNoFxComponent extends TComponent
{
	/**
	 * Reports no fx events.
	 * @param object $class the object to get the fx events of.
	 * @return string[] an empty list.
	 */
	protected function getClassFxEvents($class)
	{
		return [];
	}
}

/**
 * Component reporting no fx events that catches undefined dynamic and global events.
 */
class TComponentGlobalEventsTestNoFxDynamicComponent extends TComponentGlobalEventsTestNoFxComponent implements IDynamicMethods
{
	/**
	 * Passes the first argument through.
	 * @param string $method the dynamic or global event name.
	 * @param array $args the event arguments.
	 * @return mixed the first argument, or null without arguments.
	 */
	public function __dycall($method, $args)
	{
		return $args[0] ?? null;
	}
}
