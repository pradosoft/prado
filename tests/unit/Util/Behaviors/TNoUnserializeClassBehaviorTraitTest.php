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
}
