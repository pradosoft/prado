<?php

namespace Prado\Test\Unit;

use Prado\Exceptions\TUnknownMethodException;
use Prado\Util\TClassBehavior;

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
		$this->component->attachClassBehavior('FooFooBehaviorAsClass', FooFooBehavior::class);

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

	/**
	 * IOwnerVisibleMethods restricts the __callStatic class-behavior path.  A class
	 * behavior attached as an instance that hides its methods makes its static method
	 * unreachable through the owner class, while an unrestricted one resolves normally.
	 */
	public function testCallStatic_classBehaviorOwnerVisibility()
	{
		$behaviorName = 'aHiddenStaticMethodBehavior';
		NewComponent::attachClassBehavior($behaviorName, new NewComponentStaticHiddenClassBehavior());
		$this->tearDownScripts[$behaviorName] = function () use ($behaviorName) { NewComponent::detachClassBehavior($behaviorName); };

		try {
			NewComponent::aStaticMethod(3);
			self::fail("failed to raise TUnknownMethodException for an owner-hidden static class-behavior method.");
		} catch (TUnknownMethodException $e) {
		}

		NewComponent::detachClassBehavior($behaviorName);
		unset($this->tearDownScripts[$behaviorName]);

		// An unrestricted class behavior still resolves its static method.
		NewComponent::attachClassBehavior($behaviorName, new NewComponentStaticClassBehavior());
		$this->tearDownScripts[$behaviorName] = function () use ($behaviorName) { NewComponent::detachClassBehavior($behaviorName); };

		self::assertEquals(9, NewComponent::aStaticMethod(3));

		NewComponent::detachClassBehavior($behaviorName);
		unset($this->tearDownScripts[$behaviorName]);
	}

	/**
	 * A per-instance class behavior registered as a configuration array resolves its
	 * static method through the configured 'class' without owner-visibility restriction.
	 */
	public function testCallStatic_instanceClassBehaviorAsArray()
	{
		$behaviorName = 'aStaticConfigBehavior';
		NewComponent::attachClassBehavior($behaviorName, ['class' => NewComponentStaticBehavior::class]);
		$this->tearDownScripts[$behaviorName] = function () use ($behaviorName) { NewComponent::detachClassBehavior($behaviorName); };

		self::assertEquals(6, NewComponent::aStaticMethod(3));

		NewComponent::detachClassBehavior($behaviorName);
		unset($this->tearDownScripts[$behaviorName]);
	}

	/**
	 * A class behavior naming its static method in getOwnerVisibleMethods() exposes it
	 * to the owner class; the name match is case-insensitive.
	 */
	public function testCallStatic_classBehaviorOwnerVisibleStaticMethod()
	{
		$behaviorName = 'aVisibleStaticMethodBehavior';
		NewComponent::attachClassBehavior($behaviorName, new TComponentCallTestVisibleStaticClassBehavior());
		$this->tearDownScripts[$behaviorName] = function () use ($behaviorName) { NewComponent::detachClassBehavior($behaviorName); };

		self::assertEquals(7, NewComponent::aStaticMethod(3));

		NewComponent::detachClassBehavior($behaviorName);
		unset($this->tearDownScripts[$behaviorName]);
	}

	/**
	 * A class behavior naming its static method as the single string form of
	 * getOwnerVisibleMethods() exposes that method to the owner class.
	 */
	public function testCallStatic_classBehaviorOwnerVisibleMethodAsString()
	{
		$behaviorName = 'aStringVisibleStaticMethodBehavior';
		$behavior = new TComponentCallTestListedStaticClassBehavior();
		$behavior->visibleMethods = 'aStaticMethod';
		NewComponent::attachClassBehavior($behaviorName, $behavior);
		$this->tearDownScripts[$behaviorName] = function () use ($behaviorName) { NewComponent::detachClassBehavior($behaviorName); };

		self::assertTrue(PradoUnit::invoke(NewComponent::class, 'isStaticBehaviorOwnerVisibleMethod', $behavior, 'ASTATICMETHOD'));
		self::assertEquals(13, NewComponent::aStaticMethod(3));

		NewComponent::detachClassBehavior($behaviorName);
		unset($this->tearDownScripts[$behaviorName]);
	}

	/**
	 * A class behavior whose getOwnerVisibleMethods() does not name its static method
	 * hides that method from the owner class.
	 */
	public function testCallStatic_classBehaviorStaticMethodNotListed()
	{
		$behaviorName = 'anUnlistedStaticMethodBehavior';
		$behavior = new TComponentCallTestListedStaticClassBehavior();
		$behavior->visibleMethods = ['otherMethod'];
		NewComponent::attachClassBehavior($behaviorName, $behavior);
		$this->tearDownScripts[$behaviorName] = function () use ($behaviorName) { NewComponent::detachClassBehavior($behaviorName); };

		self::assertFalse(PradoUnit::invoke(NewComponent::class, 'isStaticBehaviorOwnerVisibleMethod', $behavior, 'aStaticMethod'));
		try {
			NewComponent::aStaticMethod(3);
			self::fail('failed to raise TUnknownMethodException for a static class-behavior method that is not owner visible.');
		} catch (TUnknownMethodException $e) {
			self::assertEquals('component_method_undefined', $e->getErrorCode());
		}

		NewComponent::detachClassBehavior($behaviorName);
		unset($this->tearDownScripts[$behaviorName]);
	}

	/**
	 * A protected static method called from outside its class is not run; the call
	 * returns null without an exception, as an inaccessible instance method does.
	 */
	public function testCallStatic_inaccessibleStaticMethodReturnsNull()
	{
		self::assertTrue(method_exists(NewComponent::class, 'tokenizePropertyPath'));
		self::assertNull(NewComponent::tokenizePropertyPath('a.b'));
	}
}

/**
 * Class behavior whose static method is listed in its owner-visible methods.
 */
class TComponentCallTestVisibleStaticClassBehavior extends TClassBehavior
{
	public static function aStaticMethod(int $value)
	{
		return $value + 4;
	}

	public function getOwnerVisibleMethods(): null|string|array
	{
		return ['otherMethod', 'ASTATICMETHOD'];
	}
}

/**
 * Class behavior whose owner-visible methods are set by the test.
 */
class TComponentCallTestListedStaticClassBehavior extends TClassBehavior
{
	/** @var null|array|string the value returned by {@see getOwnerVisibleMethods}. */
	public null|string|array $visibleMethods = null;

	public static function aStaticMethod(int $value)
	{
		return $value + 10;
	}

	public function getOwnerVisibleMethods(): null|string|array
	{
		return $this->visibleMethods;
	}
}
