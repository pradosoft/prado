<?php

require_once __DIR__ . '/TComponentTestBase.php';

use Prado\Exceptions\TApplicationException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TUnknownMethodException;
use Prado\Util\IDynamicMethods;
use Prado\Util\IInstanceCheck;

/**
 * Tests for TComponent instance-behavior management:
 * isa(), asa(), getClassHierarchy(), getBehaviors(), attachBehavior(),
 * detachBehavior(), attachBehaviors(), detachBehaviors(), clearBehaviors(),
 * enableBehavior(), and disableBehavior().
 */
class TComponentBehaviorTest extends TComponentTestBase
{
	public function testGetClassHierarchy()
	{
		$component = new DynamicCatchingComponent;
		$this->assertEquals([IDynamicMethods::class, DynamicCatchingTrait::class, DynamicCatchingComponent::class, NewComponentNoListen::class, NewComponentTestTrait::class, NewComponent::class, \Prado\TComponent::class], $component->getClassHierarchy());
		$this->assertEquals([IDynamicMethods::class, DynamicCatchingTrait::class, DynamicCatchingComponent::class, NewComponentNoListen::class, NewComponentTestTrait::class, NewComponent::class, \Prado\TComponent::class], $component->getClassHierarchy(false));
		$this->assertEquals([strtolower(IDynamicMethods::class), strtolower(DynamicCatchingTrait::class), strtolower(DynamicCatchingComponent::class), strtolower(NewComponentNoListen::class), strtolower(NewComponentTestTrait::class), strtolower(NewComponent::class), strtolower(\Prado\TComponent::class)], $component->getClassHierarchy(true));
	}

	/**
	 * Regression test for the getClassHierarchy() static-cache key bug.
	 *
	 * The do-while loop that walks get_parent_class() left $class set to false
	 * after exhausting the hierarchy.  The cache write then used false as the
	 * array key (coerced to 0 by PHP), so the lookup — which keys on the real
	 * class-name string — never hit and every call recomputed from scratch.
	 * The fix introduces a stable $origClass variable captured before the loop.
	 *
	 * We verify:
	 *  - TComponent appears in the hierarchy (not false or a numeric artifact).
	 *  - Both $lowercase variants return consistent results across repeated calls.
	 *  - Two different classes return their own distinct hierarchies, not each
	 *    other's (cross-contamination that would occur if both wrote to key [0]).
	 */
	public function testGetClassHierarchyCaching()
	{
		$component = new DynamicCatchingComponent();
		$plain1 = $component->getClassHierarchy(false);
		$plain2 = $component->getClassHierarchy(false);
		$lower1 = $component->getClassHierarchy(true);
		$lower2 = $component->getClassHierarchy(true);

		// Results must be stable across repeated calls (cache hit or recompute).
		$this->assertSame($plain1, $plain2, 'getClassHierarchy(false) returned different results on repeated calls');
		$this->assertSame($lower1, $lower2, 'getClassHierarchy(true) returned different results on repeated calls');

		// TComponent must appear in the hierarchy — not false/0 from the broken loop variable.
		$this->assertContains(\Prado\TComponent::class, $plain1, 'TComponent missing from plain hierarchy');
		$this->assertContains(strtolower(\Prado\TComponent::class), $lower1, 'TComponent missing from lowercase hierarchy');

		// A second, simpler class must return its own distinct hierarchy.
		$simple = new NewComponentNoListen();
		$simpleHierarchy = $simple->getClassHierarchy(false);
		$this->assertNotEquals($plain1, $simpleHierarchy, 'Different classes returned identical hierarchies — cache key collision');
		$this->assertContains(NewComponentNoListen::class, $simpleHierarchy);
		$this->assertNotContains(DynamicCatchingComponent::class, $simpleHierarchy);

		// Calling getClassHierarchy() on DynamicCatchingComponent again after
		// the NewComponentNoListen call must still return the correct result.
		$plain3 = $component->getClassHierarchy(false);
		$this->assertSame($plain1, $plain3, 'getClassHierarchy() result changed after another class computed its hierarchy');
	}


	public function testAsA()
	{
		$fooClassBehaviorName = 'FooClassBehaviorName';
		$foofooClassBehaviorName = 'FooFooClassBehavior';
		$barClassName = 'BarClassBehaviorName';
		$noBehaviorName = 'NoBehaviorInTheClassName';
		$anothercomponent = new NewComponent();

		// ensure the component does not have the FooClassBehavior
		$this->assertNull($this->component->asa($fooClassBehaviorName));
		$this->assertNull($this->component->asa($foofooClassBehaviorName));
		$this->assertNull($this->component->asa($barClassName));
		$this->assertNull($this->component->asa($noBehaviorName));

		$this->assertNull($anothercomponent->asa($fooClassBehaviorName));
		$this->assertNull($anothercomponent->asa($foofooClassBehaviorName));
		$this->assertNull($anothercomponent->asa($barClassName));
		$this->assertNull($anothercomponent->asa($noBehaviorName));

		// add the class behavior
		$this->component->attachClassBehavior($fooClassBehaviorName, new FooClassBehavior);
		$this->tearDownScripts[] = function() {$this->component->detachClassBehavior($fooClassBehaviorName);};

		//Check that the component has only the class behavior assigned
		$this->assertNotNull($this->component->asa($fooClassBehaviorName));
		$this->assertNotNull($this->component->asa(strtoupper($fooClassBehaviorName)));
		$this->assertNull($this->component->asa($foofooClassBehaviorName));
		$this->assertNull($this->component->asa($barClassName));
		$this->assertNull($this->component->asa($noBehaviorName));

		//Check that the component has only the class behavior assigned
		$this->assertNotNull($anothercomponent->asa($fooClassBehaviorName));
		$this->assertNull($anothercomponent->asa($foofooClassBehaviorName));
		$this->assertNull($anothercomponent->asa($barClassName));
		$this->assertNull($anothercomponent->asa($noBehaviorName));

		// remove the class behavior
		array_pop($this->tearDownScripts);
		$this->component->detachClassBehavior($fooClassBehaviorName);

		// Check the function doesn't have the behavior any more
		$this->assertNull($this->component->asa($fooClassBehaviorName));
		$this->assertNull($this->component->asa($foofooClassBehaviorName));
		$this->assertNull($this->component->asa($barClassName));
		$this->assertNull($this->component->asa($noBehaviorName));

		$this->assertNull($anothercomponent->asa($fooClassBehaviorName));
		$this->assertNull($anothercomponent->asa($foofooClassBehaviorName));
		$this->assertNull($anothercomponent->asa($barClassName));
		$this->assertNull($anothercomponent->asa($noBehaviorName));


		$fooBehaviorName = 'FooBehaviorName';
		$fooFooBehaviorName = 'FooFooBehavior';
		$behaviorName = 'BarBehaviorName';
		$noRegularBehaviorName = 'NonExistantBehavior';
		$this->component->attachBehavior($behaviorName, $bar = new BarBehavior);

		//Check that the component has only the object behavior assigned
		$this->assertNull($this->component->asa($fooBehaviorName));
		$this->assertNull($this->component->asa($fooFooBehaviorName));
		$this->assertEquals($bar, $this->component->asa($behaviorName));
		$this->assertNull($this->component->asa($noRegularBehaviorName));

		//Check that the component has the behavior assigned
		$this->assertNull($anothercomponent->asa($fooBehaviorName));
		$this->assertNull($anothercomponent->asa($fooFooBehaviorName));
		$this->assertNull($anothercomponent->asa($behaviorName));
		$this->assertNull($anothercomponent->asa($noRegularBehaviorName));

		$this->component->attachBehavior($fooBehaviorName, $foo = new FooBehavior);
		$this->component->attachBehavior($fooFooBehaviorName, $foofoo = new FooFooBehavior);

		$this->assertEquals($foo, $this->component->asa(FooBehavior::class));
		$this->assertEquals($foofoo, $this->component->asa(FooFooBehavior::class));
		$this->assertEquals($bar, $this->component->asa(BarBehavior::class));

		$this->component->detachBehavior($fooBehaviorName);
		$this->component->detachBehavior($fooFooBehaviorName);
		$this->component->detachBehavior($behaviorName);

		//Check that the component has no object behaviors assigned
		$this->assertNull($this->component->asa($fooBehaviorName));
		$this->assertNull($this->component->asa($fooFooBehaviorName));
		$this->assertNull($this->component->asa($behaviorName));
		$this->assertNull($this->component->asa($noRegularBehaviorName));

		//Check that the component has no behavior assigned
		$this->assertNull($anothercomponent->asa($fooBehaviorName));
		$this->assertNull($anothercomponent->asa($fooFooBehaviorName));
		$this->assertNull($anothercomponent->asa($behaviorName));
		$this->assertNull($anothercomponent->asa($noRegularBehaviorName));

		$anothercomponent->unlisten();
	}

	public function testIsA()
	{
		$this->component = new SubNewComponent();
		//This doesn't check the IInstanceCheck functionality, separate function

		$this->assertTrue($this->component->isa(\Prado\TComponent::class));
		$this->assertTrue($this->component->isa(NewComponent::class));
		$this->assertTrue($this->component->isa(NewComponentTestTrait::class));
		$this->assertTrue($this->component->isa(SubNewComponentTestTrait::class));
		$this->assertTrue($this->component->isa(SubNewComponentInterface::class));
		$this->assertTrue($this->component->isa(new SubNewComponent));
		$this->assertFalse($this->component->isa(new FooBehavior));
		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertFalse($this->component->isa(UnusedNewComponentTestTrait::class));

		$fooFooBehaviorName = 'FooFooBehaviorName';
		//Ensure there is no BarBehavior
		$this->assertNull($this->component->asa($fooFooBehaviorName));

		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertFalse($this->component->isa(FooFooBehavior::class));

		$this->component->attachBehavior($fooFooBehaviorName, new FooFooBehavior);

		$this->assertNotNull($this->component->asa($fooFooBehaviorName));

		$this->assertTrue($this->component->isa(FooBehavior::class));
		$this->assertTrue($this->component->isa(FooFooBehavior::class));

		$this->component->disableBehaviors();
		// It still has the behavior
		$this->assertNotNull($this->component->asa($fooFooBehaviorName));

		// But it is not expressed
		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertFalse($this->component->isa(FooFooBehavior::class));

		$this->component->enableBehaviors();
		$this->assertNotNull($this->component->asa($fooFooBehaviorName));

		$this->assertTrue($this->component->isa(FooFooBehavior::class));


		$fooBarBehaviorName = 'FooBarBehaviorName';
		$this->component->attachBehavior($fooBarBehaviorName, new FooBarBehavior);

		$this->assertTrue($this->component->isa(FooBehavior::class));
		$this->assertTrue($this->component->isa(FooBarBehavior::class));

		$this->component->disableBehavior($fooBarBehaviorName);

		$this->assertTrue($this->component->isa(FooBehavior::class));
		$this->assertFalse($this->component->isa(FooBarBehavior::class));

		$this->component->enableBehavior($fooBarBehaviorName);
		$this->component->disableBehavior($fooFooBehaviorName);
		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertFalse($this->component->isa(FooFooBehavior::class));
		$this->assertTrue($this->component->isa(FooBarBehavior::class));

		$this->component->disableBehavior($fooBarBehaviorName);
		$this->component->disableBehavior($fooFooBehaviorName);

		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertFalse($this->component->isa(FooFooBehavior::class));
		$this->assertFalse($this->component->isa(FooBarBehavior::class));

		$this->component->enableBehavior($fooBarBehaviorName);
		$this->component->enableBehavior($fooFooBehaviorName);

		$this->assertTrue($this->component->isa(FooFooBehavior::class));
		$this->assertTrue($this->component->isa(FooBarBehavior::class));


		$this->component->detachBehavior($fooFooBehaviorName);
		$this->component->detachBehavior($fooBarBehaviorName);

		$this->assertFalse($this->component->isa(new FooBehavior));
		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertFalse($this->component->isa(new FooFooBehavior));
		$this->assertFalse($this->component->isa(FooFooBehavior::class));
		$this->assertFalse($this->component->isa(new FooBarBehavior));
		$this->assertFalse($this->component->isa(FooBarBehavior::class));
	}

	public function testIsA_with_IInstanceCheck()
	{
		$this->assertTrue($this->component->isa('NewComponent'));
		$this->assertFalse($this->component->isa(PreBarBehavior::class));

		$this->component->attachBehavior('BarBehaviorName', $behavior = new BarBehavior);

		$behavior->setInstanceReturn(null);

		$this->assertTrue($this->component->isa('NewComponent'));
		$this->assertTrue($this->component->isa(PreBarBehavior::class));
		$this->assertFalse($this->component->isa(FooBehavior::class));

		// This forces the iso on the BarBehavior to respond to any class with false
		$behavior->setInstanceReturn(false);
		$this->assertFalse($this->component->isa(PreBarBehavior::class));
		$this->assertFalse($this->component->isa(FooBehavior::class));

		//This forces the isa on the BarBehavior to respond to any class with true
		$behavior->setInstanceReturn(true);
		$this->assertTrue($this->component->isa(FooBehavior::class));
	}

	public function testGetBehaviors()
	{
		$this->assertEquals([], $this->component->getBehaviors());
		$b = new FooFooBehavior();
		$behaviorName = 'aFooFooBehaviorName';
		$this->assertEquals($b, $this->component->attachBehavior($behaviorName, $b));
		$behaviorName = strtolower($behaviorName);
		$this->assertEquals([$behaviorName => $b], $this->component->getBehaviors());
		$b->setEnabled(false);
		$this->assertEquals([$behaviorName => $b], $this->component->getBehaviors());
		$b->setEnabled(true);
		$this->assertEquals([$behaviorName => $b], $this->component->getBehaviors());

		$b2 = new BarBehavior();
		$behaviorName2 = 'aBarBehaviorName';
		$this->assertEquals($b2, $this->component->attachBehavior($behaviorName2, $b2));
		$behaviorName2 = strtolower($behaviorName2);
		$this->assertEquals([$behaviorName => $b], $this->component->getBehaviors(FooFooBehavior::class));
		$this->assertEquals([$behaviorName2 => $b2], $this->component->getBehaviors(BarBehavior::class));
		$this->assertEquals([$behaviorName => $b], $this->component->getBehaviors(FooBehavior::class));
		$this->assertEquals([$behaviorName => $b], $this->component->getBehaviors(FooInterface::class));
		$this->assertEquals($b2, $this->component->detachBehavior($behaviorName2));
		$this->assertEquals(false, is_object($this->component->getBehaviors()));
		$this->assertEquals(true, is_array($this->component->getBehaviors()));
		$this->assertEquals($b, $this->component->detachBehavior($behaviorName));
		$this->assertEquals([], $this->component->getBehaviors());
	}

	public function testAttachDetachBehavior()
	{
		try {
			$this->component->faaEverMore(true, true);
			$this->fail('TUnknownMethodException not raised trying to execute a undefined class method');
		} catch (TUnknownMethodException $e) {
		}

		$this->assertNull($this->component->asa('FooBehavior'));
		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertFalse($this->component->isa(BarBehavior::class));

		try {
			$this->component->attachBehavior('FooBehavior', new \Prado\TComponent());
			$this->fail('TApplicationException trying to attach an object that is not a behavior without throwing error');
		} catch (TInvalidDataTypeException $e) {
		}

		//Instance TBehavior
		$behavior = new FooBehavior();
		try {	//  detaching without any attachment
			$behavior->detach(new \Prado\TComponent());
			$this->fail("Failed to throw TInvalidOperationException when detaching a TBehavior that isn't attached.");
		} catch (TInvalidOperationException $e) {
		}

		$this->component->attachBehavior('FooBehavior', $behavior);
		try {	//  attaching when already attached
			$behavior->attach(new \Prado\TComponent());
			$this->fail("Failed to throw TInvalidOperationException when attaching to a TBehavior that already has an owner.");
		} catch (TInvalidOperationException $e) {
		}
		try {	// detaching the wrong component.
			$behavior->detach(new \Prado\TComponent());
			$this->fail("Failed to throw TInvalidOperationException when detaching a TBehavior from the wrong owner.");
		} catch (TInvalidOperationException $e) {
		}

		$this->assertNotNull($this->component->asa('FooBehavior'));
		$this->assertTrue($this->component->isa(FooBehavior::class));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertFalse($this->component->isa(BarBehavior::class));

		try {
			$this->assertTrue($this->component->faaEverMore(true, true));
		} catch (TApplicationException $e) {
			$this->fail('TApplicationException raised while trying to execute a behavior class method');
		}

		try {
			$this->component->noMethodHere(true);
			$this->fail('TUnknownMethodException not raised trying to execute a undefined class method');
		} catch (TUnknownMethodException $e) {
		}

		$this->assertTrue($this->component->disableBehavior('FooBehavior'));

		//BarBehavior is not a behavior at this time
		$this->assertFalse($this->component->disableBehavior('BarBehavior'));

		try {
			$this->component->faaEverMore(true, true);
			$this->fail('TUnknownMethodException not raised trying to execute a undefined class method');
		} catch (TUnknownMethodException $e) {
		}

		$this->assertTrue($this->component->enableBehavior('FooBehavior'));

		//BarBehavior is not a behavior at this time
		$this->assertFalse($this->component->enableBehavior('BarBehavior'));

		try {
			$this->assertTrue($this->component->faaEverMore(true, true));
		} catch (TApplicationException $e) {
			$this->fail('TApplicationException raised while trying to execute a behavior class method');
		}

		// Instance from string, replace first behavior.

		$behavior->detached = 0;
		$this->component->attachBehavior('FooBehavior', 'FooBehavior');
		$this->assertEquals(1, $behavior->detached,  "Attaching a behavior over an existing behavior did not call detach on the prior behavior.");

		$this->component->detachBehavior('FooBehavior');

		$this->assertNull($this->component->asa('FooBehavior'));
		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertFalse($this->component->isa(BarBehavior::class));


		$this->component->attachBehavior(strtoupper('FooBehavior'), 'FooBehavior');

		$this->assertNotNull($this->component->asa(strtoupper('FooBehavior')));
		$this->assertNotNull($this->component->asa('FooBehavior'));
		$this->assertTrue($this->component->isa(FooBehavior::class));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertFalse($this->component->isa(BarBehavior::class));
		$this->assertEquals('default',$this->component->asa('FooBehavior')->PropertyA);

		$this->component->detachBehavior(strtolower('FooBehavior'));

		$this->assertNull($this->component->asa('FooBehavior'));
		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertFalse($this->component->isa(BarBehavior::class));

		// Anonymous null named behavior
		$this->component->attachBehavior(null, ['class' => 'FooBehavior', 'PropertyA'=>'anon_name_null']);

		$this->assertNotNull($this->component->asa(0));
		$this->assertTrue($this->component->isa(FooBehavior::class));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertFalse($this->component->isa(BarBehavior::class));
		$this->assertEquals('anon_name_null',$this->component->asa(0)->PropertyA);

		$this->component->detachBehavior(0);

		$this->assertNull($this->component->asa(0));
		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertFalse($this->component->isa(BarBehavior::class));


		// Anonymous number behavior
		$this->component->attachBehavior(11, ['class' => 'FooBehavior', 'PropertyA'=>'anon_name']);

		$this->assertNotNull($this->component->asa(1));
		$this->assertTrue($this->component->isa(FooBehavior::class));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertFalse($this->component->isa(BarBehavior::class));
		$this->assertEquals('anon_name',$this->component->asa(1)->PropertyA);

		$this->component->detachBehavior(1);

		$this->assertNull($this->component->asa(1));
		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertFalse($this->component->isa(BarBehavior::class));


		//Instance TClassBehavior
		$behavior = new FooClassBehavior();
		try {	//  detaching without any attachment
			$behavior->detach(new \Prado\TComponent());
			$this->fail("Failed to throw TInvalidOperationException when detaching a TClassBehavior that isn't attached.");
		} catch (TInvalidOperationException $e) {
		}
		$fooClassBehaviorName = 'FooClassBehavior';
		$this->component->attachBehavior($fooClassBehaviorName, $behavior);
		try {	//  attaching the same owner twice.
			$behavior->attach($this->component);
			$this->fail("Failed to throw TInvalidOperationException when attaching the same object twice to a TClassBehavior.");
		} catch (TInvalidOperationException $e) {
		}
		try {	// detaching the wrong component.
			$behavior->detach(new \Prado\TComponent());
			$this->fail("Failed to throw TInvalidOperationException when detaching from the wrong owner from a TClassBehavior.");
		} catch (TInvalidOperationException $e) {
		}

		$this->assertNotNull($this->component->asa($fooClassBehaviorName));
		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertNull($this->component->asa('BarBehavior'));
		$this->assertFalse($this->component->isa(BarBehavior::class));
	}


	public function testAttachBehaviorEventHandlersAtPriority()
	{
		$this->component->attachBehavior($name1 = 'TestWithEvents1', $b1 = new FooBehaviorWithEvents());
		$this->component->attachBehavior($name2 = 'TestWithEvents2', $b2 = new FooBehaviorWithEvents(), 3);

		$this->assertEquals('fooEventHandler', $b1->eventsLog()['onMyEvent'][0]);
		$this->assertInstanceOf('\Closure', $b1->eventsLog()['onMyEvent'][1]);
		$this->assertEquals(10, $this->component->onMyEvent->priorityOf([$b1, $b1->eventsLog()['onMyEvent'][0]]));
		$this->assertEquals(10, $this->component->onMyEvent->priorityOf($b1->eventsLog()['onMyEvent'][1]));
		$this->assertEquals(3, $this->component->onMyEvent->priorityOf([$b2, $b2->eventsLog()['onMyEvent'][0]]));
		$this->assertEquals(3, $this->component->onMyEvent->priorityOf($b2->eventsLog()['onMyEvent'][1]));
	}


	public function testAttachDetachBehaviors()
	{
		$fooBehaviorName = 'FooBehaviorName';
		$barBehaviorName = 'BarBehaviorName';
		$fooBarBehaviorName = 'FooBarBehaviorName';
		$preBarBehaviorName = 'PreBarBehaviorName';
		$fooFooBehaviorName = 'FooFooBehaviorName';
		$this->assertNull($this->component->asa($fooBehaviorName));
		$this->assertNull($this->component->asa($barBehaviorName));
		$this->assertNull($this->component->asa($fooBarBehaviorName));
		$this->assertNull($this->component->asa($preBarBehaviorName));

		$this->component->attachBehaviors([$fooFooBehaviorName => new FooFooBehavior, $barBehaviorName => new BarBehavior, $preBarBehaviorName => new PreBarBehavior]);

		$this->assertNull($this->component->asa($fooBehaviorName));
		$this->assertNotNull($this->component->asa($fooFooBehaviorName));
		$this->assertNotNull($this->component->asa($barBehaviorName));
		$this->assertNull($this->component->asa($fooBarBehaviorName));
		$this->assertNotNull($this->component->asa($preBarBehaviorName));

		$this->assertTrue($this->component->isa(FooFooBehavior::class));
		$this->assertTrue($this->component->isa(FooBehavior::class));
		$this->assertTrue($this->component->isa(BarBehavior::class));
		$this->assertTrue($this->component->isa(PreBarBehavior::class));
		$this->assertFalse($this->component->isa(FooBarBehavior::class));

		$this->component->detachBehaviors([$fooFooBehaviorName => new FooFooBehavior, $barBehaviorName => new BarBehavior]);

		$this->assertNull($this->component->asa($fooBehaviorName));
		$this->assertNull($this->component->asa($fooFooBehaviorName));
		$this->assertNull($this->component->asa($barBehaviorName));
		$this->assertNull($this->component->asa($fooBarBehaviorName));
		$this->assertNotNull($this->component->asa($preBarBehaviorName));

		$this->assertFalse($this->component->isa(FooFooBehavior::class));
		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertFalse($this->component->isa(BarBehavior::class));
		$this->assertFalse($this->component->isa(FooBarBehavior::class));
		$this->assertTrue($this->component->isa(PreBarBehavior::class));



		//	testing if we can detachBehaviors just by the name of the behavior instead of an array of the behavior
		$this->component->attachBehaviors([$fooFooBehaviorName => new FooFooBehavior, $barBehaviorName => new BarBehavior]);

		$this->assertTrue($this->component->isa(FooBehavior::class));
		$this->assertTrue($this->component->isa(BarBehavior::class));

		$this->component->detachBehaviors([$fooFooBehaviorName, $barBehaviorName]);

		$this->assertNull($this->component->asa($fooBehaviorName));
		$this->assertNull($this->component->asa($fooFooBehaviorName));
		$this->assertNull($this->component->asa($barBehaviorName));
		$this->assertNull($this->component->asa($fooBarBehaviorName));

		$this->assertFalse($this->component->isa(FooFooBehavior::class));
		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertFalse($this->component->isa(BarBehavior::class));
		$this->assertFalse($this->component->isa(FooBarBehavior::class));
	}


	public function testClearBehaviors()
	{
		$fooBehaviorName = 'FooBehaviorName';
		$barBehaviorName = 'BarBehaviorName';
		$fooBarBehaviorName = 'FooBarBehaviorName';
		$preBarBehaviorName = 'PreBarBehaviorName';
		$fooFooBehaviorName = 'FooFooBehaviorName';

		$this->assertNull($this->component->asa($fooBehaviorName));
		$this->assertNull($this->component->asa($barBehaviorName));
		$this->assertNull($this->component->asa($fooBarBehaviorName));
		$this->assertNull($this->component->asa($preBarBehaviorName));

		$this->component->attachBehaviors([$fooFooBehaviorName => new FooFooBehavior, $barBehaviorName => new BarBehavior, $preBarBehaviorName => new PreBarBehavior]);

		$this->assertNull($this->component->asa($fooBehaviorName));
		$this->assertNotNull($this->component->asa($fooFooBehaviorName));
		$this->assertNotNull($this->component->asa($barBehaviorName));
		$this->assertNull($this->component->asa($fooBarBehaviorName));
		$this->assertNotNull($this->component->asa($preBarBehaviorName));

		$this->component->clearBehaviors();

		$this->assertNull($this->component->asa($fooBehaviorName));
		$this->assertNull($this->component->asa($barBehaviorName));
		$this->assertNull($this->component->asa($fooBarBehaviorName));
		$this->assertNull($this->component->asa($preBarBehaviorName));
	}

	public function testEnableDisableBehavior()
	{
		$behaviorName = 'FooBehaviorName';

		$this->assertFalse($this->component->enableBehavior($behaviorName));
		$this->assertFalse($this->component->disableBehavior($behaviorName));
		$this->assertEquals(0, $this->component->onMyEvent->getCount());

		try {
			$this->component->faaEverMore(true, true);
			$this->fail('TUnknownMethodException not raised trying to execute a undefined class method');
		} catch (TUnknownMethodException $e) {
		}

		// *** Test TBehavior

		$fooB = new FooBehaviorWithEvents();
		try { // set name without owner
			$fooB->setName('initialName');
		} catch(TInvalidOperationException $e) {
			$this->fail("TBehavior wasn't able to set the name. \n" . $e->getErrorMessage());
		}
		$eventsLog = $fooB->eventsLog();
		$this->assertEquals(1, count($eventsLog), "TBehavior::eventsLog not returning the 1 test event with handlers");
		$this->assertEquals(2, count($eventsLog['onMyEvent']), "TBehavior::eventsLog not returning the 2 event handlers");
		$this->assertEquals('fooEventHandler', $eventsLog['onMyEvent'][0]);
		$this->assertInstanceOf(\Closure::class, $eventsLog['onMyEvent'][1]);
		$this->assertNull($fooB->getOwner());
		$this->assertEquals([], $fooB->getOwners());
		$this->assertFalse($fooB->hasOwner());
		$this->assertFalse($fooB->isOwner($this->component));

		//  Attach TBehavior

		$this->component->attachBehavior($behaviorName, $fooB);
		$this->assertEquals($this->component, $fooB->getOwner());
		$this->assertEquals([$this->component], $fooB->getOwners());
		$this->assertTrue($fooB->hasOwner());
		$this->assertTrue($fooB->isOwner($this->component));
		$this->assertFalse($fooB->isOwner(new \Prado\TComponent()));
		$this->assertEquals(2, $this->component->onMyEvent->getCount(), "TBehavior not adding events to the owner properly.");
		$fooB->syncEventHandlers(null, null);
		$this->assertEquals(0, $this->component->onMyEvent->getCount());
		$fooB->syncEventHandlers(null, false);
		$this->assertEquals(2, $this->component->onMyEvent->getCount());

		try {// set name after attaching to owner error.
			$fooB->setName('initialName');
			$this->fail("TInvalidOperationException was not thrown.  Names can't change after they have owners.");
		} catch(TInvalidOperationException $e) {
		}
		try { // set the when the name doesn't change, no error
			$fooB->setName($behaviorName);
		} catch(TInvalidOperationException $e) {
			$this->fail("TBehavior has an error when setting the name to the same set name (in the owner) and shouldn't have an error. \n" . $e->getErrorMessage());
		}

		$this->assertTrue($this->component->isa(FooBehavior::class));
		try {
			$this->assertTrue($this->component->faaEverMore(true, true));
		} catch (TApplicationException $e) {
			$this->fail('TApplicationException raised while trying to execute a behavior class method');
		}
		$fooB->syncEventHandlers(null, null);
		$this->assertEquals(0, $this->component->onMyEvent->getCount());
		$fooB->syncEventHandlers(null, false);
		$this->assertEquals(2, $this->component->onMyEvent->getCount());

		//Test upper case name as well.
		$this->assertTrue($this->component->disableBehavior(strtoupper($behaviorName)));
		$this->assertEquals(0, $this->component->onMyEvent->getCount());
		$this->assertFalse($this->component->isa(FooBehavior::class));
		$this->assertEquals(0, $this->component->onMyEvent->getCount());
		$fooB->syncEventHandlers(null, true);
		$this->assertEquals(2, $this->component->onMyEvent->getCount());
		$fooB->syncEventHandlers(null, false);
		$this->assertEquals(0, $this->component->onMyEvent->getCount());

		try {
			$this->component->faaEverMore(true, true);
			$this->fail('TUnknownMethodException not raised trying to execute a undefined class method');
		} catch (TUnknownMethodException $e) {
		}

		//Test upper case name as well.
		$this->assertTrue($this->component->enableBehavior(strtoupper($behaviorName)));
		$this->assertTrue($this->component->isa(FooBehavior::class));
		$this->assertEquals(2, $this->component->onMyEvent->getCount());

		try {
			$this->assertTrue($this->component->faaEverMore(true, true));
		} catch (TApplicationException $e) {
			$this->fail('TApplicationException raised while trying to execute a behavior class method');
		}

		//  *** Test TClassBehavior

		$className = 'BarClassBehaviorName'; //Name of the TBehavior test object.

		$this->assertFalse($this->component->enableBehavior($className));
		$this->assertEquals(2, $this->component->onMyEvent->getCount());
		$this->assertFalse($this->component->disableBehavior($className));
		$this->assertEquals(2, $this->component->onMyEvent->getCount());

		try {
			$this->component->moreFunction(true, true);
			$this->fail('TUnknownMethodException not raised trying to execute an undefined class method');
		} catch (TUnknownMethodException $e) {
		}

		// instance

		$classBehavior = new BarClassBehaviorWithEvents();
		try { // set name without owner
			$classBehavior->setName('initialName');
		} catch(TInvalidOperationException $e) {
			$this->fail("TClassBehavior wasn't able to set the name. \n" . $e->getErrorMessage());
		}
		$this->assertEquals([], $classBehavior->getOwners());
		$this->assertFalse($classBehavior->hasOwner());
		$this->assertFalse($classBehavior->isOwner($this->component));

		// Attach

		$this->component->attachClassBehavior($className, $classBehavior);
		$this->assertEquals([$this->component], $classBehavior->getOwners());
		$this->assertTrue($classBehavior->hasOwner());
		$this->assertTrue($classBehavior->isOwner($this->component));
		$this->assertFalse($classBehavior->isOwner(new \Prado\TComponent()));

		try {// set name after attaching to owner error.
			$classBehavior->setName('initialName');
			$this->fail("TInvalidOperationException was not thrown.  Names can't change after they have owners.");
		} catch(TInvalidOperationException $e) {
		}
		try { // set the when the name doesn't change, no error
			$classBehavior->setName($className);
		} catch(TInvalidOperationException $e) {
			$this->fail("TBehavior has an error when setting the name to the same set name (in the owner) and shouldn't have an error. \n" . $e->getErrorMessage());
		}

		$this->tearDownScripts[] = function() use ($className) {$this->component->detachClassBehavior($className);};
		$this->assertEquals([$this->component], $classBehavior->getOwners());

		$this->assertInstanceOf(BarClassBehaviorWithEvents::class, $this->component->asa($className));
		$this->assertEquals(4, $this->component->onMyEvent->getCount(), "TClassBehavior did not attach its handlers.");
		$this->assertTrue($this->component->enableBehavior($className));
		$classBehavior->syncEventHandlers(null, null);
		$this->assertEquals(2, $this->component->onMyEvent->getCount());
		$classBehavior->syncEventHandlers(null, false);
		$this->assertEquals(4, $this->component->onMyEvent->getCount());
		$this->assertEquals(4, $this->component->onMyEvent->getCount());
		$this->assertTrue($this->component->disableBehavior($className));
		$this->assertEquals(2, $this->component->onMyEvent->getCount());
		$classBehavior->syncEventHandlers(null, true);
		$this->assertEquals(4, $this->component->onMyEvent->getCount());
		$classBehavior->syncEventHandlers(null, false);
		$this->assertEquals(2, $this->component->onMyEvent->getCount());

		try {
			$this->assertTrue($this->component->moreFunction(true, true));
			$this->fail('TUnknownMethodException not raised while trying to execute a disabled behavior class method');
		} catch (TUnknownMethodException $e) {
		}
		$this->assertTrue($this->component->enableBehavior($className));
		$this->assertEquals(4, $this->component->onMyEvent->getCount());

		{
			$this->component->disableBehaviors();
			$this->assertEquals(0, $this->component->onMyEvent->getCount(), "The behaviors were not turned off when the component behaviors were flagged as off.");

			$this->assertTrue($this->component->disableBehavior($behaviorName));
			$this->assertEquals(0, $this->component->onMyEvent->getCount());
			$this->assertTrue($this->component->enableBehavior($behaviorName));
			$this->assertEquals(0, $this->component->onMyEvent->getCount());

			$this->assertTrue($this->component->disableBehavior($className));
			$this->assertEquals(0, $this->component->onMyEvent->getCount());
			$this->assertTrue($this->component->enableBehavior($className));
			$this->assertEquals(0, $this->component->onMyEvent->getCount());
		}

		$this->component->enableBehaviors();
		$this->assertEquals(4, $this->component->onMyEvent->getCount());
		$this->component->disableBehaviors();
		$this->assertTrue($this->component->disableBehavior($behaviorName));
		$this->assertTrue($this->component->disableBehavior($className));
		$this->component->enableBehaviors();
		$this->assertEquals(0, $this->component->onMyEvent->getCount());
		$this->component->disableBehaviors();
		$this->assertTrue($this->component->enableBehavior($behaviorName));
		$this->component->enableBehaviors();
		$this->assertEquals(2, $this->component->onMyEvent->getCount());

		$this->assertTrue($this->component->enableBehavior($className));
		$this->assertEquals(4, $this->component->onMyEvent->getCount());

		{	// Test RetainDisabledHandlers = false on TBehavior and TClassBehavior
			$this->assertTrue($this->component->disableBehavior($behaviorName));
			$this->assertTrue($this->component->disableBehavior($className));
			$this->assertEquals(0, $this->component->onMyEvent->getCount());
			$fooB->setRetainDisabledHandlers(true);
			$this->assertTrue($fooB->getRetainDisabledHandlers());
			$this->assertEquals(2, $this->component->onMyEvent->getCount());
			$fooB->setRetainDisabledHandlers(null);
			$this->assertNull($fooB->getRetainDisabledHandlers());
			$fooB->setRetainDisabledHandlers('null');
			$this->assertNull($fooB->getRetainDisabledHandlers());
			$fooB->setRetainDisabledHandlers(0);
			$this->assertNull($fooB->getRetainDisabledHandlers());
			$fooB->setRetainDisabledHandlers('0');
			$this->assertNull($fooB->getRetainDisabledHandlers());
			$this->assertEquals(0, $this->component->onMyEvent->getCount());
			$fooB->setRetainDisabledHandlers(true);
			$this->assertEquals(2, $this->component->onMyEvent->getCount());
			$fooB->setRetainDisabledHandlers('false');
			$this->assertFalse($fooB->getRetainDisabledHandlers());
			$this->assertEquals(0, $this->component->onMyEvent->getCount());
			$fooB->setRetainDisabledHandlers(true);
			$this->assertEquals(2, $this->component->onMyEvent->getCount());
			$classBehavior->setRetainDisabledHandlers(true);
			$this->assertEquals(4, $this->component->onMyEvent->getCount());
			$classBehavior->setRetainDisabledHandlers(null);
			$this->assertEquals(2, $this->component->onMyEvent->getCount());
			$classBehavior->setRetainDisabledHandlers(true);
			$this->assertEquals(4, $this->component->onMyEvent->getCount());
			$this->assertTrue($this->component->enableBehavior($behaviorName));
			$this->assertTrue($this->component->enableBehavior($className));
			$this->assertEquals(4, $this->component->onMyEvent->getCount());
			$fooB->setRetainDisabledHandlers(null);
			$this->assertEquals(2, $this->component->onMyEvent->getCount());
			$fooB->setRetainDisabledHandlers(false);
			$this->assertEquals(4, $this->component->onMyEvent->getCount());
			$fooB->setRetainDisabledHandlers(true);
			$this->assertEquals(4, $this->component->onMyEvent->getCount());

			$this->assertTrue($this->component->disableBehavior($behaviorName));
			$this->assertTrue($this->component->disableBehavior($className));
			$this->assertEquals(4, $this->component->onMyEvent->getCount());
			$this->component->disableBehaviors();
			$this->assertEquals(4, $this->component->onMyEvent->getCount());
			$this->assertTrue($this->component->enableBehavior($behaviorName));
			$this->assertTrue($this->component->enableBehavior($className));
			$this->assertEquals(4, $this->component->onMyEvent->getCount());
			$this->assertTrue($this->component->disableBehavior($behaviorName));
			$this->assertTrue($this->component->disableBehavior($className));
			$this->assertEquals(4, $this->component->onMyEvent->getCount());
			$this->component->enableBehaviors();
			$this->assertEquals(4, $this->component->onMyEvent->getCount());
			$this->assertTrue($this->component->enableBehavior($behaviorName));
			$this->assertTrue($this->component->enableBehavior($className));
			$this->assertEquals(4, $this->component->onMyEvent->getCount());
		}
	}

	// -----------------------------------------------------------------------
	// asa() and getBehaviors() — Prado3 dot-notation class name resolution
	// -----------------------------------------------------------------------

	/**
	 * asa() with a Prado3 System dot-notation framework class name.
	 *
	 * FooBehavior extends TBehavior (\Prado\Util\TBehavior).
	 * Calling asa('System.Util.TBehavior') must resolve to Prado\Util\TBehavior,
	 * then return the attached FooBehavior since it is an instance of TBehavior.
	 */
	public function testAsA_withPrado3SystemDotNotation(): void
	{
		$foo = new FooBehavior();
		$this->component->attachBehavior('fooBehaviorInstance', $foo);

		// System.Util.TBehavior → Prado\Util\TBehavior; FooBehavior IS-A TBehavior
		$result = $this->component->asa('System.Util.TBehavior');
		$this->assertSame($foo, $result);

		$this->component->detachBehavior('fooBehaviorInstance');
	}

	/**
	 * asa() with a Prado3 Prado dot-notation framework class name.
	 */
	public function testAsA_withPrado3PradoDotNotation(): void
	{
		$foo = new FooBehavior();
		$this->component->attachBehavior('fooBehaviorInstance2', $foo);

		// Prado.Util.TBehavior → Prado\Util\TBehavior
		$result = $this->component->asa('Prado.Util.TBehavior');
		$this->assertSame($foo, $result);

		$this->component->detachBehavior('fooBehaviorInstance2');
	}

	/**
	 * asa() with a Prado3 name returns null when no attached behavior matches.
	 */
	public function testAsA_withPrado3DotNotation_returnsNullWhenNoMatch(): void
	{
		// Attach a FooFooBehavior but look up by TClassBehavior (unrelated) Prado3 name
		$foo = new FooBehavior();
		$this->component->attachBehavior('fooForNullTest', $foo);

		// Prado.Util.TClassBehavior is a class behavior interface, FooBehavior is NOT one
		$result = $this->component->asa('Prado.Util.TClassBehavior');
		$this->assertNull($result);

		$this->component->detachBehavior('fooForNullTest');
	}

	/**
	 * getBehaviors() filtered by a Prado3 System dot-notation class name.
	 *
	 * FooBehavior extends TBehavior; FooFooBehavior extends FooBehavior.
	 * Both should be returned when filtering by 'System.Util.TBehavior'.
	 */
	public function testGetBehaviors_withPrado3SystemDotNotation(): void
	{
		$foo = new FooBehavior();
		$foofoo = new FooFooBehavior();
		$this->component->attachBehavior('fooB', $foo);
		$this->component->attachBehavior('foofooB', $foofoo);

		// System.Util.TBehavior → Prado\Util\TBehavior; both behaviors match
		$result = $this->component->getBehaviors('System.Util.TBehavior');
		$this->assertContains($foo, $result);
		$this->assertContains($foofoo, $result);
		$this->assertCount(2, $result);

		$this->component->detachBehavior('fooB');
		$this->component->detachBehavior('foofooB');
	}

	/**
	 * getBehaviors() filtered by a Prado3 Prado dot-notation class name.
	 */
	public function testGetBehaviors_withPrado3PradoDotNotation(): void
	{
		$foo = new FooBehavior();
		$this->component->attachBehavior('fooBehaviorPrado3', $foo);

		// Prado.Util.TBehavior → Prado\Util\TBehavior
		$result = $this->component->getBehaviors('Prado.Util.TBehavior');
		$this->assertContains($foo, $result);
		$this->assertCount(1, $result);

		$this->component->detachBehavior('fooBehaviorPrado3');
	}

	// -----------------------------------------------------------------------
	// asa() and getBehaviors() — usingClass() false / null edge cases
	// -----------------------------------------------------------------------

	/**
	 * asa() with a directory namespace (usingClass returns false) must return
	 * null — the !is_string() guard prevents searching the behavior list.
	 */
	public function testAsA_withDirectoryNamespace_returnsNull(): void
	{
		$foo = new FooBehavior();
		$this->component->attachBehavior('fooDir', $foo);

		$result = $this->component->asa('Prado\\Util\\*');
		$this->assertNull($result);

		$this->component->detachBehavior('fooDir');
	}

	/**
	 * asa() with an unknown class name (usingClass returns null) must return
	 * null — the !is_string() guard prevents searching the behavior list.
	 */
	public function testAsA_withUnknownClass_returnsNull(): void
	{
		$foo = new FooBehavior();
		$this->component->attachBehavior('fooUnknown', $foo);

		$result = $this->component->asa('TFakeBehaviorClassXYZ99999');
		$this->assertNull($result);

		$this->component->detachBehavior('fooUnknown');
	}

	/**
	 * getBehaviors() with a directory namespace (usingClass returns false)
	 * must return an empty array.
	 */
	public function testGetBehaviors_withDirectoryNamespace_returnsEmptyArray(): void
	{
		$foo = new FooBehavior();
		$this->component->attachBehavior('fooDirB', $foo);

		$result = $this->component->getBehaviors('Prado\\Util\\*');
		$this->assertSame([], $result);

		$this->component->detachBehavior('fooDirB');
	}

	/**
	 * getBehaviors() with an unknown class name (usingClass returns null)
	 * must return an empty array.
	 */
	public function testGetBehaviors_withUnknownClass_returnsEmptyArray(): void
	{
		$foo = new FooBehavior();
		$this->component->attachBehavior('fooUnknownB', $foo);

		$result = $this->component->getBehaviors('TFakeBehaviorClassXYZ99999');
		$this->assertSame([], $result);

		$this->component->detachBehavior('fooUnknownB');
	}

	/**
	 * A behavior implementing IOwnerVisibleMethods restricts which of its methods
	 * the owner may call.  Declared methods are reachable; undeclared ones are not.
	 */
	public function testOwnerVisibleMethods_restrictsInstanceBehavior(): void
	{
		$this->component->attachBehavior('ownerVisible', new OwnerVisibleMethodsBehavior());

		$this->assertTrue($this->component->hasMethod('visibleMethod'));
		$this->assertFalse($this->component->hasMethod('hiddenMethod'));
		$this->assertEquals('visible', $this->component->visibleMethod());

		try {
			$this->component->hiddenMethod();
			$this->fail('TUnknownMethodException not raised for an owner-hidden behavior method');
		} catch (TUnknownMethodException $e) {
		}

		$this->component->detachBehavior('ownerVisible');
	}

	/**
	 * IOwnerVisibleMethods also restricts class behaviors, and accepts the single
	 * string shorthand for the visible method name.
	 */
	public function testOwnerVisibleMethods_restrictsClassBehavior(): void
	{
		$this->component->attachBehavior('ownerVisibleClass', new OwnerVisibleMethodsClassBehavior());

		$this->assertTrue($this->component->hasMethod('visibleClassMethod'));
		$this->assertFalse($this->component->hasMethod('hiddenClassMethod'));
		$this->assertEquals('visibleClass', $this->component->visibleClassMethod());

		try {
			$this->component->hiddenClassMethod();
			$this->fail('TUnknownMethodException not raised for an owner-hidden class behavior method');
		} catch (TUnknownMethodException $e) {
		}

		$this->component->detachBehavior('ownerVisibleClass');
	}

	/**
	 * The behavior-method resolution caches ($_cm, $_bv) hold live behavior object
	 * references and spl_object_id keys, so they must be excluded from serialization.
	 * After a populate-then-round-trip the caches reset and the owner restriction
	 * still holds against the reattached behavior, not a stale serialized copy.
	 */
	public function testOwnerVisibleMethods_cachesExcludedFromSerialization(): void
	{
		$this->component->attachBehavior('ownerVisibleSerial', new OwnerVisibleMethodsBehavior());

		// Populate both caches: $_cm (method resolution) and $_bv (owner-visible set).
		$this->assertEquals('visible', $this->component->visibleMethod());
		$this->assertFalse($this->component->hasMethod('hiddenMethod'));
		$this->assertNotEmpty(PradoUnit::getProp($this->component, '_cm'));
		$this->assertNotEmpty(PradoUnit::getProp($this->component, '_bv'));

		$restored = unserialize(serialize($this->component));

		// The caches are rebuilt lazily, never carried across serialization.
		$this->assertSame([], PradoUnit::getProp($restored, '_cm'));
		$this->assertSame([], PradoUnit::getProp($restored, '_bv'));

		// The restriction still applies through the reattached behavior.
		$this->assertTrue($restored->hasMethod('visibleMethod'));
		$this->assertFalse($restored->hasMethod('hiddenMethod'));
		$this->assertEquals('visible', $restored->visibleMethod());

		try {
			$restored->hiddenMethod();
			$this->fail('TUnknownMethodException not raised for an owner-hidden behavior method after unserialize');
		} catch (TUnknownMethodException $e) {
		}

		$this->component->detachBehavior('ownerVisibleSerial');
	}

	/**
	 * The default TBaseBehavior returns null from getOwnerVisibleMethods, placing
	 * no restriction so every public behavior method remains visible to the owner.
	 */
	public function testOwnerVisibleMethods_nullPlacesNoRestriction(): void
	{
		$foo = new FooBehavior();
		$this->assertNull($foo->getOwnerVisibleMethods());

		$this->component->attachBehavior('fooNoRestriction', $foo);
		$this->assertTrue($this->component->hasMethod('faaEverMore'));
		$this->assertTrue($this->component->faaEverMore(true, true));

		$this->component->detachBehavior('fooNoRestriction');
	}

	/**
	 * An empty array from getOwnerVisibleMethods hides every behavior method from the
	 * owner.  This is the 5.0 opt-in default expressed explicitly.
	 */
	public function testOwnerVisibleMethods_emptyArrayHidesAllMethods(): void
	{
		$this->component->attachBehavior('hiddenAll', new OwnerHiddenAllBehavior());

		$this->assertFalse($this->component->hasMethod('visibleMethod'));

		try {
			$this->component->visibleMethod();
			$this->fail('TUnknownMethodException not raised when all behavior methods are hidden');
		} catch (TUnknownMethodException $e) {
		}

		$this->component->detachBehavior('hiddenAll');
	}

	/**
	 * dy dynamic events are the behavior-to-owner protocol and bypass owner-method
	 * visibility.  A behavior hiding all methods still receives its dy events.
	 */
	public function testOwnerVisibleMethods_dyEventBypassesRestriction(): void
	{
		$behavior = new OwnerHiddenAllBehavior();
		$this->component->attachBehavior('hiddenAllDy', $behavior);

		// The normal method is hidden ...
		$this->assertFalse($this->component->hasMethod('visibleMethod'));
		// ... but the dy event still reaches the behavior.
		$this->assertEquals('bbb', $this->component->dyTextFilter('aaa'));
		$this->assertTrue($behavior->isDyCalled());

		$this->component->detachBehavior('hiddenAllDy');
	}

	/**
	 * Enabled state is excluded from the $_cm cache and re-checked on each dispatch.
	 * Disabling an attached behavior hides its visible method without flushing the
	 * cache; re-enabling restores it.  This guards the cache design where only the
	 * structural resolution is memoized.
	 */
	public function testOwnerVisibleMethods_enabledCheckedLiveNotCached(): void
	{
		$this->component->attachBehavior('ownerVisibleLive', new OwnerVisibleMethodsBehavior());

		// Populate the $_cm cache for visibleMethod.
		$this->assertEquals('visible', $this->component->visibleMethod());
		$this->assertNotEmpty(PradoUnit::getProp($this->component, '_cm'));

		$this->component->disableBehavior('ownerVisibleLive');

		// The cache still holds the candidate (disable does not flush) ...
		$this->assertNotEmpty(PradoUnit::getProp($this->component, '_cm'));
		// ... but the live getEnabled() check hides the method.
		$this->assertFalse($this->component->hasMethod('visibleMethod'));
		try {
			$this->component->visibleMethod();
			$this->fail('TUnknownMethodException not raised for a disabled behavior method');
		} catch (TUnknownMethodException $e) {
		}

		$this->component->enableBehavior('ownerVisibleLive');
		$this->assertTrue($this->component->hasMethod('visibleMethod'));
		$this->assertEquals('visible', $this->component->visibleMethod());

		$this->component->detachBehavior('ownerVisibleLive');
	}

	/**
	 * Detaching a behavior flushes $_cm so a previously resolved owner method no
	 * longer resolves.
	 */
	public function testOwnerVisibleMethods_cacheFlushedOnDetach(): void
	{
		$this->component->attachBehavior('ownerVisibleFlush', new OwnerVisibleMethodsBehavior());

		$this->assertEquals('visible', $this->component->visibleMethod());
		$this->assertNotEmpty(PradoUnit::getProp($this->component, '_cm'));

		$this->component->detachBehavior('ownerVisibleFlush');

		$this->assertSame([], PradoUnit::getProp($this->component, '_cm'));
		$this->assertFalse($this->component->hasMethod('visibleMethod'));
	}

	/**
	 * Declared visible method names match case-insensitively, the names being
	 * normalized to a lowercased lookup set.
	 */
	public function testOwnerVisibleMethods_matchingIsCaseInsensitive(): void
	{
		$this->component->attachBehavior('ownerVisibleCase', new OwnerVisibleMethodsBehavior());

		$this->assertTrue($this->component->hasMethod('VISIBLEMETHOD'));
		$this->assertEquals('visible', $this->component->VisibleMethod());

		$this->component->detachBehavior('ownerVisibleCase');
	}

	/**
	 * A subclass composes its visible methods with the parent result via parent::,
	 * so both the inherited and the added methods are visible to the owner.
	 */
	public function testOwnerVisibleMethods_overrideComposesWithParent(): void
	{
		$this->component->attachBehavior('ownerVisibleComposed', new OwnerVisibleComposedBehavior());

		$this->assertTrue($this->component->hasMethod('visibleMethod'));
		$this->assertTrue($this->component->hasMethod('anotherVisibleMethod'));
		$this->assertFalse($this->component->hasMethod('hiddenMethod'));
		$this->assertEquals('visible', $this->component->visibleMethod());
		$this->assertEquals('another', $this->component->anotherVisibleMethod());

		$this->component->detachBehavior('ownerVisibleComposed');
	}
}
