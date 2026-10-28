<?php

namespace Prado\Test\Unit\IO\Async;

use Prado\IO\Async\IEventLoop;
use Prado\IO\Async\TRevoltEventLoop;
use Revolt\EventLoop;

/**
 * Runs the IEventLoop conformance suite against TRevoltEventLoop, plus the adapter's own seams.
 */
class TRevoltEventLoopTest extends EventLoopConformanceTestCase
{
	protected function createLoop(): IEventLoop
	{
		return new TRevoltEventLoop();
	}

	protected function createChildLoop(): IEventLoop
	{
		TRevoltEventLoop::installFreshDriver();
		return new TRevoltEventLoop();
	}

	public function testInstancesShareTheProcessWideDriver(): void
	{
		$other = new TRevoltEventLoop();
		$id = $other->delay(10, fn () => null);
		self::assertContains($id, $this->loop->getIdentifiers(), 'Every instance addresses the same Revolt driver.');
		self::assertContains($id, EventLoop::getIdentifiers());
		$this->loop->cancel($id);
		self::assertNotContains($id, $other->getIdentifiers());
	}

	public function testErrorHandlerIsTheRevoltErrorHandler(): void
	{
		$handler = fn (\Throwable $e) => null;
		$this->loop->setErrorHandler($handler);
		self::assertNotNull(EventLoop::getErrorHandler(), 'The handler is installed on the driver.');
		self::assertNotNull($this->loop->getErrorHandler());
		$this->loop->setErrorHandler(null);
		self::assertNull(EventLoop::getErrorHandler());
	}

	public function testRunFromAWorkerFiberThrows(): void
	{
		$fiber = new \Fiber(function (): ?string {
			try {
				$this->loop->run();
				return null;
			} catch (\Error $e) {
				return $e->getMessage();
			}
		});
		$fiber->start();
		self::assertNotNull($fiber->getReturn(), 'Revolt refuses run() inside a fiber.');
	}
}
