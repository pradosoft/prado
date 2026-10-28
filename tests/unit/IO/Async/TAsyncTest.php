<?php

namespace Prado\Test\Unit\IO\Async;

use Prado\Exceptions\TCancelledException;
use Prado\IO\Async\IEventLoop;
use Prado\IO\Async\TAsync;
use Prado\IO\Async\TDeferred;
use Prado\IO\Async\TFuture;
use Prado\IO\Async\TRevoltEventLoop;
use Prado\IO\Socket\TSocketReactor;

class TAsyncTest extends \PHPUnit\Framework\TestCase
{
	protected function tearDown(): void
	{
		TAsync::setLoop(null);
	}

	public function testLoopDefaultsToRevoltAndAcceptsAnotherLoop(): void
	{
		TAsync::setLoop(null);
		self::assertInstanceOf(TRevoltEventLoop::class, TAsync::getLoop());
		$reactor = new TSocketReactor();
		TAsync::setLoop($reactor);
		self::assertSame($reactor, TAsync::getLoop());
	}

	public function testRunReturnsTheFutureOfTheFiber(): void
	{
		$future = TAsync::run(fn (int $a, int $b) => $a + $b, 2, 3);
		self::assertInstanceOf(TFuture::class, $future);
		self::assertTrue($future->isComplete(), 'A body that never suspends completes during run().');
		self::assertSame(5, $future->await());

		$failed = TAsync::run(fn () => throw new \RuntimeException('body'));
		self::assertTrue($failed->isRejected());
		$this->expectException(\RuntimeException::class);
		$failed->await();
	}

	public function testRunFibersInterleaveThroughSleep(): void
	{
		$order = [];
		$a = TAsync::run(function () use (&$order) {
			$order[] = 'a1';
			TAsync::sleep(0.02);
			$order[] = 'a2';
			return 'a';
		});
		$b = TAsync::run(function () use (&$order) {
			$order[] = 'b1';
			TAsync::sleep(0.01);
			$order[] = 'b2';
			return 'b';
		});
		self::assertSame(['a1', 'b1'], $order, 'Both fibers ran to their first sleep.');
		self::assertSame(['a' => 'a', 'b' => 'b'], TAsync::all(['a' => $a, 'b' => $b])->await());
		self::assertSame(['a1', 'b1', 'b2', 'a2'], $order, 'The shorter sleep resumes first.');
	}

	public function testAllRejectsWithTheFirstRejectionAndResolvesEmpty(): void
	{
		self::assertSame([], TAsync::all([])->await());
		$ok = new TDeferred();
		$bad = new TDeferred();
		$all = TAsync::all([$ok->getFuture(), $bad->getFuture()]);
		$bad->reject(new \LogicException('first'));
		self::assertTrue($all->isRejected(), 'all() rejects as soon as one input rejects.');
		$ok->resolve(1);
		$this->expectException(\LogicException::class);
		$all->await();
	}

	public function testAnyResolvesWithTheFirstValueAndRejectsWhenAllReject(): void
	{
		$slow = new TDeferred();
		$fast = new TDeferred();
		$any = TAsync::any([$slow->getFuture(), $fast->getFuture()]);
		$fast->resolve('fast');
		self::assertSame('fast', $any->await());
		$slow->resolve('slow');
		self::assertSame('fast', $any->await(), 'Later values are ignored.');

		$x = new TDeferred();
		$y = new TDeferred();
		$none = TAsync::any([$x->getFuture(), $y->getFuture()]);
		$x->reject(new \RuntimeException('x'));
		self::assertFalse($none->isComplete(), 'One rejection does not settle any().');
		$y->reject(new \RuntimeException('y'));
		self::assertTrue($none->isRejected());
		try {
			$none->await();
		} catch (\RuntimeException $e) {
			self::assertSame('y', $e->getMessage(), 'The last rejection is the result.');
		}
		self::assertTrue(TAsync::any([])->isRejected(), 'An empty any() rejects.');
	}

	public function testTimeoutRejectsWhenTheDeadlinePassesFirst(): void
	{
		$deferred = new TDeferred();
		$bounded = TAsync::timeout($deferred->getFuture(), 0.01);
		try {
			$bounded->await();
			self::fail('The deadline rejects the bounded future.');
		} catch (TCancelledException $e) {
			self::assertSame('async_timeout', $e->getErrorCode());
		}
		self::assertFalse($deferred->isComplete(), 'The input stays pending.');
	}

	public function testTimeoutPassesAResultThroughAndRemovesItsTimer(): void
	{
		$before = TAsync::getLoop()->getIdentifiers();
		$deferred = new TDeferred();
		$bounded = TAsync::timeout($deferred->getFuture(), 10);
		$deferred->resolve('in time');
		self::assertSame('in time', $bounded->await());
		self::assertSame($before, TAsync::getLoop()->getIdentifiers(), 'The timer is cancelled once the input completes.');
	}

	public function testSleepFromMainPausesAndRunsOtherCallbacks(): void
	{
		$other = false;
		TAsync::getLoop()->delay(0.005, function () use (&$other) {
			$other = true;
		});
		$start = microtime(true);
		TAsync::sleep(0.02);
		self::assertGreaterThanOrEqual(0.015, microtime(true) - $start);
		self::assertTrue($other, 'A main-fiber sleep runs the loop, so other due callbacks run.');
		TAsync::sleep(0);
		TAsync::sleep(-1);
	}

	public function testSleepOnTheReactorLoop(): void
	{
		$reactor = new TSocketReactor();
		TAsync::setLoop($reactor);
		$start = microtime(true);
		TAsync::sleep(0.01);
		self::assertGreaterThanOrEqual(0.005, microtime(true) - $start);
		self::assertSame([], $reactor->getIdentifiers(), 'The sleep leaves no timer behind.');

		$future = TAsync::run(function () {
			TAsync::sleep(0.01);
			return 'reactor';
		});
		self::assertSame('reactor', $future->await(), 'await() from main drives the reactor.');
	}

	public function testSleepInsideADestructorFallsBackToBlocking(): void
	{
		$elapsed = null;
		$object = new class ($elapsed) {
			public function __construct(private ?float &$elapsed)
			{
			}

			public function __destruct()
			{
				$start = microtime(true);
				TAsync::sleep(0.01);
				$this->elapsed = microtime(true) - $start;
			}
		};
		unset($object);
		self::assertNotNull($elapsed, 'The destructor ran.');
		self::assertGreaterThanOrEqual(0.005, $elapsed, 'A sleep where no fiber switch is possible still pauses.');
	}
}
