<?php

require_once __DIR__ . '/TComponentTestFixtures.php';

use Prado\Collections\TPriorityList;
use Prado\Exceptions\TApplicationException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TUnknownMethodException;
use Prado\TComponent;
use Prado\TEventResults;
use Prado\Util\IDynamicMethods;
use Prado\Util\IInstanceCheck;
use Prado\Util\TBehavior;
use Prado\Util\TClassBehavior;

/**
 * @package System
 */
class TComponentRaiseEventTest extends PHPUnit\Framework\TestCase
{
	protected $tearDownScripts = [];
	protected $component;

	protected function setUp(): void
	{
		$component = new TComponent();
		$component->getEventHandlers('fxAttachClassBehavior')->clear();
		$component->getEventHandlers('fxDetachClassBehavior')->clear();
		unset($component);
		$this->tearDownScripts = [];
		$this->component = new NewComponent();
	}

	protected function tearDown(): void
	{
		//Closure may do something with the component.
		foreach ($this->tearDownScripts as $closure) {
			$closure();
		}
		$this->tearDownScripts = [];
		$this->component = null;

		$component = new NewComponent();

		$this->assertEquals([], $component->getBehaviors());
	}

	public function testRaiseEvent()
	{
		$component = new NewComponent();

		// object method callable
		$component->attachEventHandler('OnMyEvent', [$this->component, 'myEventHandler']);
		$this->assertFalse($this->component->isEventHandled());
		$this->assertFalse($this->component->Object->isEventHandled());
		$component->raiseEvent('OnMyEvent', $this, null);
		$this->assertTrue($this->component->isEventHandled());
		$this->assertFalse($this->component->Object->isEventHandled());

		$this->component->resetEventHandled();
		$this->component->Object->resetEventHandled();
		$component->detachEventHandler('OnMyEvent', [$this->component, 'myEventHandler']);

		// object sub-property method
		$component->attachEventHandler('OnMyEvent', [$this->component, 'Object.myEventHandler']);
		$this->assertFalse($this->component->isEventHandled());
		$this->assertFalse($this->component->Object->isEventHandled());
		$component->raiseEvent('OnMyEvent', $this, null);
		$this->assertFalse($this->component->isEventHandled());
		$this->assertTrue($this->component->Object->isEventHandled());

		$component->detachEventHandler('OnMyEvent', [$this->component, 'myEventHandler']);
		$this->component->resetEventHandled();
		$this->component->Object->resetEventHandled();

		// closure
		$raised = false;
		$eventCount = $component->OnMyEvent->count();
		$component->attachEventHandler('OnMyEvent', $closure = function () use (&$raised) {
			$raised = true;
		});
		$this->assertEquals($eventCount + 1, $component->OnMyEvent->count());
		$component->raiseEvent('OnMyEvent', $this, null);
		$this->assertTrue($raised);

		$component->detachEventHandler('OnMyEvent', $closure);
		$this->assertEquals($eventCount, $component->OnMyEvent->count());
		$this->component->resetEventHandled();
		$this->component->Object->resetEventHandled();


		// Test a behavior on event
		$this->component->attachBehavior('test', new BehaviorTestBehavior);

		$this->component->attachEventHandler('onBehaviorEvent', [$this->component, 'myEventHandler']);
		$this->assertFalse($this->component->isEventHandled());
		$this->component->raiseEvent('onBehaviorEvent', $this, null);
		$this->assertTrue($this->component->isEventHandled());
		$this->component->attachEventHandler('onBehaviorEvent', [$this->component, 'Object.myEventHandler']);
		$this->assertFalse($this->component->Object->isEventHandled());
		$this->component->raiseEvent('onBehaviorEvent', $this, null);
		$this->assertTrue($this->component->Object->isEventHandled());

		//test behavior enabled/disabled events
		$this->component->disableBehavior('test');

		$this->component->resetEventHandled();
		$this->component->Object->resetEventHandled();

		try {
			$this->component->attachEventHandler('onBehaviorEvent', [$this->component, 'myEventHandler']);
			$this->fail('exception not raised when getting event handlers for undefined event');
		} catch (Prado\Exceptions\TInvalidOperationException $e) {
		}
		$this->assertFalse($this->component->isEventHandled());
		try {
			$this->component->raiseEvent('onBehaviorEvent', $this, null);
			$this->fail('exception not raised when getting event handlers for undefined event');
		} catch (Prado\Exceptions\TInvalidOperationException $e) {
		}
		$this->assertFalse($this->component->isEventHandled());

		$this->component->enableBehavior('test');



		//Test the return types of this function

		$this->assertFalse($this->component->isEventHandled());
		$this->assertFalse($this->component->Object->isEventHandled());
		$this->assertEquals([], $this->component->onBehaviorEvent($this, $this->component));
		$this->assertTrue($this->component->isEventHandled());
		$this->assertTrue($this->component->Object->isEventHandled());

		// This accumulates all the responses from each of the events
		$arr = $this->component->onBehaviorEvent($this, $this->component, TEventResults::EVENT_RESULT_ALL);
		$this->assertEquals($this, $arr[0]['sender']);
		$this->assertEquals($this->component, $arr[0]['param']);
		$this->assertTrue(null === $arr[0]['response']);

		$this->assertEquals($this, $arr[1]['sender']);
		$this->assertEquals($this->component, $arr[1]['param']);
		$this->assertTrue(null === $arr[1]['response']);

		$this->assertEquals(2, count($arr));

		// This tests without the default filtering-out of null
		$arr = $this->component->onBehaviorEvent($this, $this->component, false);
		$this->assertEquals([null, null], $arr);


		unset($this->component->onBehaviorEvent);
		$this->assertEquals(0, $this->component->onBehaviorEvent->Count);

		$this->component->onBehaviorEvent = [$this, 'returnValue4'];
		$this->component->onBehaviorEvent = [$this, 'returnValue1'];

		// Test the per event post processing function
		$arr = $this->component->onBehaviorEvent($this, $this->component, [$this, 'postEventFunction']);
		$this->assertEquals([exp(4), exp(1)], $arr);
		$arr = $this->component->onBehaviorEvent($this, $this->component, [$this, 'postEventFunction2']);
		$this->assertEquals([sin(4), sin(1)], $arr);


		//Testing Feed-forward functionality
		unset($this->component->onBehaviorEvent);

		$this->component->onBehaviorEvent = [$this, 'ffValue4'];
		$this->component->onBehaviorEvent = [$this, 'ffValue2'];
		$arr = $this->component->onBehaviorEvent($this, 5, TEventResults::EVENT_RESULT_FEED_FORWARD);
		$this->assertEquals([20, 40], $arr);

		$arr = $this->component->onBehaviorEvent($this, 5, 0);
		$this->assertEquals([20, 10], $arr);

		$arr = $this->component->onBehaviorEvent($this, 5, TEventResults::EVENT_REVERSE);
		$this->assertEquals([10, 20], $arr);


		unset($this->component->onBehaviorEvent);

		//Order of these events affects the response order in feed forward
		$this->component->onBehaviorEvent = [$this, 'ffValue2'];
		$this->component->onBehaviorEvent = [$this, 'ffValue4'];
		$arr = $this->component->onBehaviorEvent($this, 5, TEventResults::EVENT_RESULT_FEED_FORWARD);
		$this->assertEquals([10, 40], $arr);
	}

	public function returnValue1()
	{
		return 1;
	}
	public function returnValue4()
	{
		return 4;
	}
	public function postEventFunction($sender, $param, $caller, $response)
	{
		return exp($response);
	}
	public function postEventFunction2($sender, $param, $caller, $response)
	{
		return sin($response);
	}
	public function ffValue2($sender, $param)
	{
		return $param * 2;
	}
	public function ffValue4($sender, $param)
	{
		return $param * 4;
	}

	public function testGlobalEventListenerInRaiseEvent()
	{
		//TODO Test the Global Event Listener
		throw new PHPUnit\Framework\IncompleteTestError();
	}
}
