<?php

require_once __DIR__ . '/TComponentTestBase.php';

use Prado\Collections\TPriorityList;
use Prado\Exceptions\TInvalidOperationException;

/**
 * Tests for TComponent's event system: hasEvent(), hasEventHandler(),
 * getEventHandlers(), attachEventHandler(), and detachEventHandler(),
 * including behavior-forwarded events and priority ordering.
 */
class TComponentEventTest extends TComponentTestBase
{
	public function testHasEvent()
	{
		$behaviorTestBehaviorName = 'BehaviorTestBehaviorName';

		$this->assertTrue($this->component->hasEvent('OnMyEvent'));
		$this->assertTrue($this->component->hasEvent('onmyevent'));
		$this->assertFalse($this->component->hasEvent('onYourEvent'));

		// fx won't throw an error if any of these fx function are called on an object.
		//	It is a special prefix event designation that every object responds to all events.
		$this->assertTrue($this->component->hasEvent('fxAttachClassBehavior'));
		$this->assertTrue($this->component->hasEvent('fxattachclassbehavior'));

		$this->assertTrue($this->component->hasEvent('fxNonExistantGlobalEvent'));
		$this->assertTrue($this->component->hasEvent('fxnonexistantglobalevent'));

		$this->assertTrue($this->component->hasEvent('dyNonExistantLocalEvent'));
		$this->assertTrue($this->component->hasEvent('dynonexistantlocalevent'));


		//Test behavior events
		$this->assertFalse($this->component->hasEvent('onBehaviorEvent'));
		$this->component->attachBehavior($behaviorTestBehaviorName, new BehaviorTestBehavior);
		$this->assertTrue($this->component->hasEvent('onBehaviorEvent'));
		$this->assertTrue($this->component->$behaviorTestBehaviorName->hasEvent('onBehaviorEvent'));

		$this->component->disableBehavior($behaviorTestBehaviorName);
		$this->assertFalse($this->component->hasEvent('onBehaviorEvent'));
		$this->component->enableBehavior($behaviorTestBehaviorName);
		$this->assertTrue($this->component->hasEvent('onBehaviorEvent'));

		$this->component->disableBehaviors();
		$this->assertFalse($this->component->hasEvent('onBehaviorEvent'));
		$this->component->enableBehaviors();
		$this->assertTrue($this->component->hasEvent('onBehaviorEvent'));
	}

	public function testHasEventHandler()
	{
		$behaviorTestBehaviorName = 'BehaviorTestBehaviorName';

		$this->assertFalse($this->component->hasEventHandler('OnMyEvent'));
		$this->component->attachEventHandler('OnMyEvent', 'foo');
		$this->assertTrue($this->component->hasEventHandler('OnMyEvent'));

		$this->assertFalse($this->component->hasEventHandler('fxNonExistantGlobalEvent'));
		$this->component->attachEventHandler('fxNonExistantGlobalEvent', 'foo');
		$this->assertTrue($this->component->hasEventHandler('fxNonExistantGlobalEvent'));

		//Test behavior events
		$this->assertFalse($this->component->hasEventHandler('onBehaviorEvent'));
		$this->component->attachBehavior($behaviorTestBehaviorName, new BehaviorTestBehavior);
		$this->assertFalse($this->component->hasEventHandler('onBehaviorEvent'));
		$this->assertFalse($this->component->$behaviorTestBehaviorName->hasEventHandler('onBehaviorEvent'));

		$this->component->attachEventHandler('onBehaviorEvent', 'foo');
		$this->assertTrue($this->component->hasEventHandler('onBehaviorEvent'));

		$this->component->disableBehavior($behaviorTestBehaviorName);
		$this->assertFalse($this->component->hasEvent('onBehaviorEvent'));
		$this->assertFalse($this->component->hasEventHandler('onBehaviorEvent'));
		$this->component->enableBehavior($behaviorTestBehaviorName);
		$this->assertTrue($this->component->hasEvent('onBehaviorEvent'));
		$this->assertTrue($this->component->hasEventHandler('onBehaviorEvent'));
	}

	public function testGetEventHandlers()
	{
		$list = $this->component->getEventHandlers('OnMyEvent');
		$this->assertTrue(($list instanceof TPriorityList) && ($list->getCount() === 0));
		$this->component->attachEventHandler('OnMyEvent', 'foo');
		$this->assertTrue(($list instanceof TPriorityList) && ($list->getCount() === 1));
		try {
			$list = $this->component->getEventHandlers('YourEvent');
			$this->fail('exception not raised when getting event handlers for undefined event');
		} catch (TInvalidOperationException $e) {
		}

		$list = $this->component->getEventHandlers('fxRandomEvent');
		$this->assertTrue(($list instanceof TPriorityList) && ($list->getCount() === 0));
		$this->component->attachEventHandler('fxRandomEvent', 'foo');
		$this->assertTrue(($list instanceof TPriorityList) && ($list->getCount() === 1));
		try {
			$list = $this->component->getEventHandlers('fxSomeUndefinedGlobalEvent');
		} catch (TInvalidOperationException $e) {
			$this->fail('exception raised when getting event handlers for universal global event');
		}



		//Test behavior events
		try {
			$list = $this->component->getEventHandlers('onBehaviorEvent');
			$this->fail('exception not raised when getting event handlers for undefined event');
		} catch (TInvalidOperationException $e) {
		}
		$this->assertFalse($this->component->hasEventHandler('onBehaviorEvent'));

		$behaviorTestBehaviorName = 'BehaviorTestBehaviorName';
		$this->component->attachBehavior($behaviorTestBehaviorName, new BehaviorTestBehavior);
		$list = $this->component->getEventHandlers('onBehaviorEvent');
		$this->assertTrue(($list instanceof TPriorityList) && ($list->getCount() === 0));
		$this->component->attachEventHandler('onBehaviorEvent', 'foo');
		$this->assertTrue(($list instanceof TPriorityList) && ($list->getCount() === 1));

		$this->component->disableBehavior($behaviorTestBehaviorName);
		try {
			$list = $this->component->getEventHandlers('onBehaviorEvent');
			$this->fail('exception not raised when getting event handlers for undefined event');
		} catch (TInvalidOperationException $e) {
		}
		$this->component->enableBehavior($behaviorTestBehaviorName);
		$this->assertTrue(($this->component->getEventHandlers('onBehaviorEvent') instanceof TPriorityList) && ($list->getCount() === 1));
	}

	public function testAttachEventHandler()
	{
		$this->component->attachEventHandler('OnMyEvent', 'foo');
		$this->assertEquals(1, $this->component->getEventHandlers('OnMyEvent')->getCount());
		try {
			$this->component->attachEventHandler('YourEvent', 'foo');
			$this->fail('exception not raised when attaching event handlers for undefined event');
		} catch (TInvalidOperationException $e) {
		}

		//Testing the priorities of attaching events
		$this->component->attachEventHandler('OnMyEvent', 'foopre', 5);
		$this->component->attachEventHandler('OnMyEvent', 'foopost', 15);
		$this->component->attachEventHandler('OnMyEvent', 'foobar', 10);
		$this->assertEquals(4, $this->component->getEventHandlers('OnMyEvent')->getCount());
		$list = $this->component->getEventHandlers('OnMyEvent');
		$this->assertEquals('foopre', $list[0]);
		$this->assertEquals('foo', $list[1]);
		$this->assertEquals('foobar', $list[2]);
		$this->assertEquals('foopost', $list[3]);


		//Test attaching behavior events
		try {
			$this->component->attachEventHandler('onBehaviorEvent', 'foo');
			$this->fail('exception not raised when getting event handlers for undefined event');
		} catch (TInvalidOperationException $e) {
		}
		$behaviorTestBehaviorName = 'BehaviorTestBehaviorName';
		$this->component->attachBehavior($behaviorTestBehaviorName, new BehaviorTestBehavior);

		$this->component->attachEventHandler('onBehaviorEvent', 'foo');

		//Testing the priorities of attaching behavior events
		$this->component->attachEventHandler('onBehaviorEvent', 'foopre', 5);
		$this->component->attachEventHandler('onBehaviorEvent', 'foopost', 15);
		$this->component->attachEventHandler('onBehaviorEvent', 'foobar', 10);
		$this->component->attachEventHandler('onBehaviorEvent', 'foobarfoobar', 10);
		$this->assertEquals(5, $this->component->getEventHandlers('onBehaviorEvent')->getCount());
		$list = $this->component->getEventHandlers('onBehaviorEvent');
		$this->assertEquals('foopre', $list[0]);
		$this->assertEquals('foo', $list[1]);
		$this->assertEquals('foobar', $list[2]);
		$this->assertEquals('foobarfoobar', $list[3]);
		$this->assertEquals('foopost', $list[4]);

		$this->component->disableBehavior($behaviorTestBehaviorName);
		try {
			$this->component->attachEventHandler('onBehaviorEvent', 'bar');
			$this->fail('exception not raised when getting event handlers for undefined event');
		} catch (TInvalidOperationException $e) {
		}
		$this->component->enableBehavior($behaviorTestBehaviorName);

		unset($this->component->OnMyEvent);
	}

	public function testDetachEventHandler()
	{
		$this->component->attachEventHandler('OnMyEvent', 'foo');
		$this->assertEquals(1, $this->component->getEventHandlers('OnMyEvent')->getCount());

		$this->component->attachEventHandler('OnMyEvent', 'foopre', 5);
		$this->component->attachEventHandler('OnMyEvent', 'foopost', 15);
		$this->component->attachEventHandler('OnMyEvent', 'foobar', 10);
		$this->component->attachEventHandler('OnMyEvent', 'foobarfoobar', 10);



		$this->component->detachEventHandler('OnMyEvent', 'foo');
		$list = $this->component->getEventHandlers('OnMyEvent');
		$this->assertEquals(4, $list->getCount());

		$this->assertEquals('foopre', $list[0]);
		$this->assertEquals('foobar', $list[1]);
		$this->assertEquals('foobarfoobar', $list[2]);
		$this->assertEquals('foopost', $list[3]);

		$this->component->detachEventHandler('OnMyEvent', 'foopre', null);
		$this->assertEquals(4, $list->getCount());

		$this->component->detachEventHandler('OnMyEvent', 'foopre', 5);
		$this->assertEquals(3, $list->getCount());


		// Now do detaching of behavior on events
		try {
			$this->component->attachEventHandler('onBehaviorEvent', 'foo');
			$this->fail('exception not raised when getting event handlers for undefined event');
		} catch (TInvalidOperationException $e) {
		}
		$behaviorTestBehaviorName = 'BehaviorTestBehaviorName';
		$this->component->attachBehavior($behaviorTestBehaviorName, new BehaviorTestBehavior);

		$this->component->attachEventHandler('onBehaviorEvent', 'foo');
		$this->assertEquals(1, $this->component->getEventHandlers('onBehaviorEvent')->getCount());

		$this->component->attachEventHandler('onBehaviorEvent', 'foopre', 5);
		$this->component->attachEventHandler('onBehaviorEvent', 'foopost', 15);
		$this->component->attachEventHandler('onBehaviorEvent', 'foobar', 10);
		$this->component->attachEventHandler('onBehaviorEvent', 'foobarfoobar', 10);



		$this->component->detachEventHandler('onBehaviorEvent', 'foo');
		$list = $this->component->getEventHandlers('onBehaviorEvent');
		$this->assertEquals(4, $list->getCount());

		$this->assertEquals('foopre', $list[0]);
		$this->assertEquals('foobar', $list[1]);
		$this->assertEquals('foobarfoobar', $list[2]);
		$this->assertEquals('foopost', $list[3]);

		$this->component->detachEventHandler('onBehaviorEvent', 'foopre', null);
		$this->assertEquals(4, $list->getCount());

		$this->component->detachEventHandler('onBehaviorEvent', 'foopre', 5);
		$this->assertEquals(3, $list->getCount());
	}

	public function testDetachEventHandlerReturnAndErrorHandling()
	{
		// No handlers attached at all: nothing to detach, returns false.
		$this->assertFalse($this->component->detachEventHandler('OnMyEvent', 'foo'));

		$this->component->attachEventHandler('OnMyEvent', 'foo');

		// Detaching a handler that is not attached swallows the
		// 'list_item_inexistent' TInvalidDataValueException and returns false.
		$this->assertFalse($this->component->detachEventHandler('OnMyEvent', 'notattached'));
		$this->assertEquals(1, $this->component->getEventHandlers('OnMyEvent')->getCount());

		// Detaching an attached handler succeeds and returns true.
		$this->assertTrue($this->component->detachEventHandler('OnMyEvent', 'foo'));
		$this->assertEquals(0, $this->component->getEventHandlers('OnMyEvent')->getCount());

		// Only the 'list_item_inexistent' TInvalidDataValueException is swallowed;
		// any other exception from remove() (e.g. a read-only 'list_readonly'
		// TInvalidOperationException) propagates rather than being masked as false.
	}
}
