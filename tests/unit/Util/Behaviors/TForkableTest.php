<?php

use Prado\TComponent;
use Prado\Util\Behaviors\TForkable;


class TTestForkablePrepare extends TComponent
{
	public function fxPrepareForFork($sender, $param)
	{
		
	}
}

class TTestForkableRestore extends TComponent
{
	
	public function fxRestoreAfterFork($sender, $param)
	{
		
	}
}
class TTestForkableBehavior extends TComponent
{
	public function fxPrepareForFork($sender, $param)
	{
		
	}
	
	public function fxRestoreAfterFork($sender, $param)
	{
		
	}
}

class TForkableTest extends PHPUnit\Framework\TestCase
{
	protected $behavior;

	protected function setUp(): void
	{
		$this->behavior = new TForkable();
	}
	

	protected function tearDown(): void
	{
		$this->behavior = null;
	}
	
	public function testAttachDetach()
	{
		$name = 'forkable';
		$component = new TComponent();
		
		self::assertFalse($component->hasEventHandler('fxPrepareForFork'));
		self::assertFalse($component->hasEventHandler('fxRestoreAfterFork'));

		$component->attachBehavior($name, $this->behavior);
		
		self::assertFalse($component->hasEventHandler('fxPrepareForFork'));
		self::assertFalse($component->hasEventHandler('fxRestoreAfterFork'));
	
		$component->detachBehavior($name);
		self::assertFalse($component->hasEventHandler('fxPrepareForFork'));
		self::assertFalse($component->hasEventHandler('fxRestoreAfterFork'));


		$component = new TTestForkablePrepare();
		
		self::assertFalse($component->hasEventHandler('fxPrepareForFork'));
		self::assertFalse($component->hasEventHandler('fxRestoreAfterFork'));
		
		$component->attachBehavior($name, $this->behavior);
		self::assertTrue($component->hasEventHandler('fxPrepareForFork'));
		self::assertEquals([[$component, 'fxPrepareForFork']], $component->getEventHandlers('fxPrepareForFork')->toArray());
		self::assertFalse($component->hasEventHandler('fxRestoreAfterFork'));
		
		$component->detachBehavior($name);
		self::assertFalse($component->hasEventHandler('fxPrepareForFork'));
		self::assertFalse($component->hasEventHandler('fxRestoreAfterFork'));
		
		
		$component = new TTestForkableRestore();
		
		self::assertFalse($component->hasEventHandler('fxPrepareForFork'));
		self::assertFalse($component->hasEventHandler('fxRestoreAfterFork'));
		
		$component->attachBehavior($name, $this->behavior);
		self::assertFalse($component->hasEventHandler('fxPrepareForFork'));
		self::assertEquals([[$component, 'fxRestoreAfterFork']], $component->getEventHandlers('fxRestoreAfterFork')->toArray());
		self::assertTrue($component->hasEventHandler('fxRestoreAfterFork'));
		
		$component->detachBehavior($name);
		self::assertFalse($component->hasEventHandler('fxPrepareForFork'));
		self::assertFalse($component->hasEventHandler('fxRestoreAfterFork'));
		
		
		$component = new TTestForkableBehavior();
		
		self::assertFalse($component->hasEventHandler('fxPrepareForFork'));
		self::assertFalse($component->hasEventHandler('fxRestoreAfterFork'));
		
		$component->attachBehavior($name, $this->behavior);
		self::assertTrue($component->hasEventHandler('fxPrepareForFork'));
		self::assertEquals([[$component, 'fxRestoreAfterFork']], $component->getEventHandlers('fxRestoreAfterFork')->toArray());
		self::assertEquals([[$component, 'fxPrepareForFork']], $component->getEventHandlers('fxPrepareForFork')->toArray());
		self::assertTrue($component->hasEventHandler('fxRestoreAfterFork'));
		
		$component->detachBehavior($name);
		self::assertFalse($component->hasEventHandler('fxPrepareForFork'));
		self::assertFalse($component->hasEventHandler('fxRestoreAfterFork'));
	}
	
}
