<?php

use Prado\TComponent;
use Prado\Util\TBehavior;
use Prado\Util\Behaviors\TNoUnserializeBehaviorTrait;

class DeprecatedTestTComponent extends TComponent
{
}

class TTestNonDeprecatedBehaviorClass extends TBehavior
{
}
class TTestDeprecatedBehaviorClass extends TTestNonDeprecatedBehaviorClass
{
	use TNoUnserializeBehaviorTrait;
}

class TNoUnserializeBehaviorTraitTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
	}

	protected function tearDown(): void
	{
	}

	public function testDyWakeUp()
	{
		$component = new DeprecatedTestTComponent();
		$component->attachBehavior('b1', $b1 = new TTestNonDeprecatedBehaviorClass());
		$component->attachBehavior('b2', $b2 = new TTestDeprecatedBehaviorClass());
		
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
