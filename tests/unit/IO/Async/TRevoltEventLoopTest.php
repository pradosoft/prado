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

	public function testCancellingTheLastSignalCallbackRestoresThePriorHandler(): void
	{
		if (!function_exists('pcntl_signal')) {
			self::markTestSkipped('pcntl is required for signal callbacks.');
		}
		$original = pcntl_signal_get_handler(SIGUSR1);
		$prior = static function (int $signal): void {
		};
		pcntl_signal(SIGUSR1, $prior);
		try {
			$id = $this->loop->onSignal(SIGUSR1, fn () => null);
			$this->loop->delay(0, fn () => $this->loop->stop());   // a lone signal callback keeps run() alive
			$this->loop->run();   // the driver installs its handler as the loop activates the callback
			self::assertNotSame($prior, pcntl_signal_get_handler(SIGUSR1), 'The driver holds the signal while a callback is registered.');
			$this->loop->cancel($id);
			self::assertSame($prior, pcntl_signal_get_handler(SIGUSR1), 'The prior handler returns once the last callback is cancelled.');
		} finally {
			pcntl_signal(SIGUSR1, $original);
		}
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
