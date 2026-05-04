<?php

use Prado\TComponent;
use Prado\Util\TClassBehavior;
use Prado\Util\Behaviors\TNoUnserializeClassBehaviorTrait;

class DeprecatedClassTestTComponent extends TComponent
{
}

class TTestNonDeprecatedClassBehaviorClass extends TClassBehavior
{
}
class TTestDeprecatedClassBehaviorClass extends TTestNonDeprecatedClassBehaviorClass
{
	use TNoUnserializeClassBehaviorTrait;
}

class TNoUnserializeClassBehaviorTraitTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
	}

	protected function tearDown(): void
	{
	}

	public function testDyWakeUp()
	{
		$component = new DeprecatedClassTestTComponent();
		$component->attachBehavior('b1', $b1 = new TTestNonDeprecatedClassBehaviorClass());
		$component->attachBehavior('b2', $b2 = new TTestDeprecatedClassBehaviorClass());
			
		$data = serialize($component);
		
		$copy = unserialize($data);
		self::assertNotNull($copy->asa('b1'));
		self::assertNull($copy->asa('b2'));
		self::assertNotNull($component->asa('b2'));
		
		$component->dyWakeUp();
		self::assertNotNull($component->asa('b1'));
		self::assertNull($component->asa('b2'));
	}

	public function testDyWakeUpAtIndexZero()
	{
		$component = new DeprecatedClassTestTComponent();
		$component->attachBehavior(null, $deprecated = new TTestDeprecatedClassBehaviorClass());
		$component->attachBehavior(null, $normal = new TTestNonDeprecatedClassBehaviorClass());
		
		$behaviors = $component->getBehaviors();
		$deprecatedIndex = array_search($deprecated, $behaviors, true);
		self::assertEquals(0, $deprecatedIndex, 'Deprecated behavior should be at index 0');
		
		$data = serialize($component);
		
		$copy = unserialize($data);
		self::assertNotNull($copy->asa(1));
		self::assertNull($copy->asa(0), 'Deprecated behavior should be detached after unserialize at index 0');
	}
}
