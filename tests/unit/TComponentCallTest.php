<?php

require_once __DIR__ . '/TComponentTestBase.php';

use Prado\Exceptions\TUnknownMethodException;

/**
 * Tests for TComponent magic dispatch: __call() (behavior method forwarding)
 * and __callStatic() (singleton and class-behavior static methods).
 */
class TComponentCallTest extends TComponentTestBase
{
	public function testCall_ForBehaviorFunction()
	{
		$fooClassBehaviorName = 'FooClassBehaviorName';
		$fooBarBehaviorName = 'FooBarBehaviorName';
		$this->component->attachBehavior($fooBarBehaviorName, $behavior = new FooBarBehavior);
		$this->component->attachClassBehavior($fooClassBehaviorName, $classbehavior = new FooClassBehavior);

		$this->tearDownScripts[$fooClassBehaviorName] = function() use ($fooClassBehaviorName) {NewComponent::detachClassBehavior($fooClassBehaviorName);};

		// Test the Class Methods
		$this->assertEquals(12, $this->component->faaEverMore(3, 4));

		// Check that the called object is shifted in front of the array of a class behavior call
		$this->assertEquals($this->component, $this->component->getLastClassObject());


		//Test the FooBarBehavior
		$this->assertEquals(27, $this->component->moreFunction(3, 3));

		$this->assertTrue($this->component->disableBehavior($fooBarBehaviorName));
		try {
			$this->assertNull($this->component->moreFunction(3, 4));
			$this->fail('TUnknownMethodException not raised trying to execute a disabled behavior');
		} catch (TUnknownMethodException $e) {
		}
		$this->assertTrue($this->component->enableBehavior($fooBarBehaviorName));

		// Test the global event space, this should work and return false because no function implements these methods
		$this->assertNull($this->component->fxSomeUndefinedGlobalEvent());
		$this->assertNull($this->component->dySomeUndefinedIntraObjectEvent());

		$this->component->detachClassBehavior($fooClassBehaviorName);
		unset($this->tearDownScripts[$fooClassBehaviorName]);


		// test object instance behaviors implemented through class-wide behaviors
		$this->component->attachClassBehavior('FooFooBehaviorAsClass', 'FooFooBehavior');

		$component = new NewComponent;

		$this->assertEquals(5, $this->component->faafaaEverMore(3, 4));
		$this->assertEquals(10, $component->faafaaEverMore(6, 8));

		$this->component->detachClassBehavior('FooFooBehaviorAsClass');
		$component->unlisten();
		$component = null;

		try {
			$this->component->faafaaEverMore(3, 4);
			$this->fail('TUnknownMethodException not raised trying to execute a disabled behavior');
		} catch (TUnknownMethodException $e) {
		}



		// make a call to an unpatched fx and dy call so that it's passed through to the __dycall function
		$dynamicComponent = new DynamicCallComponent;

		$this->assertNull($dynamicComponent->fxUndefinedEvent());
		$this->assertNull($dynamicComponent->dyUndefinedEvent());

		//This tests the dynamic __dycall function
		$this->assertEquals(1024, $dynamicComponent->dyPowerFunction(2, 10));
		$this->assertEquals(5, $dynamicComponent->dyDivisionFunction(10, 2));

		$this->assertEquals(2048, $dynamicComponent->fxPowerFunction(2, 10));
		$this->assertEquals(10, $dynamicComponent->fxDivisionFunction(10, 2));

		$dynamicComponent->unlisten();
	}


	public function testCallStatic_singleton()
	{
		$app = \Prado\Prado::getApplication();
		try {
			\Prado\TApplication::aStaticMethod(3);
			self::fail("failed to raise TUnknownMethodException when calling an undefined static method.");
		} catch(TUnknownMethodException $e) {
		}

		$behaviorName = 'aStaticMethodBehavior';
		$app->attachBehavior($behaviorName, NewComponentStaticBehavior::class);

		self::assertEquals(6, \Prado\TApplication::aStaticMethod(3));

		// Disable Behavior has no singleton behavior static function
		$app->disableBehavior($behaviorName);
		try {
			\Prado\TApplication::aStaticMethod(3);
			self::fail("failed to raise TUnknownMethodException when calling an undefined static method.");
		} catch(TUnknownMethodException $e) {
		}
		$app->enableBehavior($behaviorName);
		self::assertEquals(8, \Prado\TApplication::aStaticMethod(4));

		// Disable Behaviors of application, has no singleton behavior static function
		$app->disableBehaviors();
		try {
			\Prado\TApplication::aStaticMethod(3);
			self::fail("failed to raise TUnknownMethodException when calling an undefined static method.");
		} catch(TUnknownMethodException $e) {
		}
		$app->enableBehaviors();
		self::assertEquals(10, \Prado\TApplication::aStaticMethod(5));

		$app->detachBehavior($behaviorName);

		try {
			\Prado\TApplication::aStaticMethod(3);
			self::fail("failed to raise TUnknownMethodException when calling an undefined static method.");
		} catch(TUnknownMethodException $e) {
		}
	}

	public function testCallStatic_classBehavior()
	{
		try {
			NewComponent::aStaticMethod(3);
			self::fail("failed to raise TUnknownMethodException when calling an undefined static method.");
		} catch(TUnknownMethodException $e) {
		}

		$behaviorName = 'aStaticMethodBehavior';

		// Class Behavior as String
		NewComponent::attachClassBehavior($behaviorName, NewComponentStaticClassBehavior::class);
		$this->tearDownScripts[$behaviorName] = function() use ($behaviorName) { NewComponent::detachClassBehavior($behaviorName);};

		self::assertEquals(9, NewComponent::aStaticMethod(3));
		NewComponent::detachClassBehavior($behaviorName);

		// Class Behavior as Array
		NewComponent::attachClassBehavior($behaviorName, ['class' => NewComponentStaticClassBehavior::class]);

		self::assertEquals(9, NewComponent::aStaticMethod(3));
		NewComponent::detachClassBehavior($behaviorName);

		// Class Behavior as instanced class
		NewComponent::attachClassBehavior($behaviorName, $behavior = new NewComponentStaticClassBehavior());

		self::assertEquals(9, NewComponent::aStaticMethod(3));
		$behavior->setEnabled(false);
		try {
			NewComponent::aStaticMethod(3);
			self::fail("failed to raise TUnknownMethodException when calling an undefined static method.");
		} catch(TUnknownMethodException $e) {
		}
		$behavior->setEnabled(true);
		self::assertEquals(16, NewComponent::aStaticMethod(4));

		NewComponent::detachClassBehavior($behaviorName);


		unset($this->tearDownScripts[$behaviorName]);
	}
}
