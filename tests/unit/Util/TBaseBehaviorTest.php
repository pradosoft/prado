<?php

use Prado\Exceptions\TInvalidOperationException;
use Prado\Util\TBaseBehavior;
use Prado\Util\TBehavior;
use Prado\Util\TClassBehavior;
use Prado\TComponent;

class StrictEventsBehavior extends TBehavior
{
	public function events()
	{
		return ['onMyEvent' => 'myHandler'];
	}
	public function myHandler($sender, $param)
	{
	}
}

class NonStrictEventsBehavior extends StrictEventsBehavior
{
	public function getStrictEvents(): bool
	{
		return false;
	}
}

class TestAssertOwnerBehavior extends TBehavior
{
	public function callAssertOwner(string $property, ?string $exceptionKey = null): void
	{
		$this->assertOwner($property, $exceptionKey);
	}

	public function callAssertWithoutOwner(string $property, ?string $exceptionKey = null): void
	{
		$this->assertWithoutOwner($property, $exceptionKey);
	}

	public function getOwnerExceptionKeyPublic(): string
	{
		return $this->getOwnerExceptionKey();
	}

	public function getWithoutOwnerExceptionKeyPublic(): string
	{
		return $this->getWithoutOwnerExceptionKey();
	}
}

class TestAssertOwnerClassBehavior extends TClassBehavior
{
	public function callAssertOwner(string $property, ?string $exceptionKey = null): void
	{
		$this->assertOwner($property, $exceptionKey);
	}

	public function callAssertWithoutOwner(string $property, ?string $exceptionKey = null): void
	{
		$this->assertWithoutOwner($property, $exceptionKey);
	}

	public function getOwnerExceptionKeyPublic(): string
	{
		return $this->getOwnerExceptionKey();
	}

	public function getWithoutOwnerExceptionKeyPublic(): string
	{
		return $this->getWithoutOwnerExceptionKey();
	}
}

class TBaseBehaviorTest extends PHPUnit\Framework\TestCase
{
	// === mergeHandlers ===

	public function testMergeHandlers_emptyArgs()
	{
		$this->assertEquals([], TBaseBehavior::mergeHandlers());
	}

	public function testMergeHandlers_emptyArray()
	{
		$this->assertEquals([], TBaseBehavior::mergeHandlers([]));
	}

	public function testMergeHandlers_stringHandler()
	{
		$result = TBaseBehavior::mergeHandlers(['onEvent' => 'handlerName']);
		$this->assertEquals(['onEvent' => ['handlerName']], $result);
	}

	public function testMergeHandlers_callableHandler()
	{
		$closure = function () {};
		$result = TBaseBehavior::mergeHandlers(['onEvent' => $closure]);
		$this->assertEquals(['onEvent' => [$closure]], $result);
	}

	public function testMergeHandlers_arrayHandler()
	{
		$result = TBaseBehavior::mergeHandlers(['onEvent' => ['h1', 'h2']]);
		$this->assertEquals(['onEvent' => ['h1', 'h2']], $result);
	}

	public function testMergeHandlers_mergesMultipleEvents()
	{
		$closure = function ($sender, $param) {};
		$result = TBaseBehavior::mergeHandlers(
			['onEvent2' => $closure],
			['onEvent1' => 'behaviorHandler', 'onEvent2' => [$this, __METHOD__], 'onEvent3' => ['behaviorHandler2', [$this, __METHOD__]]]
		);
		$this->assertEquals([
			'onEvent2' => [$closure, [$this, __METHOD__]],
			'onEvent1' => ['behaviorHandler'],
			'onEvent3' => ['behaviorHandler2', [$this, __METHOD__]],
		], $result);
	}

	// === StrictEvents ===

	public function testStrictEvents_throwsOnMissingEvent()
	{
		$component = new TComponent();
		$strict = new StrictEventsBehavior();
		$this->assertTrue($strict->getStrictEvents());
		try {
			$component->attachBehavior('strict', $strict);
			$this->fail("TInvalidOperationException not thrown when attaching strict behavior event handlers");
		} catch (TInvalidOperationException $e) {
		}
	}

	public function testNonStrictEvents_attachesSuccessfully()
	{
		$component = new TComponent();
		$nonStrict = new NonStrictEventsBehavior();
		$this->assertFalse($nonStrict->getStrictEvents());
		$component->attachBehavior('nonstrict', $nonStrict);
	}

	// === assertOwner (TBehavior) ===

	public function testAssertOwner_throwsWithoutOwner()
	{
		$b = new TestAssertOwnerBehavior();
		$this->expectException(TInvalidOperationException::class);
		$b->callAssertOwner('myProperty');
	}

	public function testAssertOwner_passesWithOwner()
	{
		$b = new TestAssertOwnerBehavior();
		$c = new TComponent();
		$b->attach($c);
		$b->callAssertOwner('myProperty');
		$this->assertTrue(true);
	}

	public function testAssertOwner_passesAfterDetachThenReattach()
	{
		$b = new TestAssertOwnerBehavior();
		$c = new TComponent();
		$b->attach($c);
		$b->callAssertOwner('prop');
		$b->detach($c);
		$this->expectException(TInvalidOperationException::class);
		$b->callAssertOwner('prop');
	}

	public function testAssertOwner_defaultKey()
	{
		$b = new TestAssertOwnerBehavior();
		$this->assertEquals('behavior_requires_owner', $b->getOwnerExceptionKeyPublic());
	}

	public function testAssertOwner_nullExceptionKeyUsesDefault()
	{
		$b = new TestAssertOwnerBehavior();
		try {
			$b->callAssertOwner('someProp', null);
			$this->fail('Expected TInvalidOperationException');
		} catch (TInvalidOperationException $e) {
			$this->assertEquals('behavior_requires_owner', $e->getErrorCode());
		}
	}

	public function testAssertOwner_explicitKeyOverridesDefault()
	{
		$b = new TestAssertOwnerBehavior();
		try {
			$b->callAssertOwner('someProp', 'custom_override_key');
			$this->fail('Expected TInvalidOperationException');
		} catch (TInvalidOperationException $e) {
			$this->assertEquals('custom_override_key', $e->getErrorCode());
		}
	}

	public function testAssertOwner_customOwnerKeyOverride()
	{
		$b = new class extends TestAssertOwnerBehavior {
			protected function getOwnerExceptionKey(): string
			{
				return 'custom_requires_owner';
			}
		};
		try {
			$b->callAssertOwner('someProp');
			$this->fail('Expected TInvalidOperationException');
		} catch (TInvalidOperationException $e) {
			$this->assertEquals('custom_requires_owner', $e->getErrorCode());
		}
	}

	public function testAssertOwner_includesPropertyAndShortClassName()
	{
		$b = new TestAssertOwnerBehavior();
		try {
			$b->callAssertOwner('testPropertyName');
			$this->fail('Expected TInvalidOperationException');
		} catch (TInvalidOperationException $e) {
			$msg = $e->getMessage();
			$this->assertStringContainsString('testPropertyName', $msg);
			$this->assertStringContainsString('TestAssertOwnerBehavior', $msg);
		}
	}

	// === assertOwner (TClassBehavior) ===

	public function testAssertOwner_classBehavior_throwsWithoutOwner()
	{
		$cb = new TestAssertOwnerClassBehavior();
		$this->expectException(TInvalidOperationException::class);
		$cb->callAssertOwner('myProp');
	}

	public function testAssertOwner_classBehavior_passesWithSingleOwner()
	{
		$cb = new TestAssertOwnerClassBehavior();
		$c = new TComponent();
		$cb->attach($c);
		$cb->callAssertOwner('myProp');
		$this->assertTrue(true);
	}

	public function testAssertOwner_classBehavior_passesWithMultipleOwners()
	{
		$cb = new TestAssertOwnerClassBehavior();
		$c1 = new TComponent();
		$c2 = new TComponent();
		$cb->attach($c1);
		$cb->attach($c2);
		$cb->callAssertOwner('myProp');
		$this->assertTrue(true);
	}

	public function testAssertOwner_classBehavior_defaultKey()
	{
		$cb = new TestAssertOwnerClassBehavior();
		$this->assertEquals('behavior_requires_owner', $cb->getOwnerExceptionKeyPublic());
	}

	public function testAssertOwner_classBehavior_explicitKeyOverridesDefault()
	{
		$cb = new TestAssertOwnerClassBehavior();
		try {
			$cb->callAssertOwner('prop', 'class_custom_key');
			$this->fail('Expected TInvalidOperationException');
		} catch (TInvalidOperationException $e) {
			$this->assertEquals('class_custom_key', $e->getErrorCode());
		}
	}

	public function testAssertOwner_classBehavior_includesShortClassName()
	{
		$cb = new TestAssertOwnerClassBehavior();
		try {
			$cb->callAssertOwner('someProp');
			$this->fail('Expected TInvalidOperationException');
		} catch (TInvalidOperationException $e) {
			$this->assertStringContainsString('TestAssertOwnerClassBehavior', $e->getMessage());
		}
	}

	public function testAssertOwner_classBehavior_throwsAfterAllOwnersDetached()
	{
		$cb = new TestAssertOwnerClassBehavior();
		$c1 = new TComponent();
		$c2 = new TComponent();
		$cb->attach($c1);
		$cb->attach($c2);
		$cb->detach($c1);
		$cb->callAssertOwner('prop');
		$cb->detach($c2);
		$this->expectException(TInvalidOperationException::class);
		$cb->callAssertOwner('prop');
	}

	public function testAssertOwner_classBehavior_customOwnerKeyOverride()
	{
		$cb = new class extends TestAssertOwnerClassBehavior {
			protected function getOwnerExceptionKey(): string
			{
				return 'class_custom_requires_owner';
			}
		};
		try {
			$cb->callAssertOwner('someProp');
			$this->fail('Expected TInvalidOperationException');
		} catch (TInvalidOperationException $e) {
			$this->assertEquals('class_custom_requires_owner', $e->getErrorCode());
		}
	}

	// === assertWithoutOwner (TBehavior) ===

	public function testAssertWithoutOwner_passesWithoutOwner()
	{
		$b = new TestAssertOwnerBehavior();
		$b->callAssertWithoutOwner('anyProp');
		$this->assertTrue(true);
	}

	public function testAssertWithoutOwner_throwsWithOwner()
	{
		$b = new TestAssertOwnerBehavior();
		$c = new TComponent();
		$b->attach($c);
		$this->expectException(TInvalidOperationException::class);
		$b->callAssertWithoutOwner('anyProp');
	}

	public function testAssertWithoutOwner_defaultKey()
	{
		$b = new TestAssertOwnerBehavior();
		$this->assertEquals('behavior_property_unchangeable', $b->getWithoutOwnerExceptionKeyPublic());
	}

	public function testAssertWithoutOwner_explicitKeyOverridesDefault()
	{
		$b = new TestAssertOwnerBehavior();
		$c = new TComponent();
		$b->attach($c);
		try {
			$b->callAssertWithoutOwner('someProp', 'explicit_noowner_key');
			$this->fail('Expected TInvalidOperationException');
		} catch (TInvalidOperationException $e) {
			$this->assertStringContainsString('explicit_noowner_key', $e->getErrorCode());
		}
	}

	public function testAssertWithoutOwner_customKeyOverride()
	{
		$b = new class extends TestAssertOwnerBehavior {
			protected function getWithoutOwnerExceptionKey(): string
			{
				return 'custom_property_unchangeable';
			}
		};
		$c = new TComponent();
		$b->attach($c);
		try {
			$b->callAssertWithoutOwner('someProp');
			$this->fail('Expected TInvalidOperationException');
		} catch (TInvalidOperationException $e) {
			$this->assertEquals('custom_property_unchangeable', $e->getErrorCode());
		}
	}

	// === assertWithoutOwner (TClassBehavior) ===

	public function testAssertWithoutOwner_classBehavior_passesWithoutOwner()
	{
		$cb = new TestAssertOwnerClassBehavior();
		$cb->callAssertWithoutOwner('anyProp');
		$this->assertTrue(true);
	}

	public function testAssertWithoutOwner_classBehavior_throwsWithOwner()
	{
		$cb = new TestAssertOwnerClassBehavior();
		$c = new TComponent();
		$cb->attach($c);
		$this->expectException(TInvalidOperationException::class);
		$cb->callAssertWithoutOwner('anyProp');
	}

	public function testAssertWithoutOwner_classBehavior_defaultKey()
	{
		$cb = new TestAssertOwnerClassBehavior();
		$this->assertEquals('behavior_property_unchangeable', $cb->getWithoutOwnerExceptionKeyPublic());
	}

	public function testAssertWithoutOwner_classBehavior_explicitKeyOverridesDefault()
	{
		$cb = new TestAssertOwnerClassBehavior();
		$c = new TComponent();
		$cb->attach($c);
		try {
			$cb->callAssertWithoutOwner('prop', 'explicit_class_noowner_key');
			$this->fail('Expected TInvalidOperationException');
		} catch (TInvalidOperationException $e) {
			$this->assertEquals('explicit_class_noowner_key', $e->getErrorCode());
		}
	}

	public function testAssertWithoutOwner_classBehavior_customKeyOverride()
	{
		$cb = new class extends TestAssertOwnerClassBehavior {
			protected function getWithoutOwnerExceptionKey(): string
			{
				return 'custom_class_property_unchangeable';
			}
		};
		$c = new TComponent();
		$cb->attach($c);
		try {
			$cb->callAssertWithoutOwner('someProp');
			$this->fail('Expected TInvalidOperationException');
		} catch (TInvalidOperationException $e) {
			$this->assertEquals('custom_class_property_unchangeable', $e->getErrorCode());
		}
	}

	public function testAssertWithoutOwner_classBehavior_throwsWithMultipleOwners()
	{
		$cb = new TestAssertOwnerClassBehavior();
		$c1 = new TComponent();
		$c2 = new TComponent();
		$cb->attach($c1);
		$cb->attach($c2);
		$this->expectException(TInvalidOperationException::class);
		$cb->callAssertWithoutOwner('prop');
	}

	public function testAssertWithoutOwner_classBehavior_passesAfterAllOwnersDetached()
	{
		$cb = new TestAssertOwnerClassBehavior();
		$c1 = new TComponent();
		$c2 = new TComponent();
		$cb->attach($c1);
		$cb->attach($c2);
		$cb->detach($c1);
		$cb->detach($c2);
		$cb->callAssertWithoutOwner('anyProp');
		$this->assertTrue(true);
	}
}