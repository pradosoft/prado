<?php

use Prado\Exceptions\TInvalidOperationException;
use Prado\Util\TBaseBehavior;
use Prado\Util\TBehavior;

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

class TBaseBehaviorTest extends PHPUnit\Framework\TestCase
{	
	public function testMergeHandlers()
	{
		self::assertEquals([], TBaseBehavior::mergeHandlers());
		self::assertEquals([], TBaseBehavior::mergeHandlers([]));
		self::assertEquals([
			'onEvent1' => [ 'behaviorHandler' ],
			'onEvent2' => [$closure = function ($sender, $param) {}, [$this, 'testMergeHandlers']],
			'onEvent3' => ['behaviorHandler2', [$this, 'testMergeHandlers']],
		], TBaseBehavior::mergeHandlers( ['onEvent2' => $closure],
			['onEvent1' => 'behaviorHandler', 'onEvent2' => [$this, 'testMergeHandlers'], 'onEvent3' => ['behaviorHandler2', [$this, 'testMergeHandlers']]]));
	}
	
	public function testStrictEvents() 
	{
		// We cannot attach when behavior event handlers are strict.
		$component = new TComponent();
		$strictBehavior = new StrictEventsBehavior();
		self::assertTrue($strictBehavior->getStrictEvents());
		try {
			$component->attachBehavior('strict', $strictBehavior);
			self::fail("TInvalidOperationException not thrown when attaching strict behavior event handlers");
		} catch(TInvalidOperationException $e){
		}
	}
	
	public function testNonStrictEvents()
	{
		$component = new TComponent();
		// We can attach when behavior event handlers are not strict.
		$nonStrictBehavior = new NonStrictEventsBehavior();
		self::assertFalse($nonStrictBehavior->getStrictEvents());
		$component->attachBehavior('nonstrict', $nonStrictBehavior);
	}
}
