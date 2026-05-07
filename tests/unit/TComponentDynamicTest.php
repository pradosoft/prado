<?php

require_once __DIR__ . '/TComponentTestBase.php';

use Prado\TComponent;
use Prado\TEventResults;
use Prado\Exceptions\TUnknownMethodException;
use Prado\Util\IDynamicMethods;

/**
 * Tests for TComponent's dynamic-event system: IDynamicMethods behaviors,
 * global-event raise listeners, dy* function call chaining, intra-object
 * dynamic events, and evaluateExpression() / evaluateStatements().
 */
class TComponentDynamicTest extends TComponentTestBase
{
	public function testIDynamicMethodsOnBehavior()
	{

	//Add Behavior with dynamic call
		$this->component->attachBehavior('TDynamicBehavior', new TDynamicBehavior);

		//Check that the behavior is working as it should
		$this->assertTrue($this->component->isa('TDynamicBehavior'));
		$this->assertEquals('dyAttachBehavior', $this->component->getLastBehaviorDynamicMethodCalled());

		// call basic behavior implemented method from object (containing behavior)
		$this->assertEquals(42, $this->component->TestBehaviorMethod(6, 7));

		//Test out undefined behavior/host object method
		try {
			$this->component->objectAndBehaviorUndefinedMethod();
			$this->fail('TUnknownMethodException not raised when evaluating an undefined method by the object and behavior');
		} catch (TUnknownMethodException $e) {
		}

		// calling undefined dynamic method, caught by the __dycall method in the behavior and implemented
		//	this behavior catches undefined dynamic event and divides param1 by param 2
		$this->assertEquals(22, $this->component->dyTestDynamicBehaviorMethod(242, 11));
		$this->assertEquals('dyTestDynamicBehaviorMethod', $this->component->getLastBehaviorDynamicMethodCalled());

		// calling undefined dynamic method, caught by the __dycall in the behavior and ignored
		$this->assertNull($this->component->dyUndefinedIntraEvent(242, 11));
		$this->assertEquals('dyUndefinedIntraEvent', $this->component->getLastBehaviorDynamicMethodCalled());

		//call behavior defined dynamic event
		//	param1 * 2 * param2
		$this->assertEquals(2420, $this->component->dyTestIntraEvent(121, 10));

		$this->component->detachBehavior('TDynamicBehavior');
		$this->assertFalse($this->component->isa('TDynamicBehavior'));



		//Add Class Behavior with dynamic call
		$this->component->attachBehavior('TDynamicClassBehavior', new TDynamicClassBehavior);

		//Check that the behavior is working as it should
		$this->assertTrue($this->component->isa('TDynamicClassBehavior'));
		$this->assertEquals('dyAttachBehavior', $this->component->getLastBehaviorDynamicMethodCalled());

		// call basic behavior implemented method from object (containing behavior)
		$this->assertEquals(42, $this->component->TestBehaviorMethod(6, 7));

		//Test out undefined behavior/host object method
		try {
			$this->component->objectAndBehaviorUndefinedMethod();
			$this->fail('TUnknownMethodException not raised when evaluating an undefined method by the object and behavior');
		} catch (TUnknownMethodException $e) {
		}

		// calling undefined dynamic method, caught by the __dycall method in the behavior and implemented
		//	this behavior catches undefined dynamic event and divides param1 by param 2
		$this->assertEquals(22, $this->component->dyTestDynamicClassBehaviorMethod(242, 11));
		$this->assertEquals('dyTestDynamicClassBehaviorMethod', $this->component->getLastBehaviorDynamicMethodCalled());

		// calling undefined dynamic method, caught by the __dycall in the behavior and ignored
		$this->assertNull($this->component->dyUndefinedIntraEvent(242, 11));
		$this->assertEquals('dyUndefinedIntraEvent', $this->component->getLastBehaviorDynamicMethodCalled());

		//call behavior defined dynamic event
		//	param1 * 2 * param2
		$this->assertEquals(2420, $this->component->dyTestIntraEvent(121, 10));

		$this->component->detachBehavior('TDynamicClassBehavior');
		$this->assertFalse($this->component->isa('TDynamicClassBehavior'));
	}

	// This also tests the priority of the common global raiseEvent events
	public function testIDynamicMethodsOnBehaviorGlobalEvents()
	{
		$component = new GlobalRaiseComponent();

		// common function has a default priority of 10
		$component->attachEventHandler(TComponent::GLOBAL_RAISE_EVENT_LISTENER, [$component, 'commonRaiseEventListener']);
		$component->attachEventHandler(TComponent::GLOBAL_RAISE_EVENT_LISTENER, [$component, 'postglobalRaiseEventListener'], 1);
		$component->attachEventHandler(TComponent::GLOBAL_RAISE_EVENT_LISTENER, [$component, 'preglobalRaiseEventListener'], -1);

		$this->assertEquals(5, $this->component->fxGlobalListener->getCount());
		$this->assertEquals(1, $this->component->fxPrimaryGlobalEvent->getCount());
		$this->assertEquals(1, $this->component->fxPrimaryGlobalEvent->getCount(), 'fxPrimaryGlobalEvent is not installed on test object');

		// call the global event on a different object than the test object
		$res = $this->component->raiseEvent('fxPrimaryGlobalEvent', $this, null, TEventResults::EVENT_RESULT_ALL);

		$this->assertEquals(6, count($res));
		$this->assertEquals(['pregl', 'primary', 'postgl', 'fxGL', 'fxcall', 'com'], $component->getCallOrders());

		$component->unlisten();

		//These are not 'fx' so these need to be removed individually.
		$component->detachEventHandler(TComponent::GLOBAL_RAISE_EVENT_LISTENER, [$component, 'commonRaiseEventListener']);
		$component->detachEventHandler(TComponent::GLOBAL_RAISE_EVENT_LISTENER, [$component, 'postglobalRaiseEventListener'], 1);
		$component->detachEventHandler(TComponent::GLOBAL_RAISE_EVENT_LISTENER, [$component, 'preglobalRaiseEventListener'], -1);
	}

	public function testEvaluateExpression()
	{
		$expression = "1+2";
		$this->assertTrue(3 === $this->component->evaluateExpression($expression));
		try {
			$button = $this->component->evaluateExpression('$this->button');
			$this->fail('exception not raised when evaluating an invalid exception');
		} catch (\Exception $e) {
		}
	}

	public function testEvaluateStatements()
	{
		$statements = '$a="test string"; echo $a;';
		$this->assertEquals('test string', $this->component->evaluateStatements($statements));
		try {
			$statements = '$a=new NewComponent; echo $a->button;';
			$button = $this->component->evaluateStatements($statements);
			$this->fail('exception not raised when evaluating an invalid statement');
		} catch (\Prado\Exceptions\TInvalidOperationException $e) {
			ob_end_flush();
		}
	}

	public function testDynamicFunctionCall()
	{
		$this->assertEquals(' aa bb cc __ .. ++ || !! ?? ', $this->component->dyTextFilter(' aa bb cc __ .. ++ || !! ?? '));

		$this->component->attachBehavior('dy1', new dy1TextReplace);
		$this->assertFalse($this->component->dy1->isCalled());
		$this->assertEquals(' aa bb cc __ __ ++ || !! ?? ', $this->component->dyTextFilter(' aa bb cc __ .. ++ || !! ?? '));
		$this->assertTrue($this->component->dy1->isCalled());

		$this->component->attachBehavior('dy2', new dy2TextReplace);
		$this->assertFalse($this->component->dy2->isCalled());
		$this->assertEquals(' aa bb cc __ __ || || !! ?? ', $this->component->dyTextFilter(' aa bb cc __ .. ++ || !! ?? '));
		$this->assertTrue($this->component->dy2->isCalled());

		$this->component->attachBehavior('dy3', new dy3TextReplace);
		$this->assertFalse($this->component->dy3->isCalled());
		$this->assertEquals(' aa bb cc __ __ || || ?? ?? ', $this->component->dyTextFilter(' aa bb cc __ .. ++ || !! ?? '));
		$this->assertTrue($this->component->dy3->isCalled());

		$this->assertEquals(' aa bb cc __ .. ++ || !! ?? ', $this->component->dyUndefinedEvent(' aa bb cc __ .. ++ || !! ?? '));

		$this->assertEquals(0.25, $this->component->dyPowerFunction(2, 2));


		$this->component->detachBehavior('dy1');
		$this->component->detachBehavior('dy2');
		$this->component->detachBehavior('dy3');

		//test class behaviors of dynamic events and the argument list order

		$this->assertEquals(' aa bb cc __ .. ++ || !! ?? ', $this->component->dyTextFilter(' aa bb cc __ .. ++ || !! ?? '));

		$this->component->attachBehavior('dy1', new dy1ClassTextReplace);
		$this->assertFalse($this->component->dy1->isCalled());
		$this->assertEquals(' aa bb cc .. .. ++ || !! ?? ', $this->component->dyTextFilter(' aa bb cc __ .. ++ || !! ?? '));
		$this->assertTrue($this->component->dy1->isCalled());

		$this->component->attachBehavior('dy2', new dy2ClassTextReplace);
		$this->assertFalse($this->component->dy2->isCalled());
		$this->assertEquals(' aa bb cc .. .. ++ ++ !! ?? ', $this->component->dyTextFilter(' aa bb cc __ .. ++ || !! ?? '));
		$this->assertTrue($this->component->dy2->isCalled());

		$this->component->attachBehavior('dy3', new dy3ClassTextReplace);
		$this->assertFalse($this->component->dy3->isCalled());
		$this->assertEquals(' aa bb cc .. .. ++ ++ !! ^_^ ', $this->component->dyTextFilter(' aa bb cc __ .. ++ || !! ?? '));
		$this->assertTrue($this->component->dy3->isCalled());

		$this->assertEquals(' aa bb cc __ .. ++ || !! ?? ', $this->component->dyUndefinedEvent(' aa bb cc __ .. ++ || !! ?? '));

		$this->assertEquals(0.25, $this->component->dyPowerFunction(2, 2));
	}

	public function testDynamicIntraObjectEvents()
	{
		$this->component->attachBehavior('IntraEvents', new IntraObjectExtenderBehavior);

		$this->assertEquals(11, $this->component->IntraEvents->LastCall);

		//unlisten first, this object listens upon instantiation.
		$this->component->unlisten();
		$this->assertEquals(2, $this->component->IntraEvents->LastCall);

		// ensures that IntraEvents nulls the last call variable when calling this getter
		$this->assertNull($this->component->IntraEvents->LastCall);

		//listen next to undo the unlisten
		$this->component->listen();
		$this->assertEquals(1, $this->component->IntraEvents->LastCall);


		$this->assertEquals(3, $this->component->evaluateExpression('1+2'));
		$this->assertEquals(7, $this->component->IntraEvents->LastCall);

		$statements = '$a="test string"; echo $a;';
		$this->assertEquals('test string', $this->component->evaluateStatements($statements));
		$this->assertEquals(8, $this->component->IntraEvents->LastCall);

		$component2 = new NewComponentNoListen();
		$this->assertNull($this->component->createdOnTemplate($component2));
		$this->assertEquals(9, $this->component->IntraEvents->LastCall);

		$this->assertNull($this->component->addParsedObject($component2));
		$this->assertEquals(10, $this->component->IntraEvents->LastCall);


		//  Attach new Barbehavior
		$behavior = new BarBehavior;
		$this->assertEquals($behavior, $this->component->attachBehavior('BarBehavior', $behavior));
		$this->assertEquals(11, $this->component->IntraEvents->LastCall);

		$this->assertNull($this->component->disableBehaviors());
		$this->assertNull($this->component->enableBehaviors());
		$this->assertEquals(27, $this->component->IntraEvents->LastCall);

		$this->assertTrue($this->component->disableBehavior('BarBehavior'));
		$this->assertEquals(16, $this->component->IntraEvents->LastCall);

		$this->assertTrue($this->component->enableBehavior('BarBehavior'));
		$this->assertEquals(15, $this->component->IntraEvents->LastCall);

		$this->assertEquals($behavior, $this->component->detachBehavior('BarBehavior'));
		$this->assertEquals(12, $this->component->IntraEvents->LastCall);


		$this->component->attachEventHandler('OnMyEvent', [$this->component, 'myEventHandler']);
		$this->component->raiseEvent('OnMyEvent', $this, null);

		//3 + 4 + 5 + 6 = 18 (the behavior adds these together when each raiseEvent dynamic intra event is called)
		$this->assertEquals(18, $this->component->IntraEvents->LastCall);
	}
}
