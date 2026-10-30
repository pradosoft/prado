<?php

/**
 * EventLoopConformanceTestCase class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\IO\Async;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\Async\IEventLoop;
use Prado\IO\Process\TProcessFork;
use Prado\IO\Socket\TSocketStream;
use Prado\Util\Helpers\TProcessHelper;

/**
 * The IEventLoop conformance suite.  A concrete subclass names a loop in {@see createLoop()}
 * and the suite asserts the contract every loop shares: callback order, timer order, resume
 * from a callback for the main and a worker fiber, exception isolation, cancellation that
 * leaves no callback, and a fork made while a fiber is suspended.
 *
 * Timers run on each loop's own clock.  A pinned {@see \Prado\Util\Clock\TMockClock} advances
 * the {@see \Prado\IO\Socket\TSocketReactor} timers, which read the injected clock; it does not
 * advance {@see \Prado\IO\Async\TRevoltEventLoop} timers, which read Revolt's monotonic clock.
 * A test that pins a clock under scheduled work uses the reactor; this suite uses real delays
 * so it holds for both.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
abstract class EventLoopConformanceTestCase extends \PHPUnit\Framework\TestCase
{
	protected IEventLoop $loop;

	/**
	 * @return IEventLoop The loop under test.
	 */
	abstract protected function createLoop(): IEventLoop;

	/**
	 * @return IEventLoop A loop with no callbacks, installed in a forked child.
	 */
	abstract protected function createChildLoop(): IEventLoop;

	protected function setUp(): void
	{
		$this->loop = $this->createLoop();
		$this->clearLoop();
	}

	protected function tearDown(): void
	{
		$this->clearLoop();
	}

	/**
	 * Cancels every callback, unsets the error handler, and runs the emptied loop once so a
	 * process-wide driver unwinds any interrupted loop fiber; one test then does not leak into the
	 * next.
	 */
	private function clearLoop(): void
	{
		$this->loop->setErrorHandler(null);
		foreach ($this->loop->getIdentifiers() as $id) {
			$this->loop->cancel($id);
		}
		$this->loop->run();
	}

	// =========================================================================
	// Ordering
	// =========================================================================

	public function testDeferredCallbacksRunInOrderBeforeTimers(): void
	{
		$order = [];
		$this->loop->delay(0, function () use (&$order) {
			$order[] = 'timer';
		});
		$this->loop->defer(function () use (&$order) {
			$order[] = 'a';
		});
		$this->loop->defer(function () use (&$order) {
			$order[] = 'b';
		});
		$this->loop->run();
		self::assertSame(['a', 'b', 'timer'], $order, 'Deferred callbacks run in order, before a due timer.');
		self::assertSame([], $this->loop->getIdentifiers(), 'One-shot callbacks are gone after they run.');
	}

	public function testCallbacksReceiveTheirIdentifier(): void
	{
		$got = [];
		$deferId = $this->loop->defer(function (string $id) use (&$got) {
			$got['defer'] = $id;
		});
		$delayId = $this->loop->delay(0, function (string $id) use (&$got) {
			$got['delay'] = $id;
		});
		$this->loop->run();
		self::assertSame(['defer' => $deferId, 'delay' => $delayId], $got);
	}

	public function testTimersFireInDeadlineOrder(): void
	{
		$order = [];
		$this->loop->delay(0.03, function () use (&$order) {
			$order[] = 'a';
		});
		$this->loop->delay(0.01, function () use (&$order) {
			$order[] = 'b';
		});
		$this->loop->delay(0.02, function () use (&$order) {
			$order[] = 'c';
		});
		$this->loop->run();
		self::assertSame(['b', 'c', 'a'], $order, 'Timers fire by deadline, not by registration.');
	}

	public function testRepeatFiresEachIntervalUntilCancelled(): void
	{
		$count = 0;
		$this->loop->repeat(0.002, function (string $id) use (&$count) {
			if (++$count >= 3) {
				$this->loop->cancel($id);
			}
		});
		$this->loop->run();
		self::assertSame(3, $count, 'A repeating timer fires until it is cancelled.');
		self::assertSame([], $this->loop->getIdentifiers(), 'Cancelling from the callback removes the timer.');
	}

	public function testNegativeDelayIsRejected(): void
	{
		$this->expectException(TInvalidDataValueException::class);
		$this->loop->delay(-1, fn () => null);
	}

	// =========================================================================
	// Run and stop
	// =========================================================================

	public function testRunReturnsWhenNoEnabledCallbackRemains(): void
	{
		$start = microtime(true);
		$this->loop->run();
		self::assertLessThan(0.5, microtime(true) - $start, 'An empty loop returns from run() at once.');
		self::assertFalse($this->loop->isRunning());
	}

	public function testStopEndsRunAndKeepsTheCallbacks(): void
	{
		$count = 0;
		$running = null;
		$id = $this->loop->repeat(0.001, function () use (&$count, &$running) {
			$running = $this->loop->isRunning();
			if (++$count >= 2) {
				$this->loop->stop();
			}
		});
		$this->loop->run();
		self::assertSame(2, $count, 'stop() ends run() after the current callback.');
		self::assertTrue($running, 'isRunning() is true inside a callback.');
		self::assertContains($id, $this->loop->getIdentifiers(), 'stop() leaves the callbacks registered.');
		self::assertFalse($this->loop->isRunning());
	}

	public function testStopOutsideRunIsANoOp(): void
	{
		$this->loop->stop();
		$suspension = $this->loop->suspension();
		$this->loop->delay(0.001, fn () => $suspension->resume('ok'));
		self::assertSame('ok', $suspension->suspend(), 'A stop() outside run() leaves the next wait running.');
		$ran = false;
		$this->loop->stop();
		$this->loop->delay(0.001, function () use (&$ran) {
			$ran = true;
		});
		$this->loop->run();
		self::assertTrue($ran, 'A stop() outside run() leaves the next run() running.');
	}

	public function testStopFromAWorkerFiberOutsideRunIsANoOp(): void
	{
		$suspension = $this->loop->suspension();
		$this->loop->delay(0.001, fn () => $suspension->resume('ok'));
		self::assertSame('ok', $suspension->suspend(), 'A main-fiber wait completes first.');
		$running = null;
		(new \Fiber(function () use (&$running): void {
			$running = $this->loop->isRunning();
			$this->loop->stop();
		}))->start();
		self::assertFalse($running, 'isRunning() is false in a worker fiber outside run().');
		$ran = false;
		$this->loop->delay(0.001, function () use (&$ran) {
			$ran = true;
		});
		$this->loop->run();
		self::assertTrue($ran, 'A stop() from a worker fiber outside run() leaves the next run() running.');
	}

	// =========================================================================
	// Suspension
	// =========================================================================

	public function testSuspendFromTheMainFiberResumesFromACallback(): void
	{
		$suspension = $this->loop->suspension();
		$this->loop->delay(0.01, fn () => $suspension->resume('ok'));
		self::assertSame('ok', $suspension->suspend(), 'suspend() from main runs the loop until the callback resumes it.');
	}

	public function testSuspendFromAWorkerFiberResumesFromTheLoop(): void
	{
		$fiber = new \Fiber(function (): string {
			$suspension = $this->loop->suspension();
			$this->loop->delay(0.01, fn () => $suspension->resume('fiber'));
			return $suspension->suspend();
		});
		$fiber->start();
		self::assertTrue($fiber->isSuspended(), 'The worker fiber yields to the loop.');
		$this->loop->run();
		self::assertTrue($fiber->isTerminated(), 'The loop re-enters the fiber from the callback.');
		self::assertSame('fiber', $fiber->getReturn());
	}

	public function testThrowDeliversAnExceptionToSuspend(): void
	{
		$suspension = $this->loop->suspension();
		$this->loop->delay(0, fn () => $suspension->throw(new \RuntimeException('boom')));
		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('boom');
		$suspension->suspend();
	}

	public function testASuspensionServesItsFiberRepeatedly(): void
	{
		$suspension = $this->loop->suspension();
		self::assertSame($suspension, $this->loop->suspension(), 'One suspension per fiber.');
		$this->loop->delay(0, fn () => $suspension->resume(1));
		self::assertSame(1, $suspension->suspend());
		$this->loop->delay(0, fn () => $suspension->resume(2));
		self::assertSame(2, $suspension->suspend(), 'The same suspension suspends again after a resume.');
	}

	public function testResumeWithoutAPendingSuspendThrows(): void
	{
		$this->expectException(\Error::class);
		$this->loop->suspension()->resume();
	}

	// =========================================================================
	// Exception isolation
	// =========================================================================

	public function testErrorHandlerReceivesCallbackExceptionsAndTheLoopContinues(): void
	{
		$caught = [];
		$this->loop->setErrorHandler(function (\Throwable $e) use (&$caught) {
			$caught[] = $e;
		});
		$this->loop->defer(fn () => throw new \LogicException('first'));
		$later = false;
		$this->loop->delay(0.005, function () use (&$later) {
			$later = true;
		});
		$this->loop->run();
		self::assertCount(1, $caught);
		self::assertInstanceOf(\LogicException::class, $caught[0]);
		self::assertTrue($later, 'The loop continues past a throwing callback when a handler is set.');
	}

	public function testUncaughtCallbackExceptionLeavesRunWithTheLoopUsable(): void
	{
		$this->loop->defer(fn () => throw new \DomainException('uncaught'));
		$later = false;
		$this->loop->delay(0.005, function () use (&$later) {
			$later = true;
		});
		try {
			$this->loop->run();
			self::fail('The callback exception leaves run().');
		} catch (\DomainException $e) {
			self::assertSame('uncaught', $e->getMessage(), 'The original exception leaves run().');
		}
		self::assertFalse($this->loop->isRunning());
		$this->loop->run();
		self::assertTrue($later, 'The other callbacks stay registered and run afterwards.');
	}

	// =========================================================================
	// Cancellation, enable and disable
	// =========================================================================

	public function testCancelLeavesNoCallback(): void
	{
		[$a, $b] = TSocketStream::pair();
		$ids = [
			$this->loop->delay(10, fn () => null),
			$this->loop->repeat(10, fn () => null),
			$this->loop->defer(fn () => null),
			$this->loop->onReadable($a, fn () => null),
			$this->loop->onWritable($b, fn () => null),
		];
		foreach ($ids as $id) {
			self::assertContains($id, $this->loop->getIdentifiers());
			$this->loop->cancel($id);
		}
		foreach ($ids as $id) {
			self::assertNotContains($id, $this->loop->getIdentifiers(), 'A cancelled callback is gone.');
		}
		$this->loop->cancel('no-such-callback');   // an unknown identifier is ignored
		$start = microtime(true);
		$this->loop->run();
		self::assertLessThan(0.5, microtime(true) - $start, 'Nothing remains to run.');
		$a->close();
		$b->close();
	}

	public function testDisabledCallbacksDoNotRunUntilEnabled(): void
	{
		$count = 0;
		$id = $this->loop->repeat(0.001, function () use (&$count) {
			$count++;
		});
		$this->loop->disable($id);
		$this->loop->delay(0.01, fn () => null);
		$this->loop->run();
		self::assertSame(0, $count, 'A disabled timer does not fire and does not keep run() alive.');

		$this->loop->enable($id);
		$this->loop->delay(0.02, fn () => $this->loop->cancel($id));
		$this->loop->run();
		self::assertGreaterThan(0, $count, 'An enabled timer fires again.');
	}

	public function testEnableOfAnUnknownIdentifierThrows(): void
	{
		$this->expectException(TInvalidDataValueException::class);
		$this->loop->enable('no-such-callback');
	}

	// =========================================================================
	// Streams
	// =========================================================================

	public function testOnReadableDeliversTheStreamAsRegistered(): void
	{
		[$a, $b] = TSocketStream::pair();
		$a->setBlocking(false);
		$seen = null;
		$data = null;
		$this->loop->onReadable($a, function (string $id, $stream) use (&$seen, &$data) {
			$seen = $stream;
			$data = $stream->read(1024);
			$this->loop->cancel($id);
		});
		$b->write('ping');
		$this->loop->run();
		self::assertSame($a, $seen, 'The callback receives the IResource it registered.');
		self::assertSame('ping', $data);
		$a->close();
		$b->close();
	}

	public function testOnWritableFiresForAWritableSocket(): void
	{
		[$a, $b] = TSocketStream::pair();
		$fired = 0;
		$this->loop->onWritable($b, function (string $id) use (&$fired) {
			$fired++;
			$this->loop->cancel($id);
		});
		$this->loop->run();
		self::assertSame(1, $fired);
		$a->close();
		$b->close();
	}

	public function testTwoCallbacksWatchOneStream(): void
	{
		[$a, $b] = TSocketStream::pair();
		$a->setBlocking(false);
		$fired = [];
		$this->loop->onReadable($a, function (string $id) use (&$fired) {
			$fired[] = 'first';
			$this->loop->cancel($id);
		});
		$this->loop->onReadable($a, function (string $id) use (&$fired) {
			$fired[] = 'second';
			$this->loop->cancel($id);
		});
		$b->write('x');
		$this->loop->run();
		sort($fired);
		self::assertSame(['first', 'second'], $fired, 'Both callbacks of one stream fire.');
		$a->read(1024);
		$a->close();
		$b->close();
	}

	// =========================================================================
	// Signals and fork
	// =========================================================================

	public function testOnSignalRunsTheCallbackFromTheLoop(): void
	{
		if (!function_exists('pcntl_signal') || !function_exists('posix_kill')) {
			self::markTestSkipped('pcntl and posix are required for signal callbacks.');
		}
		$prior = pcntl_signal_get_handler(SIGUSR2);
		try {
			$got = null;
			$this->loop->onSignal(SIGUSR2, function (string $id, int $signal) use (&$got) {
				$got = $signal;
				$this->loop->cancel($id);
			});
			$this->loop->delay(0.01, fn () => posix_kill((int) getmypid(), SIGUSR2));
			$this->loop->run();
			self::assertSame(SIGUSR2, $got, 'The signal callback runs with the signal number.');
		} finally {
			pcntl_signal(SIGUSR2, $prior);
		}
	}

	public function testForkWhileAFiberIsSuspended(): void
	{
		if (!TProcessHelper::isForkable()) {
			self::markTestSkipped('pcntl forking is not available.');
		}
		$fiber = new \Fiber(function (): string {
			$suspension = $this->loop->suspension();
			$this->loop->delay(0.05, fn () => $suspension->resume('parent'));
			return $suspension->suspend();
		});
		$fiber->start();
		self::assertTrue($fiber->isSuspended());

		$fork = TProcessFork::fork(function (): int {
			$child = $this->createChildLoop();
			if ($child->getIdentifiers() !== []) {
				return 2;   // the child inherited the parent's callbacks
			}
			$ran = false;
			$child->delay(0.01, function () use (&$ran) {
				$ran = true;
			});
			$child->run();
			return $ran ? 0 : 1;
		});
		self::assertSame(0, $fork->wait(), 'The child runs a loop of its own with no inherited callback.');

		$this->loop->run();
		self::assertTrue($fiber->isTerminated(), 'The parent fiber resumes after the fork.');
		self::assertSame('parent', $fiber->getReturn());
	}
}
