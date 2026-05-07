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
}
