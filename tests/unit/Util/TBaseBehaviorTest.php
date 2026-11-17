<?php

namespace Prado\Test\Unit\Util;

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

class TBaseBehaviorTestOwner extends TComponent
{
	public function onMyEvent($param)
	{
		return $this->raiseEvent('onMyEvent', $this, $param);
	}
}

class TBaseBehaviorTestHandlers extends TBehavior
{
	public function events()
	{
		return ['onMyEvent' => 'myHandler'];
	}

	public function myHandler($sender, $param)
	{
	}

	public function callAttachEventHandlers(TComponent $component): bool
	{
		return $this->attachEventHandlers($component);
	}

	public function callDetachEventHandlers(TComponent $component): bool
	{
		return $this->detachEventHandlers($component);
	}
}

class TBaseBehaviorTestNonStrictHandlers extends TBaseBehaviorTestHandlers
{
	public function getStrictEvents(): bool
	{
		return false;
	}
}

class TBaseBehaviorTestConfigurable extends TBehavior
{
	public array $eventHandlers = [];
	public bool $strict = true;

	public function events()
	{
		return $this->eventHandlers;
	}

	public function getStrictEvents(): bool
	{
		return $this->strict;
	}
}

class TBaseBehaviorTest extends \PHPUnit\Framework\TestCase
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

	public function testMergeHandlers_appendsTheHandlersOfEachArgument()
	{
		$closure = function ($sender, $param) {};
		$result = TBaseBehavior::mergeHandlers(
			['onEvent' => ['parent1', 'parent2']],
			['onEvent' => ['child1', $closure]],
			['onEvent' => 'child2', 'onOther' => ['other1', 'other2']]
		);
		$this->assertSame([
			'onEvent' => ['parent1', 'parent2', 'child1', $closure, 'child2'],
			'onOther' => ['other1', 'other2'],
		], $result, 'a subclass adds its handlers to its parent\'s; none is dropped');
		$this->assertSame(['onEvent' => ['h1', 'h2']], TBaseBehavior::mergeHandlers(['onEvent' => ['a' => 'h1', 'b' => 'h2']]), 'the handlers are a list');
	}

	public function testMergeHandlers_mergesMultipleEvents()
	{
		$closure = function ($sender, $param) {};
		$result = TBaseBehavior::mergeHandlers(
			['onEvent2' => $closure],
			['onEvent1' => 'behaviorHandler', 'onEvent2' => [$this, __FUNCTION__], 'onEvent3' => ['behaviorHandler2', [$this, __FUNCTION__]]]
		);
		$this->assertEquals([
			'onEvent2' => [$closure, [$this, __FUNCTION__]],
			'onEvent1' => ['behaviorHandler'],
			'onEvent3' => ['behaviorHandler2', [$this, __FUNCTION__]],
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

	// === Name, Enabled, HasOwner, OwnerVisibleMethods ===

	private static function handlerCount(TComponent $owner): int
	{
		return $owner->getEventHandlers('onMyEvent')->getCount();
	}

	public function testOwnerVisibleMethods_null()
	{
		$this->assertNull((new TBehavior())->getOwnerVisibleMethods());
	}

	public function testSetName_withOwner()
	{
		$owner = new TComponent();
		$behavior = new TBehavior();
		$owner->attachBehavior('MyName', $behavior);
		$this->assertSame('myname', $behavior->getName());

		$behavior->setName('MYNAME');
		$behavior->setName(5);
		$this->assertSame('myname', $behavior->getName());

		try {
			$behavior->setName('other');
			$this->fail('setName() did not throw for a new name with an owner.');
		} catch (TInvalidOperationException $e) {
			$this->assertSame('basebehavior_cannot_setname_with_owner', $e->getErrorCode());
		}
	}

	public function testSetEnabled_syncsHandlers()
	{
		$owner = new TBaseBehaviorTestOwner();
		$behavior = new TBaseBehaviorTestHandlers();
		$this->assertFalse($behavior->getHasOwner());
		$owner->attachBehavior('b', $behavior);
		$this->assertTrue($behavior->getHasOwner());
		$this->assertSame(1, self::handlerCount($owner));

		$behavior->setEnabled('false');
		$this->assertFalse($behavior->getEnabled());
		$this->assertSame(0, self::handlerCount($owner));

		$behavior->setEnabled(false);
		$this->assertSame(0, self::handlerCount($owner));

		$behavior->setEnabled(true);
		$this->assertSame(1, self::handlerCount($owner));
	}

	// === RetainDisabledHandlers ===

	public static function retainValueProvider(): array
	{
		return [
			'0' => [0, null],
			'null' => [null, null],
			'"null"' => ['null', null],
			'"NULL"' => ['NULL', null],
			'"0"' => ['0', null],
			'true' => [true, true],
			'"true"' => ['true', true],
			'false' => [false, false],
			'"false"' => ['false', false],
			'1' => [1, true],
		];
	}

	/**
	 * @dataProvider retainValueProvider
	 */
	public function testRetainDisabledHandlers_values($value, $expected)
	{
		$behavior = new TBehavior();
		$this->assertFalse($behavior->getRetainDisabledHandlers());
		$behavior->setRetainDisabledHandlers($value);
		$this->assertSame($expected, $behavior->getRetainDisabledHandlers());
	}

	public function testRetainDisabledHandlers_syncsHandlers()
	{
		$owner = new TBaseBehaviorTestOwner();
		$behavior = new TBaseBehaviorTestHandlers();
		$owner->attachBehavior('b', $behavior);
		$behavior->setEnabled(false);
		$this->assertSame(0, self::handlerCount($owner));

		$behavior->setRetainDisabledHandlers(true);
		$this->assertSame(1, self::handlerCount($owner));
		$behavior->setRetainDisabledHandlers(true);
		$this->assertSame(1, self::handlerCount($owner));

		$behavior->setEnabled(true);
		$behavior->setRetainDisabledHandlers(null);
		$this->assertSame(0, self::handlerCount($owner));

		$behavior->setRetainDisabledHandlers(false);
		$this->assertSame(1, self::handlerCount($owner));
	}

	// === syncEventHandlers ===

	public function testSyncEventHandlers_withoutOwner()
	{
		$behavior = new TBaseBehaviorTestHandlers();
		$behavior->syncEventHandlers();

		try {
			$behavior->syncEventHandlers(new TComponent());
			$this->fail('syncEventHandlers() did not throw for a component without an owner.');
		} catch (TInvalidOperationException $e) {
			$this->assertSame('basebehavior_sync_no_owner', $e->getErrorCode());
		}
	}

	public function testSyncEventHandlers_notOwner()
	{
		$behavior = new TBaseBehaviorTestHandlers();
		$owner = new TBaseBehaviorTestOwner();
		$owner->attachBehavior('b', $behavior);

		try {
			$behavior->syncEventHandlers(new TComponent());
			$this->fail('syncEventHandlers() did not throw for a component that is not an owner.');
		} catch (TInvalidOperationException $e) {
			$this->assertSame('basebehavior_sync_not_owner', $e->getErrorCode());
		}
	}

	public function testSyncEventHandlers_attachOverride()
	{
		$owner = new TBaseBehaviorTestOwner();
		$behavior = new TBaseBehaviorTestHandlers();
		$owner->attachBehavior('b', $behavior);

		$behavior->syncEventHandlers($owner, null);
		$this->assertNull($behavior->getRetainDisabledHandlers());
		$this->assertSame(0, self::handlerCount($owner));

		$behavior->syncEventHandlers($owner, true);
		$this->assertTrue($behavior->getRetainDisabledHandlers());
		$this->assertSame(1, self::handlerCount($owner));

		$owner->disableBehaviors();
		$this->assertSame(1, self::handlerCount($owner));
		$behavior->syncEventHandlers(null, false);
		$this->assertFalse($behavior->getRetainDisabledHandlers());
		$this->assertSame(0, self::handlerCount($owner));
	}

	// === attachEventHandlers / detachEventHandlers ===

	public function testAttachDetachEventHandlers_onlyOnStatusChange()
	{
		$owner = new TBaseBehaviorTestOwner();
		$behavior = new TBaseBehaviorTestHandlers();
		$owner->attachBehavior('b', $behavior);

		$this->assertFalse($behavior->callAttachEventHandlers($owner));
		$this->assertSame(1, self::handlerCount($owner));
		$this->assertTrue($behavior->callDetachEventHandlers($owner));
		$this->assertFalse($behavior->callDetachEventHandlers($owner));
		$this->assertSame(0, self::handlerCount($owner));
	}

	public function testNonStrictEvents_attachToTheOwnersEvents()
	{
		$owner = new TBaseBehaviorTestOwner();
		$behavior = new TBaseBehaviorTestNonStrictHandlers();
		$owner->attachBehavior('b', $behavior);
		$this->assertSame(1, self::handlerCount($owner));

		$owner->detachBehavior('b');
		$this->assertSame(0, self::handlerCount($owner));
	}

	public function testNonStrictEvents_skipMissingOwnerEvents()
	{
		$owner = new TComponent();
		$behavior = new TBaseBehaviorTestNonStrictHandlers();
		$owner->attachBehavior('b', $behavior);
		$this->assertFalse($owner->hasEventHandler('onMyEvent'));

		$owner->detachBehavior('b');
		$this->assertFalse($behavior->getHasOwner());
	}

	// === Serialization ===

	public static function serializeProvider(): array
	{
		return [
			'default' => [true, false],
			'disabled' => [false, false],
			'retain' => [true, true],
			'retain null' => [true, null],
			'disabled, retain null' => [false, null],
		];
	}

	/**
	 * @dataProvider serializeProvider
	 */
	public function testSerialize_keepsEnabledAndRetainDisabledHandlers(bool $enabled, ?bool $retain)
	{
		$behavior = new TBehavior();
		$behavior->setEnabled($enabled);
		$behavior->setRetainDisabledHandlers($retain);

		$copy = unserialize(serialize($behavior));
		$this->assertSame($enabled, $copy->getEnabled());
		$this->assertSame($retain, $copy->getRetainDisabledHandlers());
		$this->assertNull($copy->getName());
	}

	public static function handlerKindProvider(): array
	{
		return [
			'strict, closure' => [true, 'closure'],
			'strict, no handlers' => [true, 'none'],
			'non-strict, closure' => [false, 'closure'],
			'non-strict, no handlers' => [false, 'none'],
		];
	}

	/**
	 * @dataProvider handlerKindProvider
	 */
	public function testAttachDetachEventHandlers_handlerKinds(bool $strict, string $kind)
	{
		$owner = new TBaseBehaviorTestOwner();
		$behavior = new TBaseBehaviorTestConfigurable();
		$behavior->strict = $strict;
		$closure = function ($sender, $param) {
		};
		$behavior->eventHandlers = ['onMyEvent' => $kind === 'closure' ? $closure : []];
		$owner->attachBehavior('b', $behavior);
		$this->assertSame($kind === 'closure' ? 1 : 0, self::handlerCount($owner));
		if ($kind === 'closure') {
			$this->assertSame($closure, $owner->getEventHandlers('onMyEvent')->itemAt(0));
		}

		$owner->detachBehavior('b');
		$this->assertSame(0, self::handlerCount($owner));
	}
}
