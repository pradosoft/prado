<?php

namespace Prado\Test\Unit\IO\Async;

use Prado\Exceptions\TInvalidOperationException;
use Prado\IO\Async\TDeferred;
use Prado\IO\Async\TFuture;

class TDeferredTest extends \PHPUnit\Framework\TestCase
{
	public function testResolveCompletesTheFuture(): void
	{
		$deferred = new TDeferred();
		self::assertInstanceOf(TFuture::class, $deferred->getFuture());
		self::assertSame($deferred->getFuture(), $deferred->Future, 'Future is a component property.');
		self::assertFalse($deferred->isComplete());
		$deferred->resolve('v');
		self::assertTrue($deferred->isComplete());
		self::assertSame('v', $deferred->getFuture()->await());
	}

	public function testRejectCompletesTheFutureWithTheException(): void
	{
		$deferred = new TDeferred();
		$deferred->reject(new \RuntimeException('r'));
		self::assertTrue($deferred->getFuture()->isRejected());
		$this->expectException(\RuntimeException::class);
		$deferred->getFuture()->await();
	}

	public function testASecondCompletionThrows(): void
	{
		$deferred = new TDeferred();
		$deferred->resolve(1);
		$this->expectException(TInvalidOperationException::class);
		$deferred->reject(new \RuntimeException('again'));
	}
}
