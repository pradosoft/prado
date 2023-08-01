<?php

use Prado\Util\Behaviors\TApplicationSignals;
use Prado\Util\TSignalsDispatcher;

class TTestAppSignalsDispatcher extends TSignalsDispatcher
{
	
}

class TTestApplicationSignals extends TApplicationSignals
{
	
}

class TApplicationSignalTest extends PHPUnit\Framework\TestCase
{
	public const BEHAVIOR_NAME = 'appSignals';

	protected $behavior;

	protected function setUp(): void
	{
		$this->behavior = new TApplicationSignals();
	}

	protected function tearDown(): void
	{
		$this->behavior = null;
	}

	public function testAttachDetach()
	{
		$app = Prado::getApplication();
		
		self::assertNull(TSignalsDispatcher::singleton(false));
		$this->behavior->setSignalsClass(TTestAppSignalsDispatcher::class);
		
		$app->attachBehavior($name = 'appSignals', $this->behavior);
		self::assertInstanceOf(TTestAppSignalsDispatcher::class, TSignalsDispatcher::singleton(false));
		
		try {
			$this->behavior->setSignalsClass(null);
			self::fail("TInvalidOperationException not thrown when behavior already attached.");
		} catch(TInvalidOperationException $e) {
		}
		
		try {
			$this->behavior->setPriorHandlerPriority(20);
			self::fail("TInvalidOperationException not thrown when behavior already instanced.");
		} catch(TInvalidOperationException $e) {
		}
		
		self::assertEquals(TSignalsDispatcher::singleton(), $app->getSignalsDispatcher());
		
		$app->detachBehavior($name);
		
		self::assertNull(TSignalsDispatcher::singleton(false));
	}

	public function testSignalsClass()
	{
		self::assertEquals(TSignalsDispatcher::class, $this->behavior->getSignalsClass());
			
		$this->behavior->setSignalsClass(TTestAppSignalsDispatcher::class);
		
		self::assertEquals(TTestAppSignalsDispatcher::class, $this->behavior->getSignalsClass());
		
		$this->behavior->setSignalsClass(null);
		
		self::assertEquals(TSignalsDispatcher::class, $this->behavior->getSignalsClass());
			
		self::expectException(TInvalidDataValueException::class);
		$this->behavior->setSignalsClass(TComponent::class);
	}

	public function testAsyncSignals()
	{
		if (!TSignalsDispatcher::hasSignals()) {
			$this->markTestSkipped("skipping " . TSignalsDispatcher::class . "::alarm and ::disarm.");
			return;
		}
		
		$ogAsyncSignal = $this->behavior->getAsyncSignals();
		
		self::assertEquals($ogAsyncSignal, $this->behavior->setAsyncSignals(true));
		self::assertTrue($this->behavior->getAsyncSignals());
		self::assertTrue($this->behavior->setAsyncSignals(false));
		self::assertFalse($this->behavior->getAsyncSignals());
		self::assertFalse($this->behavior->setAsyncSignals("true"));
		self::assertTrue($this->behavior->getAsyncSignals());
		self::assertTrue($this->behavior->setAsyncSignals("false"));
		self::assertFalse($this->behavior->getAsyncSignals());
		
		$this->behavior->setAsyncSignals(true);
		pcntl_async_signals($ogAsyncSignal);
		self::assertEquals($ogAsyncSignal, $this->behavior->getAsyncSignals());
	}

	public function testPriorHandlerPriority()
	{
		$ogHandlerPriority = $this->behavior->getPriorHandlerPriority();
		
		self::assertTrue($this->behavior->setPriorHandlerPriority(3));
		self::assertEquals(3, $this->behavior->getPriorHandlerPriority());
		self::assertTrue($this->behavior->setPriorHandlerPriority("11"));
		self::assertEquals(11, $this->behavior->getPriorHandlerPriority());
		self::assertTrue($this->behavior->setPriorHandlerPriority(5));
		self::assertEquals(5, $this->behavior->getPriorHandlerPriority());
		
		self::assertTrue($this->behavior->setPriorHandlerPriority(null));
		self::assertNull($this->behavior->getPriorHandlerPriority());
		self::assertTrue($this->behavior->setPriorHandlerPriority($ogHandlerPriority));
		self::assertEquals($ogHandlerPriority, $this->behavior->getPriorHandlerPriority());
	}
}
