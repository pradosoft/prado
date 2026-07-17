<?php

use Prado\IO\Process\TProcessFork;
use Prado\Util\Helpers\TProcessHelper;
use Prado\Util\TSignalsDispatcher;

/** A worker subclass whose run() returns a fixed code, exercising the run() seam without a callable. */
class FixedCodeForkWorker extends TProcessFork
{
	protected function run(): int
	{
		return 5;
	}
}

/** A worker that reaps asynchronously by default, exercising the DEFAULT_ASYNC constant. */
class AsyncByDefaultProcessFork extends TProcessFork
{
	protected const DEFAULT_ASYNC = true;
}

class TProcessForkTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
		if (!TProcessHelper::isForkable()) {
			$this->markTestSkipped('pcntl forking is not available.');
		}
	}

	public function testForkRunsBodyAndExitsZero(): void
	{
		$fork = TProcessFork::fork(fn () => 0);
		self::assertTrue($fork->getIsParent(), 'The parent holds the handle.');
		self::assertFalse($fork->getIsChild());
		self::assertGreaterThan(0, $fork->getProcessId());
		self::assertSame(TProcessFork::STATE_RUNNING, $fork->getState());
		self::assertNull($fork->getChannel(), 'No channel is opened unless asked for.');
		$fork->exit(9);   // a no-op on the parent; the wait below proves the parent survived it
		self::assertSame(0, $fork->wait());
		self::assertSame(TProcessFork::STATE_EXITED, $fork->getState());
		self::assertFalse($fork->getIsRunning());
	}

	public function testForkBodyExitCodePropagates(): void
	{
		self::assertSame(3, TProcessFork::fork(fn () => 3)->wait());
	}

	public function testForkBodyExceptionExitsWithSoftwareCode(): void
	{
		$fork = TProcessFork::fork(function () {
			throw new \RuntimeException('boom');
		});
		self::assertSame(TProcessFork::EXIT_EXCEPTION, $fork->wait(), 'An uncaught child exception becomes a non-zero exit.');
	}

	public function testWorkerSubclassRunIsInvoked(): void
	{
		self::assertSame(5, FixedCodeForkWorker::fork()->wait(), 'An overridden run() defines the worker body.');
	}

	public function testChannelRoundTripsFromChildToParent(): void
	{
		$fork = TProcessFork::fork(function (TProcessFork $self) {
			$self->getChannel()->write('pong');
			return 0;
		}, channel: true);

		self::assertNotNull($fork->getChannel(), 'The parent holds its end of the channel.');
		$fork->getChannel()->setBlocking(false);
		$message = '';
		$deadline = microtime(true) + 3.0;
		while ($message !== 'pong' && microtime(true) < $deadline) {
			$chunk = (string) $fork->getChannel()->read(16);
			$message .= $chunk;
			if ($chunk === '') {
				usleep(10000);
			}
		}
		self::assertSame('pong', $message, 'The child wrote through the channel to the parent.');
		$fork->wait();
	}

	public function testStartContainerModeChildExitsExplicitly(): void
	{
		$fork = TProcessFork::start();
		if ($fork->getIsChild()) {
			$fork->exit(7);   // container mode: the child controls its own exit; never returns
		}
		self::assertTrue($fork->getIsParent());
		self::assertSame(7, $fork->wait());
	}

	public function testPollIsNullWhileRunningThenReturnsTheExitCode(): void
	{
		$fork = TProcessFork::fork(function () {
			usleep(200000);   // 200ms, so the parent observes it running first
			return 0;
		});
		self::assertNull($fork->poll(), 'poll() does not block and reports null while the child runs.');
		self::assertTrue($fork->getIsRunning());
		self::assertSame(0, $fork->wait());
		self::assertSame(0, $fork->poll(), 'poll() returns the recorded code after exit.');
	}

	public function testOnExitFiresOnceWithTheExitCode(): void
	{
		$fork = TProcessFork::fork(fn () => 2);
		$observed = [];
		$fork->attachEventHandler('onExit', function ($sender, $code) use (&$observed) {
			$observed[] = $code;
		});
		$fork->wait();
		$fork->poll();   // a second reap must not raise onExit again
		self::assertSame([2], $observed, 'onExit fires once, carrying the exit code.');
	}

	public function testKillStopsALongRunningChild(): void
	{
		$fork = TProcessFork::fork(function () {
			while (true) {
				usleep(50000);
			}
		});
		self::assertTrue($fork->getIsRunning());
		self::assertTrue($fork->kill());
		$fork->wait();
		self::assertSame(TProcessFork::STATE_EXITED, $fork->getState());
		self::assertFalse($fork->getIsRunning());
	}

	public function testSignalsAreNoOpOnAReapedChild(): void
	{
		$fork = TProcessFork::fork(fn () => 0);
		$fork->wait();
		self::assertFalse($fork->terminate(), 'A reaped child cannot be signaled.');
		self::assertFalse($fork->kill());
	}

	public function testAsyncIsOffByDefault(): void
	{
		$fork = TProcessFork::fork(fn () => 0);
		self::assertFalse($fork->getAsync());
		$fork->wait();
	}

	public function testChannelAwareWaitDrainsAndDoesNotDeadlock(): void
	{
		// A channel-backed fork must drain the channel while waiting; a non-draining wait would
		// deadlock when the child writes more than a pipe buffer holds.
		$fork = TProcessFork::fork(function (TProcessFork $self) {
			$self->getChannel()->write(str_repeat('x', 512 * 1024));   // 512 KiB > pipe buffer
			return 0;
		}, channel: true);
		self::assertSame(0, $fork->wait());
		self::assertSame(TProcessFork::STATE_EXITED, $fork->getState());
	}

	public function testEarlyChannelCloseDoesNotBlockAnExternalReactor(): void
	{
		$reactor = new \Prado\IO\Socket\TSocketReactor();
		$fork = TProcessFork::start(channel: true);
		if ($fork->getIsChild()) {
			$fork->getChannel()->close();   // close the channel early, then keep working
			usleep(800000);
			exit(6);
		}
		$fork->register($reactor);

		// Drain to the end of stream: the channel unregisters, but the child still works.
		$deadline = microtime(true) + 3.0;
		while ($reactor->isRegistered($fork->getChannel()) && microtime(true) < $deadline) {
			$reactor->tick(0.05);
		}
		self::assertNull($fork->getExitCode(), 'The end of stream does not block on the still-working child.');
		self::assertTrue($fork->getIsRunning(), 'The child keeps working after closing its channel.');

		// The poll timer captures the exit without any blocking waitpid in the loop.
		$deadline = microtime(true) + 5.0;
		while ($fork->getState() !== TProcessFork::STATE_EXITED && microtime(true) < $deadline) {
			$reactor->tick(0.05);
		}
		self::assertSame(6, $fork->getExitCode(), 'The reactor timer reaps the exit after the early close.');
	}

	public function testSynchronousReapWhileAsyncDetachesTheDispatcherHandler(): void
	{
		$created = TSignalsDispatcher::singleton(false) === null;
		$dispatcher = TSignalsDispatcher::singleton();
		$priorAsync = TSignalsDispatcher::setAsyncSignals(false);   // off, so wait() reaps deterministically
		try {
			$fork = TProcessFork::fork(fn () => 0);
			$fork->setAsync(true);
			$pid = $fork->getProcessId();
			self::assertTrue($dispatcher->hasPidHandler($pid), 'Async registers a PID handler.');

			self::assertSame(0, $fork->wait(), 'A synchronous reap while async is on.');

			self::assertFalse($dispatcher->hasPidHandler($pid), 'captureExit detaches the handler however the child is reaped.');
			self::assertFalse($fork->getAsync(), 'Async is cleared once the child is reaped.');
		} finally {
			$created ? $dispatcher->detach() : TSignalsDispatcher::setAsyncSignals($priorAsync);
		}
	}

	public function testDefaultAsyncConstantEnablesAsyncOnFork(): void
	{
		$created = TSignalsDispatcher::singleton(false) === null;
		$dispatcher = TSignalsDispatcher::singleton();
		$priorAsync = TSignalsDispatcher::setAsyncSignals(false);
		try {
			$fork = AsyncByDefaultProcessFork::fork(fn () => 0);
			self::assertTrue($fork->getAsync(), 'DEFAULT_ASYNC enables async reaping on the parent after fork.');
			self::assertTrue($dispatcher->hasPidHandler($fork->getProcessId()), 'A PID handler is registered on fork.');

			$fork->wait();
			self::assertFalse($fork->getAsync());
			self::assertFalse($dispatcher->hasPidHandler($fork->getProcessId()));
		} finally {
			$created ? $dispatcher->detach() : TSignalsDispatcher::setAsyncSignals($priorAsync);
		}
	}
}
