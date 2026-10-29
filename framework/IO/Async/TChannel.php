<?php

/**
 * TChannel class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Async;

use Prado\Exceptions\TCancelledException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\TComponent;
use Revolt\EventLoop\Suspension;

/**
 * TChannel class.
 *
 * TChannel passes values from one fiber to another in order.  {@see send()} hands a value
 * to a waiting receiver, else buffers it up to the {@see getCapacity() Capacity}, else
 * suspends the sender until a receiver takes a value.  {@see receive()} returns the next
 * buffered or sent value, else suspends the receiver until one arrives.  A capacity of 0 is
 * a rendezvous: every send waits for its receive.  A null capacity never suspends a sender.
 *
 * {@see close()} ends the channel: the buffered values remain receivable, a send and a
 * receive on an empty closed channel throw, and the waiting fibers are released with that
 * exception.  Iterating the channel receives until it is closed and drained.
 *
 * ```php
 * $channel = new TChannel(16);
 * $producer = TAsync::run(function () use ($channel) {
 *     foreach ($rows as $row) {
 *         $channel->send($row);
 *     }
 *     $channel->close();
 * });
 * foreach ($channel as $row) {
 *     $this->handle($row);
 * }
 * ```
 *
 * Every wait suspends on {@see TAsync::getLoop()}; from the main fiber it runs the loop.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @implements \IteratorAggregate<int, mixed>
 */
class TChannel extends TComponent implements \IteratorAggregate, \Countable
{
	/** @var ?int The number of values the channel buffers, or null for no limit. */
	private ?int $_capacity;

	/** @var array<int, mixed> The buffered values, in send order. */
	private array $_buffer = [];

	/** @var array<int, array{0: Suspension, 1: bool}> The waiting receivers: suspension and settled flag. */
	private array $_receivers = [];

	/** @var array<int, array{0: Suspension, 1: mixed, 2: bool}> The waiting senders: suspension, value, and settled flag. */
	private array $_senders = [];

	/** @var int The next waiter id. */
	private int $_nextId = 1;

	/** @var bool Whether the channel is closed. */
	private bool $_closed = false;

	/**
	 * Creates a channel.
	 * @param ?int $capacity The number of values the channel buffers before a sender waits; 0 for a rendezvous, null for no limit.
	 * @throws TInvalidDataValueException When the capacity is negative.
	 */
	public function __construct(?int $capacity = null)
	{
		parent::__construct();
		if ($capacity !== null && $capacity < 0) {
			throw new TInvalidDataValueException('async_channel_capacity_invalid', $capacity);
		}
		$this->_capacity = $capacity;
	}

	/**
	 * @return ?int The number of values the channel buffers, or null for no limit.
	 */
	public function getCapacity(): ?int
	{
		return $this->_capacity;
	}

	/**
	 * @return bool Whether the channel is closed.
	 */
	public function isClosed(): bool
	{
		return $this->_closed;
	}

	/**
	 * @return bool Whether no value is buffered and no sender waits.
	 */
	public function isEmpty(): bool
	{
		return $this->_buffer === [] && $this->_senders === [];
	}

	/**
	 * @return int The number of buffered values.
	 */
	public function count(): int
	{
		return count($this->_buffer);
	}

	/**
	 * Sends a value: to a waiting receiver, into the buffer while it has room, otherwise after
	 * waiting for a receiver.
	 * @param mixed $value The value.
	 * @param ?TCancellationToken $token A token whose cancellation ends the wait; the value is not sent.
	 * @throws TInvalidOperationException When the channel is closed.
	 * @throws TCancelledException When the token is cancelled during the wait.
	 */
	public function send(mixed $value, ?TCancellationToken $token = null): void
	{
		if ($this->_closed) {
			throw new TInvalidOperationException('async_channel_closed');
		}
		if ($this->trySend($value)) {
			return;
		}
		$token?->throwIfCancelled();
		$id = $this->_nextId++;
		$suspension = TAsync::getLoop()->suspension();
		$this->_senders[$id] = [$suspension, $value, false];
		$this->wait($suspension, $token, function (\Throwable $reason) use ($id): void {
			unset($this->_senders[$id]);
		});
	}

	/**
	 * Sends a value without waiting.
	 * @param mixed $value The value.
	 * @throws TInvalidOperationException When the channel is closed.
	 * @return bool Whether the value was taken by a receiver or buffered; false when the buffer is full.
	 */
	public function trySend(mixed $value): bool
	{
		if ($this->_closed) {
			throw new TInvalidOperationException('async_channel_closed');
		}
		if ($this->handToReceiver($value)) {
			return true;
		}
		if ($this->_capacity === null || count($this->_buffer) < $this->_capacity) {
			$this->_buffer[] = $value;
			return true;
		}
		return false;
	}

	/**
	 * Receives the next value: from the buffer, from a waiting sender, otherwise after waiting
	 * for a send.
	 * @param ?TCancellationToken $token A token whose cancellation ends the wait.
	 * @throws TInvalidOperationException When the channel is closed and drained.
	 * @throws TCancelledException When the token is cancelled during the wait.
	 * @return mixed The value.
	 */
	public function receive(?TCancellationToken $token = null): mixed
	{
		if ($this->tryReceive($value)) {
			return $value;
		}
		if ($this->_closed) {
			throw new TInvalidOperationException('async_channel_closed');
		}
		$token?->throwIfCancelled();
		$id = $this->_nextId++;
		$suspension = TAsync::getLoop()->suspension();
		$this->_receivers[$id] = [$suspension, false];
		return $this->wait($suspension, $token, function (\Throwable $reason) use ($id): void {
			unset($this->_receivers[$id]);
		});
	}

	/**
	 * Receives the next value without waiting.
	 * @param mixed $value Set to the value when one is available.
	 * @return bool Whether a value was received.
	 */
	public function tryReceive(mixed &$value): bool
	{
		if ($this->_buffer !== []) {
			$value = array_shift($this->_buffer);
			$this->admitSender();
			return true;
		}
		foreach ($this->_senders as $id => [$suspension, $sent]) {
			unset($this->_senders[$id]);
			$value = $sent;
			$suspension->resume();
			return true;
		}
		$value = null;
		return false;
	}

	/**
	 * Closes the channel.  Buffered values stay receivable; waiting senders and receivers are
	 * released with a {@see TInvalidOperationException}.
	 */
	public function close(): void
	{
		if ($this->_closed) {
			return;
		}
		$this->_closed = true;
		$waiters = array_merge(array_column($this->_senders, 0), array_column($this->_receivers, 0));
		$this->_senders = [];
		$this->_receivers = [];
		foreach ($waiters as $suspension) {
			$suspension->throw(new TInvalidOperationException('async_channel_closed'));
		}
	}

	/**
	 * Receives values until the channel is closed and drained.
	 * @return \Generator<int, mixed> The values, in order.
	 */
	public function getIterator(): \Generator
	{
		while (true) {
			try {
				$value = $this->receive();
			} catch (TInvalidOperationException $e) {
				return;
			}
			yield $value;
		}
	}

	/**
	 * Gives a value to the longest waiting receiver.
	 * @param mixed $value The value.
	 * @return bool Whether a receiver took it.
	 */
	private function handToReceiver(mixed $value): bool
	{
		foreach ($this->_receivers as $id => [$suspension]) {
			unset($this->_receivers[$id]);
			$suspension->resume($value);
			return true;
		}
		return false;
	}

	/**
	 * Moves the longest waiting sender's value into the buffer once room is available.
	 */
	private function admitSender(): void
	{
		if ($this->_capacity !== null && count($this->_buffer) >= $this->_capacity) {
			return;
		}
		foreach ($this->_senders as $id => [$suspension, $value]) {
			unset($this->_senders[$id]);
			$this->_buffer[] = $value;
			$suspension->resume();
			return;
		}
	}

	/**
	 * Suspends on the loop until resumed by the other side, the token, or {@see close()}.
	 * @param Suspension $suspension The current fiber's suspension.
	 * @param ?TCancellationToken $token The token whose cancellation ends the wait.
	 * @param \Closure $forget Removes this waiter from its list on cancellation.
	 * @return mixed The value the wait was resumed with.
	 */
	private function wait(Suspension $suspension, ?TCancellationToken $token, \Closure $forget): mixed
	{
		$subscription = $token?->subscribe(static function (TCancelledException $reason) use ($suspension, $forget): void {
			$forget($reason);
			$suspension->throw($reason);
		});
		try {
			return $suspension->suspend();
		} finally {
			if ($subscription !== null) {
				$token->unsubscribe($subscription);
			}
		}
	}
}
