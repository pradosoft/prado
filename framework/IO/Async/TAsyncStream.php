<?php

/**
 * TAsyncStream class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Async;

use Prado\Exceptions\TCancelledException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\IO\IResource;
use Prado\IO\Stream\TStreamDecorator;
use Prado\IO\TResource;
use Psr\Http\Message\StreamInterface;

/**
 * TAsyncStream class.
 *
 * TAsyncStream decorates a stream that owns a PHP stream resource, such as a
 * {@see \Prado\IO\TStream} or a {@see \Prado\IO\Socket\TSocketStream}, with reads and writes
 * that wait for readiness on the {@see TAsync::getLoop() loop} instead of blocking.  The
 * inner stream is set non-blocking.  {@see read()} returns bytes, or `''` only at end of
 * stream; while no bytes are ready the current fiber suspends until the stream is readable.
 * {@see write()} writes the whole string, suspending while the socket buffer is full.  This
 * removes the ambiguity of a non-blocking read that returns `''` for both "no data yet" and
 * "end of stream".
 *
 * A {@see TCancellationToken} passed to a read or write ends the wait with its
 * {@see TCancelledException}.  {@see waitReadable()} and {@see waitWritable()} are the bare
 * waits for code that performs its own I/O.
 *
 * ```php
 * $stream = new TAsyncStream(TSocketStream::connect('tcp://example.com:80'));
 * $stream->write("GET / HTTP/1.0\r\nHost: example.com\r\n\r\n");
 * $response = $stream->getContents();   // reads until end of stream, yielding meanwhile
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TAsyncStream extends TStreamDecorator
{
	/**
	 * Decorates a stream and sets a {@see TResource} that is open non-blocking.
	 * @param ?StreamInterface $stream The inner stream.
	 */
	public function __construct(?StreamInterface $stream = null)
	{
		parent::__construct($stream);
		if ($stream instanceof TResource && $stream->isOpen()) {
			$stream->setBlocking(false);
		}
	}

	/**
	 * Reads up to $length bytes, waiting for readability while none are ready.
	 * @param int $length The maximum number of bytes to read.
	 * @param ?TCancellationToken $token A token whose cancellation ends the wait.
	 * @throws TCancelledException When the token is cancelled while waiting.
	 * @throws TInvalidOperationException When the inner stream has no open resource to wait on.
	 * @throws \RuntimeException When the inner stream is not readable or the read fails.
	 * @return string The bytes read, or `''` at end of stream.
	 */
	public function read(int $length, ?TCancellationToken $token = null): string
	{
		if ($length <= 0) {
			return $this->getStream()->read($length);
		}
		while (true) {
			$data = $this->getStream()->read($length);
			if ($data !== '' || $this->getStream()->eof()) {
				return $data;
			}
			$this->waitReadable($token);
		}
	}

	/**
	 * Writes the whole string, waiting for writability while the buffer is full.
	 * @param string $string The bytes to write.
	 * @param ?TCancellationToken $token A token whose cancellation ends the wait.
	 * @throws TCancelledException When the token is cancelled while waiting.
	 * @throws TInvalidOperationException When the inner stream has no open resource to wait on.
	 * @throws \RuntimeException When the inner stream is not writable or the write fails.
	 * @return int The number of bytes written, the length of the string.
	 */
	public function write(string $string, ?TCancellationToken $token = null): int
	{
		$total = strlen($string);
		$offset = 0;
		while ($offset < $total) {
			$offset += $this->getStream()->write($offset === 0 ? $string : substr($string, $offset));
			if ($offset < $total) {
				$this->waitWritable($token);
			}
		}
		return $total;
	}

	/**
	 * Suspends the current fiber until the stream is readable.
	 * @param ?TCancellationToken $token A token whose cancellation ends the wait.
	 * @throws TCancelledException When the token is cancelled while waiting.
	 * @throws TInvalidOperationException When the inner stream has no open resource to wait on.
	 */
	public function waitReadable(?TCancellationToken $token = null): void
	{
		$this->awaitReadiness(true, $token);
	}

	/**
	 * Suspends the current fiber until the stream is writable.
	 * @param ?TCancellationToken $token A token whose cancellation ends the wait.
	 * @throws TCancelledException When the token is cancelled while waiting.
	 * @throws TInvalidOperationException When the inner stream has no open resource to wait on.
	 */
	public function waitWritable(?TCancellationToken $token = null): void
	{
		$this->awaitReadiness(false, $token);
	}

	/**
	 * Registers a one-shot readiness callback on the loop and suspends until it fires or the
	 * token is cancelled.
	 * @param bool $readable Whether to wait for readability (true) or writability (false).
	 * @param ?TCancellationToken $token A token whose cancellation ends the wait.
	 * @throws TCancelledException When the token is cancelled while waiting.
	 * @throws TInvalidOperationException When the inner stream has no open resource to wait on.
	 */
	private function awaitReadiness(bool $readable, ?TCancellationToken $token): void
	{
		$token?->throwIfCancelled();
		$stream = $this->getStream();
		if (!($stream instanceof IResource) || !is_resource($stream->getResource())) {
			throw new TInvalidOperationException('asyncstream_no_resource');
		}
		$loop = TAsync::getLoop();
		$suspension = $loop->suspension();
		$settled = false;
		$ready = static function (string $id) use (&$settled, $suspension, $loop): void {
			$loop->cancel($id);
			if (!$settled) {
				$settled = true;
				$suspension->resume();
			}
		};
		$id = $readable ? $loop->onReadable($stream, $ready) : $loop->onWritable($stream, $ready);
		$subscription = $token?->subscribe(static function (TCancelledException $reason) use (&$settled, $suspension, $loop, $id): void {
			if (!$settled) {
				$settled = true;
				$loop->cancel($id);
				$suspension->throw($reason);
			}
		});
		try {
			$suspension->suspend();
		} finally {
			$loop->cancel($id);
			if ($subscription !== null) {
				$token->unsubscribe($subscription);
			}
		}
	}
}
