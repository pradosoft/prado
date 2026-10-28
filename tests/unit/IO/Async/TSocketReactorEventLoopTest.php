<?php

namespace Prado\Test\Unit\IO\Async;

use Prado\IO\Async\IEventLoop;
use Prado\IO\Async\ITickableEventLoop;
use Prado\IO\Async\TLoopSuspension;
use Prado\IO\Socket\TSocketReactor;

/**
 * Runs the IEventLoop conformance suite against TSocketReactor, plus the tickable seams.
 */
class TSocketReactorEventLoopTest extends EventLoopConformanceTestCase
{
	protected function createLoop(): IEventLoop
	{
		return new TSocketReactor();
	}

	protected function createChildLoop(): IEventLoop
	{
		return new TSocketReactor();
	}

	public function testReactorIsTickable(): void
	{
		self::assertInstanceOf(ITickableEventLoop::class, $this->loop);
		self::assertTrue($this->loop->isEmpty());
		$ran = false;
		$this->loop->defer(function () use (&$ran) {
			$ran = true;
		});
		self::assertFalse($this->loop->isEmpty());
		$this->loop->tick(0);
		self::assertTrue($ran, 'tick() runs one iteration.');
		self::assertTrue($this->loop->isEmpty());
	}

	public function testSuspensionIsATLoopSuspension(): void
	{
		self::assertInstanceOf(TLoopSuspension::class, $this->loop->suspension());
	}

	public function testSuspendFromMainOnAnEmptyLoopThrows(): void
	{
		$suspension = $this->loop->suspension();
		$this->expectException(\Error::class);
		$suspension->suspend();
	}

	public function testSuspendTwiceThrows(): void
	{
		$suspension = $this->loop->suspension();
		$this->loop->delay(0, function () use ($suspension) {
			try {
				$suspension->suspend();
			} catch (\Error $e) {
				$suspension->resume($e->getMessage());
			}
		});
		self::assertStringContainsString('suspend()', $suspension->suspend(), 'A second suspend() before the resume is refused.');
	}

	public function testWorkerFiberThrowIsDeliveredByTheLoop(): void
	{
		$fiber = new \Fiber(function (): string {
			$suspension = $this->loop->suspension();
			$this->loop->delay(0, fn () => $suspension->throw(new \RuntimeException('fiber boom')));
			try {
				$suspension->suspend();
				return 'no exception';
			} catch (\RuntimeException $e) {
				return $e->getMessage();
			}
		});
		$fiber->start();
		$this->loop->run();
		self::assertSame('fiber boom', $fiber->getReturn());
	}
}
