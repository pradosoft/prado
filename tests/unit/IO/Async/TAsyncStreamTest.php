<?php

namespace Prado\Test\Unit\IO\Async;

use Prado\Exceptions\TCancelledException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\IO\Async\TAsync;
use Prado\IO\Async\TAsyncStream;
use Prado\IO\Async\TCancellationToken;
use Prado\IO\Socket\TSocketStream;
use Prado\IO\Stream\TNoSeekStream;
use Prado\IO\TStream;

class TAsyncStreamTest extends \PHPUnit\Framework\TestCase
{
	public function testConstructorSetsTheInnerStreamNonBlockingAndForwardsCapabilities(): void
	{
		[$a, $b] = TSocketStream::pair();
		self::assertTrue($a->getBlocking());
		$stream = new TAsyncStream($a);
		self::assertFalse($a->getBlocking(), 'The inner stream is set non-blocking.');
		self::assertTrue($stream->isReadable());
		self::assertTrue($stream->isWritable());
		self::assertFalse($stream->isSeekable());
		self::assertSame($a, $stream->getStream());
		$a->close();
		$b->close();
	}

	public function testReadWaitsForDataAndReturnsEmptyOnlyAtEndOfStream(): void
	{
		[$a, $b] = TSocketStream::pair();
		$stream = new TAsyncStream($a);
		TAsync::getLoop()->delay(0.01, fn () => $b->write('hello'));
		self::assertSame('hello', $stream->read(1024), 'read() waits for the bytes instead of returning an empty string.');
		self::assertSame([], TAsync::getLoop()->getIdentifiers(), 'The readiness callback is cancelled after the read.');

		$b->close();
		self::assertSame('', $stream->read(1024), 'End of stream returns an empty string.');
		self::assertTrue($stream->eof());
		$a->close();
	}

	public function testReadFromAWorkerFiberYieldsUntilDataArrives(): void
	{
		[$a, $b] = TSocketStream::pair();
		$stream = new TAsyncStream($a);
		$future = TAsync::run(fn () => $stream->read(16));
		self::assertFalse($future->isComplete(), 'The fiber is suspended on readability.');
		$b->write('later');
		self::assertSame('later', $future->await());
		$a->close();
		$b->close();
	}

	public function testWriteCompletesALargePayloadWhileAFiberDrainsThePeer(): void
	{
		[$a, $b] = TSocketStream::pair();
		$stream = new TAsyncStream($a);
		$payload = str_repeat('x', 1 << 20);
		$length = strlen($payload);
		$reader = TAsync::run(function () use ($b, $length): int {
			$peer = new TAsyncStream($b);
			$received = 0;
			while ($received < $length) {
				$chunk = $peer->read(65536);
				if ($chunk === '') {
					break;
				}
				$received += strlen($chunk);
			}
			return $received;
		});
		self::assertSame($length, $stream->write($payload), 'write() returns once every byte is written.');
		self::assertSame($length, $reader->await(), 'The peer received the whole payload.');
		$a->close();
		$b->close();
	}

	public function testACancelledTokenEndsAPendingRead(): void
	{
		[$a, $b] = TSocketStream::pair();
		$stream = new TAsyncStream($a);
		$token = new TCancellationToken(0.01);
		try {
			$stream->read(10, $token);
			self::fail('The timeout cancels the read.');
		} catch (TCancelledException $e) {
			self::assertSame('async_timeout', $e->getErrorCode());
		}
		self::assertSame([], TAsync::getLoop()->getIdentifiers(), 'A cancelled wait leaves no callback behind.');
		$a->close();
		$b->close();
	}

	public function testAnAlreadyCancelledTokenThrowsBeforeWaiting(): void
	{
		[$a, $b] = TSocketStream::pair();
		$stream = new TAsyncStream($a);
		$token = new TCancellationToken();
		$token->cancel();
		$this->expectException(TCancelledException::class);
		try {
			$stream->waitReadable($token);
		} finally {
			$a->close();
			$b->close();
		}
	}

	public function testWaitingWithoutAResourceThrows(): void
	{
		$stream = new TAsyncStream(new TNoSeekStream(TStream::fromString('x')));
		self::assertSame('x', $stream->read(1), 'Ready bytes are returned without a wait.');
		$this->expectException(TInvalidOperationException::class);
		$stream->waitReadable();
	}

	public function testGetContentsReadsToEndOfStream(): void
	{
		[$a, $b] = TSocketStream::pair();
		$stream = new TAsyncStream($a);
		TAsync::getLoop()->delay(0.005, function () use ($b) {
			$b->write('part one ');
		});
		TAsync::getLoop()->delay(0.01, function () use ($b) {
			$b->write('part two');
			$b->close();
		});
		self::assertSame('part one part two', $stream->getContents());
		$a->close();
	}
}
