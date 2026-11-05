<?php

namespace Prado\Test\Unit\IO\Async;

use Prado\Exceptions\TCancelledException;
use Prado\IO\Async\TAsync;
use Prado\IO\Async\TCancellationToken;

class TCancellationTokenTest extends \PHPUnit\Framework\TestCase
{
	public function testCancelIsIdempotentAndRaisesOnCancelOnce(): void
	{
		$token = new TCancellationToken();
		self::assertFalse($token->isCancelled());
		self::assertNull($token->getReason());
		self::assertNull($token->getTimeout());
		$token->throwIfCancelled();   // a live token does not throw

		$raised = [];
		$token->attachEventHandler('onCancel', function ($sender, $param) use (&$raised) {
			$raised[] = $param;
		});
		$token->cancel();
		$token->cancel();
		self::assertCount(1, $raised, 'onCancel is raised once.');
		self::assertInstanceOf(TCancelledException::class, $raised[0]);
		self::assertSame($raised[0], $token->getReason());
		$this->expectException(TCancelledException::class);
		$token->throwIfCancelled();
	}

	public function testCancelReasonsAreCarried(): void
	{
		$token = new TCancellationToken();
		$cause = new \RuntimeException('cause');
		$token->cancel($cause);
		self::assertSame($cause, $token->getReason()->getPrevious(), 'A plain exception becomes the previous of the reason.');

		$token = new TCancellationToken();
		$reason = new TCancelledException('async_cancelled');
		$token->cancel($reason);
		self::assertSame($reason, $token->getReason(), 'A TCancelledException is the reason as is.');
	}

	public function testSubscribersRunOnCancelAndAtOnceWhenAlreadyCancelled(): void
	{
		$token = new TCancellationToken();
		$calls = [];
		$kept = $token->subscribe(function () use (&$calls) {
			$calls[] = 'kept';
		});
		$removed = $token->subscribe(function () use (&$calls) {
			$calls[] = 'removed';
		});
		$token->unsubscribe($removed);
		$token->unsubscribe('no-such-id');
		$token->cancel();
		self::assertSame(['kept'], $calls);

		$token->subscribe(function () use (&$calls) {
			$calls[] = 'late';
		});
		self::assertSame(['kept', 'late'], $calls, 'A subscriber added after cancellation runs at once.');
		self::assertNotSame('', $kept);
	}

	public function testTimeoutCancelsThroughTheLoop(): void
	{
		$token = new TCancellationToken(0.01);
		self::assertSame(0.01, $token->getTimeout());
		self::assertFalse($token->isCancelled());
		$cancelled = false;
		$token->subscribe(function () use (&$cancelled) {
			$cancelled = true;
		});
		TAsync::getLoop()->run();
		self::assertTrue($cancelled, 'The timeout cancels the token from the loop.');
		self::assertSame('async_timeout', $token->getReason()->getErrorCode());
	}

	public function testAnEarlyCancelRemovesTheTimeoutCallback(): void
	{
		$before = TAsync::getLoop()->getIdentifiers();
		$token = new TCancellationToken(10);
		self::assertCount(count($before) + 1, TAsync::getLoop()->getIdentifiers());
		$token->cancel();
		self::assertSame($before, TAsync::getLoop()->getIdentifiers(), 'cancel() removes the timeout from the loop.');
	}

	public function testReleasingATokenRemovesItsTimeoutCallback(): void
	{
		$before = TAsync::getLoop()->getIdentifiers();
		$token = new TCancellationToken(10);
		self::assertCount(count($before) + 1, TAsync::getLoop()->getIdentifiers());
		unset($token);
		gc_collect_cycles();
		self::assertSame($before, TAsync::getLoop()->getIdentifiers(), 'The destructor removes the timeout.');
	}

	public function testALinkedTokenCancelsWithItsParent(): void
	{
		$parent = new TCancellationToken();
		$child = new TCancellationToken(null, $parent);
		self::assertFalse($child->isCancelled());
		$parent->cancel(new \RuntimeException('parent'));
		self::assertTrue($child->isCancelled());
		self::assertSame($parent->getReason(), $child->getReason(), 'The parent reason propagates.');

		$cancelledParent = new TCancellationToken();
		$cancelledParent->cancel();
		$late = new TCancellationToken(null, $cancelledParent);
		self::assertTrue($late->isCancelled(), 'Linking to a cancelled parent cancels at once.');
	}

	public function testCancellingAChildDoesNotCancelTheParent(): void
	{
		$parent = new TCancellationToken();
		$child = new TCancellationToken(null, $parent);
		$child->cancel();
		self::assertFalse($parent->isCancelled());
	}
}
