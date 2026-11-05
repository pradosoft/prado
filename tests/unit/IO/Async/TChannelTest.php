<?php

namespace Prado\Test\Unit\IO\Async;

use Prado\Exceptions\TCancelledException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\IO\Async\TAsync;
use Prado\IO\Async\TCancellationToken;
use Prado\IO\Async\TChannel;

class TChannelTest extends \PHPUnit\Framework\TestCase
{
	protected function tearDown(): void
	{
		TAsync::setLoop(null);
	}

	public function testCapacity(): void
	{
		self::assertNull((new TChannel())->getCapacity());
		self::assertSame(0, (new TChannel(0))->getCapacity());
		self::assertSame(3, (new TChannel(3))->getCapacity());
		$this->expectException(TInvalidDataValueException::class);
		new TChannel(-1);
	}

	public function testBufferedValuesNeedNoWait(): void
	{
		$channel = new TChannel();
		self::assertTrue($channel->isEmpty());
		$channel->send('a');
		$channel->send('b');
		self::assertCount(2, $channel);
		self::assertFalse($channel->isEmpty());
		self::assertSame('a', $channel->receive());
		self::assertSame('b', $channel->receive());
		self::assertTrue($channel->isEmpty());
		self::assertFalse($channel->isClosed());
	}

	public function testTrySendAndTryReceiveDoNotWait(): void
	{
		$channel = new TChannel(1);
		self::assertFalse($channel->tryReceive($value));
		self::assertNull($value);
		self::assertTrue($channel->trySend('a'));
		self::assertFalse($channel->trySend('b'), 'The buffer is full');
		self::assertTrue($channel->tryReceive($value));
		self::assertSame('a', $value);
		self::assertTrue($channel->trySend('b'));
	}

	public function testReceiveWaitsForASend(): void
	{
		$channel = new TChannel();
		$receiver = TAsync::run(fn () => $channel->receive());
		self::assertFalse($receiver->isComplete(), 'The receiver waits');
		$channel->send('x');
		self::assertSame('x', $receiver->await());

		TAsync::run(function () use ($channel): void {
			TAsync::sleep(0.01);
			$channel->send('y');
		});
		self::assertSame('y', $channel->receive(), 'The main fiber runs the loop while it waits');
	}

	public function testReceiversAreServedInOrder(): void
	{
		$channel = new TChannel();
		$first = TAsync::run(fn () => $channel->receive());
		$second = TAsync::run(fn () => $channel->receive());
		$channel->send('a');
		$channel->send('b');
		self::assertSame('a', $first->await());
		self::assertSame('b', $second->await());
	}

	public function testSendWaitsWhileTheBufferIsFull(): void
	{
		$channel = new TChannel(1);
		$channel->send('a');
		$sender = TAsync::run(function () use ($channel): string {
			$channel->send('b');
			return 'sent';
		});
		self::assertFalse($sender->isComplete(), 'The sender waits for room');
		self::assertFalse($channel->isEmpty());
		self::assertSame('a', $channel->receive());
		self::assertSame('sent', $sender->await(), 'The receive admits the waiting sender');
		self::assertCount(1, $channel);
		self::assertSame('b', $channel->receive());
	}

	public function testARendezvousChannelHandsTheValueToTheReceiver(): void
	{
		$channel = new TChannel(0);
		self::assertFalse($channel->trySend('r'));
		$sender = TAsync::run(function () use ($channel): string {
			$channel->send('r');
			return 'sent';
		});
		self::assertFalse($sender->isComplete());
		self::assertFalse($channel->isEmpty(), 'A waiting sender counts as a value');
		self::assertCount(0, $channel);
		self::assertSame('r', $channel->receive());
		self::assertSame('sent', $sender->await());
	}

	public function testCloseDrainsTheBufferAndReleasesWaiters(): void
	{
		$channel = new TChannel();
		$channel->send('a');
		$empty = new TChannel();
		$receiver = TAsync::run(fn () => $empty->receive());
		self::assertFalse($receiver->isComplete());
		$full = new TChannel(0);
		$sender = TAsync::run(fn () => $full->send('b'));

		$channel->close();
		$empty->close();
		$full->close();
		self::assertTrue($channel->isClosed());
		try {
			$receiver->await();
			self::fail('A waiting receiver is released with an exception');
		} catch (TInvalidOperationException $e) {
		}
		try {
			$sender->await();
			self::fail('A waiting sender is released with an exception');
		} catch (TInvalidOperationException $e) {
		}
		self::assertTrue($receiver->isRejected());
		self::assertTrue($sender->isRejected());

		self::assertSame('a', $channel->receive(), 'Buffered values remain receivable');
		try {
			$channel->receive();
			self::fail('A drained closed channel throws');
		} catch (TInvalidOperationException $e) {
		}
		$this->expectException(TInvalidOperationException::class);
		$channel->send('c');
	}

	public function testIterationEndsWhenTheChannelIsClosedAndDrained(): void
	{
		$channel = new TChannel(2);
		TAsync::run(function () use ($channel): void {
			foreach ([1, 2, 3, 4, 5] as $value) {
				$channel->send($value);
				TAsync::sleep(0.001);
			}
			$channel->close();
		});
		self::assertSame([1, 2, 3, 4, 5], iterator_to_array($channel, false));
	}

	public function testACancelledTokenEndsTheWaitAndRemovesTheWaiter(): void
	{
		$channel = new TChannel();
		try {
			$channel->receive(new TCancellationToken(0.02));
			self::fail('The wait ends with the cancellation');
		} catch (TCancelledException $e) {
		}
		$channel->send('a');
		self::assertSame('a', $channel->receive(), 'The cancelled receiver did not take the value');

		$full = new TChannel(0);
		try {
			$full->send('b', new TCancellationToken(0.02));
			self::fail('The wait ends with the cancellation');
		} catch (TCancelledException $e) {
		}
		self::assertTrue($full->isEmpty(), 'The cancelled sender left no value');

		$cancelled = new TCancellationToken();
		$cancelled->cancel();
		$this->expectException(TCancelledException::class);
		$channel->receive($cancelled);
	}
}
