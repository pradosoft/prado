<?php

use Prado\TComponent;
use Prado\Util\Behaviors\TGlobalClassAware;


class TGlobalClassAwareTest extends PHPUnit\Framework\TestCase
{
	protected $behavior;

	protected function setUp(): void
	{
		$this->behavior = new TGlobalClassAware();
	}
	

	protected function tearDown(): void
	{
		$this->behavior = null;
	}
	
	public function testAttachDetach()
	{
		$name = 'globalclassaware';
		$component = new TComponent();
		
		self::assertFalse($component->hasEventHandler('fxAttachClassBehavior'));
		self::assertFalse($component->hasEventHandler('fxDetachClassBehavior'));

		$component->attachBehavior($name, $this->behavior);
		
		self::assertTrue($component->hasEventHandler('fxAttachClassBehavior'));
		self::assertEquals([[$component, 'fxAttachClassBehavior']], $component->getEventHandlers('fxAttachClassBehavior')->toArray());
		self::assertTrue($component->hasEventHandler('fxDetachClassBehavior'));
		self::assertEquals([[$component, 'fxDetachClassBehavior']], $component->getEventHandlers('fxDetachClassBehavior')->toArray());
	
		$component->detachBehavior($name);
		self::assertFalse($component->hasEventHandler('fxAttachClassBehavior'));
		self::assertFalse($component->hasEventHandler('fxDetachClassBehavior'));


	}
	
}
