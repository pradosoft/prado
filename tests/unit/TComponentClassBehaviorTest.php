<?php

require_once __DIR__ . '/TComponentTestBase.php';

use Prado\Exceptions\TInvalidOperationException;
use Prado\TComponent;
use Prado\Util\IBaseBehavior;

/**
 * Tests for TComponent class-wide (static) behavior management:
 * attachClassBehavior() and detachClassBehavior().
 */
class TComponentClassBehaviorTest extends TComponentTestBase
{
	//Test Class behaviors
	public function testAttachClassBehavior()
	{
		$fooClassBehaviorName = 'FooClassBehaviorName';
		$this->assertEquals([], $this->component->getBehaviors());

	// ensure that the class is listening
		$this->assertEquals(1, $this->component->getEventHandlers('fxAttachClassBehavior')->getCount());

		//Test that the component is not a FooClassBehavior
		$this->assertNull($this->component->asa($fooClassBehaviorName), "Component is already a FooClassBehavior and should not have this behavior");


		//Add the FooClassBehavior
		// Add class behavior as IClassBehavior string
		$b1 = $this->component->attachClassBehavior($fooClassBehaviorName, 'FooClassBehavior');
		$this->tearDownScripts[] = function() use ($fooClassBehaviorName) {$this->component->detachClassBehavior($fooClassBehaviorName);};
		$this->assertInstanceof('FooClassBehavior', $b1);
		$this->assertNotNull($this->component->asa($fooClassBehaviorName), "Component is does not have the FooClassBehavior and should have this behavior");
		$this->assertEquals(FooClassBehavior::NULL_CONFIG, $this->component->asa($fooClassBehaviorName)->_config, "Component did not initialize the behavior when it should");

		// Add class behavior as instanced IClassBehavior behavior
		$b2 = $this->component->attachClassBehavior('FooClassBehavior2', $ob2 = new FooClassBehavior());
		$this->tearDownScripts[] = function() {$this->component->detachClassBehavior('FooClassBehavior2');};
		$this->assertInstanceof('FooClassBehavior', $b2);
		$this->assertEquals($ob2, $b2);
		$this->assertNotNull($this->component->asa('FooClassBehavior2'), "Component is does not have the FooClassBehavior2 and should have this behavior");
		$this->assertEquals('default', $this->component->asa('FooClassBehavior2')->PropertyA, "Component is does not have the FooClassBehavior2 and should have this behavior");
		$this->assertNull($this->component->asa('FooClassBehavior2')->_config, "Component initialized existing behavior when it should not have");

		// add class behavior as array of properties
		$b3 = $this->component->attachClassBehavior('FooClassBehavior3', ['class' => 'FooClassBehavior', 'propertyA'=>'value', IBaseBehavior::CONFIG_KEY => $foo3classdata = 'class-config-data']);
		$this->assertInstanceof('FooClassBehavior', $b3);
		$this->tearDownScripts[] = function() {$this->component->detachClassBehavior('FooClassBehavior3');};
		$this->assertNotNull($this->component->asa('FooClassBehavior3'), "Component is does not have the FooClassBehavior3 and should have this behavior");
		$this->assertEquals('value', $this->component->asa('FooClassBehavior3')->PropertyA, "Component is does not have the FooClassBehavior2 and should have this behavior");
		$this->assertEquals($foo3classdata, $this->component->asa('FooClassBehavior3')->_config, "Component did not initialize the behavior when it should");

		// add class behavior as IBehavior string
		$b4 = $this->component->attachClassBehavior('FooRegularBehavior', 'BehaviorTestBehavior');
		$this->tearDownScripts[] = function() {$this->component->detachClassBehavior('FooRegularBehavior');};
		$this->assertEquals([$this->component->FooRegularBehavior], $b4);
		$this->assertNotNull($this->component->asa('FooRegularBehavior'));
		$this->assertEquals('faa', $this->component->asa('FooRegularBehavior')->Excitement);
		$this->assertEquals(BehaviorTestBehavior::NULL_CONFIG, $this->component->asa('FooRegularBehavior')->_config, "Component did not initialize the behavior when it should");

		// add class behavior as IBehavior array of properties
		$b5 = $this->component->attachClassBehavior('FooRegularBehavior2', ['class' => 'BehaviorTestBehavior', 'Excitement'=>'behavior-value', IBaseBehavior::CONFIG_KEY => $foo2data = 'config-data']);
		$this->assertEquals([$this->component->FooRegularBehavior2], $b5);
		$this->tearDownScripts[] = function() {$this->component->detachClassBehavior('FooRegularBehavior2');};
		$this->assertNotNull($this->component->asa('FooRegularBehavior2'));
		$this->assertEquals('behavior-value', $this->component->asa('FooRegularBehavior2')->Excitement);
		$this->assertEquals($foo2data, $this->component->asa('FooRegularBehavior2')->_config, "Component did not initialize the behavior when it should");

		// Add class behavior as instance of IBehavior to be cloned
		$b6 = $this->component->attachClassBehavior('FooRegularBehavior3', $ob6 = new BehaviorTestBehavior());
		$this->tearDownScripts[] = function() {$this->component->detachClassBehavior('FooRegularBehavior3');};
		$this->assertEquals($this->component, $b6[0]->getOwner());
		$this->assertNotNull($this->component->asa('FooRegularBehavior3'));
		$this->assertEquals('faa', $this->component->asa('FooRegularBehavior3')->Excitement);
		$this->assertNull($this->component->asa('FooRegularBehavior3')->_config, "Component did not initialize the behavior when it should");

		// Add anonymous class behavior, numeric
		$this->assertNull($this->component->asa(0));
		$b7 = $this->component->attachClassBehavior('11', $ob7 = new BehaviorTestBehavior());
		$b7name = $this->anonymousClassIndex++;
		$this->tearDownScripts[] = function() use ($b7name) { $this->component->detachClassBehavior($b7name);};
		$ob7->Excitement = 'anon_behavior';
		$this->assertEquals($this->component, $b7[0]->getOwner());
		$this->assertNotNull($this->component->asa(0));
		$this->assertEquals('faa', $this->component->asa(0)->Excitement, "The original IBehavior attached to a class should have been cloned to attach and not be the original behavior in this instance.");
		$this->assertNull($this->component->asa(0)->_config, "Component did not initialize the behavior when it should");

		// Add anonymous class behavior, null
		$b8 = $this->component->attachClassBehavior(null, $ob8 = new BehaviorTestBehavior());
		$b8name = $this->anonymousClassIndex++;
		$this->tearDownScripts[] = function() use ($b8name) {$this->component->detachClassBehavior($b8name);};
		$ob7->Excitement = 'anon_null_behavior';
		$this->assertEquals($this->component, $b8[0]->getOwner());
		$this->assertNotNull($this->component->asa(1));
		$this->assertEquals('faa', $this->component->asa(1)->Excitement);
		$this->assertNull($this->component->asa(1)->_config, "Component did not initialize the behavior when it should");


		// test if the function modifies new instances of the object
		$anothercomponent = new NewComponent();

		//The new component should be a FooClassBehavior
		$this->assertNotNull($anothercomponent->asa(0), "anothercomponent does not have the numeric named anonymous behavior");
		$this->assertNotNull($anothercomponent->asa(1), "anothercomponent does not have the null named anonymous behavior");
		$this->assertNotNull($anothercomponent->asa($fooClassBehaviorName), "anothercomponent does not have the FooClassBehavior and should");
		$this->assertNotNull($anothercomponent->asa('FooClassBehavior2'), "anothercomponent does not have the FooClassBehavior2 and should");
		$this->assertNotNull($anothercomponent->asa('FooClassBehavior3'), "anothercomponent does not have the FooClassBehavior3 and should");
		$this->assertNotNull($anothercomponent->asa('FooRegularBehavior'), "anothercomponent does not have the FooRegularBehavior and should");
		$this->assertNotNull($anothercomponent->asa('FooRegularBehavior2'), "anothercomponent does not have the FooRegularBehavior2 and should");
		$this->assertNotNull($anothercomponent->asa('FooRegularBehavior3'), "anothercomponent does not have the FooRegularBehavior3 and should");
		$anothercomponent->asa('FooRegularBehavior')->Excitement = 'foo-regular-behavior-test-value';

		// Class behaviors have both classes as owners, behaviors have their owner
		$this->assertEquals([$this->component, $anothercomponent], $this->component->asa('FooClassBehavior')->getOwners());
		$this->assertEquals($this->component, $this->component->asa('FooRegularBehavior')->getOwner());
		$this->assertEquals($anothercomponent, $anothercomponent->asa('FooRegularBehavior')->getOwner());
		$this->assertNotEquals($this->component->asa('FooRegularBehavior'), $anothercomponent->asa('FooRegularBehavior'));

		// Clone adds owner to class behaviors
		$thirdcomponent = clone $anothercomponent;
		$this->assertEquals([$this->component, $anothercomponent, $thirdcomponent], $this->component->asa($fooClassBehaviorName)->getOwners());

		// test when overwriting an existing class behavior, it should throw an TInvalidOperationException
		try {
			$this->component->attachClassBehavior($fooClassBehaviorName, new BarClassBehavior);
			$this->fail('TInvalidOperationException not raised when overwriting an existing behavior');
		} catch (TInvalidOperationException $e) {
		}

		// test when using non-class regular behavior, TComponent clones IBehaviors in class context.


		// test TInvalidOperationException when placing a behavior on TComponent
		try {
			$this->component->attachClassBehavior('FooBarBehavior', 'FooBarBehavior', TComponent::class);
			$this->fail('TInvalidOperationException not raised when trying to place a behavior on the root object TComponent');
		} catch (TInvalidOperationException $e) {
		}

		// test if the function does not modify any existing objects that are not listening
		//	The FooClassBehavior is already a part of the class behaviors thus the new instance gets the behavior.
		$nolistencomponent = new NewComponentNoListen();

		// test if the function modifies all existing objects that are listening
		//	Adding a behavior to the first object, the second instance should automatically get the class behavior.
		//		This is because the second object is listening to the global events of class behaviors
		$this->component->attachClassBehavior($className = 'BarClassBehaviorName', new BarClassBehavior);
		$this->tearDownScripts[] = function() use ($className) {$this->component->detachClassBehavior($className);};
		$this->assertNotNull($anothercomponent->asa($className), "anothercomponent is does not have the BarClassBehavior");

		// The no listen object should not have the BarClassBehavior because it was added as a class behavior after the object was instanced
		$this->assertNull($nolistencomponent->asa($className), "nolistencomponent has the BarClassBehavior and should not");

		//	But the no listen object should have the FooClassBehavior because the class behavior was installed before the object was instanced
		$this->assertNotNull($nolistencomponent->asa($fooClassBehaviorName), "nolistencomponent is does not have the FooClassBehavior");

		//Clear out what was done during this test
		$anothercomponent->unlisten();
		$thirdcomponent->unlisten();
		$this->component->detachClassBehavior($className);
		array_pop($this->tearDownScripts);

		// Test attaching of single object behaviors as class-wide behaviors
		$this->component->attachClassBehavior('BarBehaviorObject', 'BarBehavior');
		$this->assertTrue($this->component->asa('BarBehaviorObject') instanceof BarBehavior);
		$this->assertEquals($this->component->BarBehaviorObject->Owner, $this->component);
		$this->component->detachClassBehavior('BarBehaviorObject');
		$this->assertNull($this->component->asa('BarBehaviorObject'));
	}


	public function testAttachClassBehavior_AnonymousOverExistingAnon()
	{
		$component = new NewComponent();
		$component->attachBehavior(null, $behavior = new BehaviorTestBehavior());
		$component->attachClassBehavior(null, $classBehaviors = new FooClassBehavior());
		$indexName = $this->anonymousClassIndex++;
		$this->tearDownScripts[] = function() use ($indexName) {NewComponent::detachClassBehavior($indexName);};
		$this->assertEquals([$classBehaviors], $this->component->getBehaviors());
		$this->assertEquals([$behavior, $classBehaviors], $component->getBehaviors());
		$this->assertEquals($behavior, $component->asa(0));
		$this->assertEquals($classBehaviors, $component->asa(1));
		$this->assertEquals($classBehaviors, $this->component->asa(0));
	}


	public function testDetachClassBehavior()
	{
		$fooClassBehaviorName = 'FooClassBehaviorName';
		$this->assertEquals([], $this->component->getBehaviors());

		// ensure that the component is listening
		$this->assertEquals(1, $this->component->getEventHandlers('fxDetachClassBehavior')->getCount());

		$prenolistencomponent = new NewComponentNoListen();

		//Attach a class behavior
		$b = $this->component->attachClassBehavior($fooClassBehaviorName, $cb = new FooClassBehavior());
		$this->tearDownScripts[$fooClassBehaviorName] = function() use ($fooClassBehaviorName) {$this->component->detachClassBehavior($fooClassBehaviorName);};
		$this->assertEquals($cb, $b);
		$b = $this->component->attachClassBehavior('FooRegularBehavior', 'BehaviorTestBehavior');
		$this->tearDownScripts['FooRegularBehavior'] = function() {$this->component->detachClassBehavior('FooRegularBehavior');};
		$rb = $this->component->FooRegularBehavior;
		$this->assertEquals([$rb], $b);

		$this->assertEquals(1, $this->component->getEventHandlers('fxDetachClassBehavior')->getCount());

		//Create new components that listen and don't listen to global events
		$anothercomponent = new NewComponent();
		$postnolistencomponent = new NewComponentNoListen();
		$ancomb = $anothercomponent->FooRegularBehavior;

		//ensures that all the Components are properly initialized
		$this->assertNotNull($this->component->asa($fooClassBehaviorName), "Listening Component does not have the FooClassBehavior and should have this behavior");
		$this->assertNull($prenolistencomponent->asa($fooClassBehaviorName), "Component has the FooClassBehavior and should _not_ have this behavior");
		$this->assertNotNull($anothercomponent->asa($fooClassBehaviorName), "Component does not have the FooClassBehavior and should have this behavior");
		$this->assertNotNull($postnolistencomponent->asa($fooClassBehaviorName), "Component does not have the FooClassBehavior and should have this behavior");
		$this->assertNotNull($anothercomponent->asa('FooRegularBehavior'), "Component does not have the FooRegularBehavior and should have this behavior");
		$this->assertNotNull($postnolistencomponent->asa('FooRegularBehavior'), "Component does not have the FooRegularBehavior and should have this behavior");
		$this->assertEquals(2, $this->component->getEventHandlers('fxDetachClassBehavior')->getCount());

		unset($this->tearDownScripts[$fooClassBehaviorName]);
		$deb = $this->component->detachClassBehavior($fooClassBehaviorName);
		$this->assertEquals($cb, $deb);

		unset($this->tearDownScripts['FooRegularBehavior']);
		$derb = $this->component->detachClassBehavior('FooRegularBehavior');
		$this->assertEquals([$rb, $ancomb], $derb);

		$noReturnBehavior = $this->component->detachClassBehavior('NoBehaviorOfThisName');
		$this->assertNull($noReturnBehavior);

		$this->assertNull($this->component->asa($fooClassBehaviorName), "Component has the FooClassBehavior and should _not_ have this behavior");
		$this->assertNull($prenolistencomponent->asa($fooClassBehaviorName), "Component has the FooClassBehavior and should _not_ have this behavior");
		$this->assertNull($anothercomponent->asa($fooClassBehaviorName), "Component has the FooClassBehavior and should _not_ have this behavior");
		$this->assertNotNull($postnolistencomponent->asa($fooClassBehaviorName), "Component does not have the FooClassBehavior and should have this behavior");
		$this->assertNull($anothercomponent->asa('FooRegularBehavior'), "Component has the FooRegularBehavior and should _not_ have this behavior");
		$this->assertNotNull($postnolistencomponent->asa('FooRegularBehavior'), "Component does not have the FooRegularBehavior and should have this behavior");


		//tear down function variables
		$anothercomponent->unlisten();
	}
}
