<?php

namespace Prado\Test\Unit\IO\Async;

use Prado\Exceptions\TCancelledException;
use Prado\IO\Async\TAsync;
use Prado\IO\Async\TCancellationToken;
use Prado\IO\Async\TDeferred;
use Prado\IO\Async\TFuture;

class TFutureTest extends \PHPUnit\Framework\TestCase
{
	public function testCompleteAndErrorFactories(): void
	{
		$future = TFuture::complete(42);
		self::assertTrue($future->isComplete());
		self::assertTrue($future->isResolved());
		self::assertFalse($future->isRejected());
		self::assertSame(42, $future->await(), 'A complete future returns at once.');

		$rejected = TFuture::error(new \RuntimeException('no'));
		self::assertTrue($rejected->isRejected());
		self::assertFalse($rejected->isResolved());
		$this->expectException(\RuntimeException::class);
		$rejected->await();
	}

	public function testExecutorSettlesTheFutureAndAThrowingExecutorRejects(): void
	{
		$future = new TFuture(fn ($resolve) => $resolve('x'));
		self::assertSame('x', $future->await());

		$future = new TFuture(fn () => throw new \LogicException('executor'));
		self::assertTrue($future->isRejected());
		$this->expectException(\LogicException::class);
		$future->await();
	}

	public function testAwaitFromMainRunsTheLoopUntilResolved(): void
	{
		$deferred = new TDeferred();
		TAsync::getLoop()->delay(0.01, fn () => $deferred->resolve('later'));
		self::assertFalse($deferred->getFuture()->isComplete());
		self::assertSame('later', $deferred->getFuture()->await(), 'await() from main pumps the loop.');
	}

	public function testAwaitFromAWorkerFiberYieldsToTheLoop(): void
	{
		$deferred = new TDeferred();
		$fiber = new \Fiber(fn () => $deferred->getFuture()->await());
		$fiber->start();
		self::assertTrue($fiber->isSuspended(), 'The worker fiber yields while the future is pending.');
		TAsync::getLoop()->delay(0.01, fn () => $deferred->resolve('fiber'));
		TAsync::getLoop()->run();
		self::assertTrue($fiber->isTerminated());
		self::assertSame('fiber', $fiber->getReturn());
	}

	public function testRejectionThrowsFromAwait(): void
	{
		$deferred = new TDeferred();
		TAsync::getLoop()->delay(0, fn () => $deferred->reject(new \DomainException('bad')));
		$this->expectException(\DomainException::class);
		$deferred->getFuture()->await();
	}

	public function testOnCompleteRunsInOrderAndAtOnceWhenComplete(): void
	{
		$deferred = new TDeferred();
		$order = [];
		$deferred->getFuture()->onComplete(function ($error, $value) use (&$order) {
			$order[] = 'a:' . $value;
		})->onComplete(function ($error, $value) use (&$order) {
			$order[] = 'b:' . $value;
		});
		$deferred->resolve(1);
		$deferred->getFuture()->onComplete(function ($error, $value) use (&$order) {
			$order[] = 'c:' . $value;
		});
		self::assertSame(['a:1', 'b:1', 'c:1'], $order);
	}

	public function testMapCatchAndFinallyDeriveFutures(): void
	{
		$doubled = TFuture::complete(2)->map(fn ($v) => $v * 2);
		self::assertSame(4, $doubled->await());

		$failed = TFuture::complete(1)->map(fn () => throw new \RuntimeException('map'));
		self::assertTrue($failed->isRejected(), 'A throwing mapper rejects the derived future.');

		$recovered = TFuture::error(new \RuntimeException('x'))->catch(fn (\Throwable $e) => 'recovered:' . $e->getMessage());
		self::assertSame('recovered:x', $recovered->await());

		$passed = TFuture::complete('v')->catch(fn () => 'unused');
		self::assertSame('v', $passed->await(), 'catch() passes a value through.');

		$ran = false;
		$final = TFuture::complete('f')->finally(function () use (&$ran) {
			$ran = true;
		});
		self::assertSame('f', $final->await());
		self::assertTrue($ran);

		$rejectedFinal = TFuture::error(new \LogicException('keep'))->finally(fn () => null);
		self::assertTrue($rejectedFinal->isRejected(), 'finally() passes a rejection through.');
	}

	public function testAwaitWithACancelledTokenThrowsAndLeavesTheFuturePending(): void
	{
		$deferred = new TDeferred();
		$token = new TCancellationToken();
		TAsync::getLoop()->delay(0.01, fn () => $token->cancel());
		try {
			$deferred->getFuture()->await($token);
			self::fail('The cancellation ends the wait.');
		} catch (TCancelledException $e) {
			self::assertFalse($deferred->isComplete(), 'The future itself stays pending.');
		}
		$deferred->resolve('late');
		self::assertSame('late', $deferred->getFuture()->await(), 'A later completion is still delivered to a new await.');
	}

	public function testAwaitWithAnAlreadyCancelledTokenThrowsAtOnce(): void
	{
		$token = new TCancellationToken();
		$token->cancel();
		$this->expectException(TCancelledException::class);
		(new TDeferred())->getFuture()->await($token);
	}
}
