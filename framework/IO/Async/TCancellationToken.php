<?php

/**
 * TCancellationToken class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Async;

use Prado\Exceptions\TCancelledException;
use Prado\TComponent;

/**
 * TCancellationToken class.
 *
 * TCancellationToken carries a cooperative cancellation request to the code that waits.  A
 * token is {@see cancel() cancelled} once, by the holder, by its {@see getTimeout() Timeout}
 * elapsing on the {@see TAsync::getLoop() loop}, or by a parent token it is linked to.  Code
 * that waits passes the token to {@see TFuture::await()}, calls {@see throwIfCancelled()} at
 * its checkpoints, or {@see subscribe() subscribes} a callback.
 *
 * Cancellation raises {@see onCancel} with the {@see getReason() Reason}, a
 * {@see TCancelledException}, then calls the subscribers.  A subscriber added to a cancelled
 * token is called at once.
 *
 * ```php
 * $token = new TCancellationToken(5.0);          // cancels itself after 5 s
 * $child = new TCancellationToken(null, $token); // cancels when $token does
 * $future->await($child);
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCancellationToken extends TComponent
{
	/** @var ?float The seconds after construction at which the token cancels itself. */
	private ?float $_timeout;

	/** @var ?TCancelledException The reason, once cancelled. */
	private ?TCancelledException $_reason = null;

	/** @var array<string, \Closure> The subscribers, keyed by subscription id. */
	private array $_subscribers = [];

	/** @var int The next subscription id. */
	private int $_nextId = 1;

	/** @var ?string The loop callback of the timeout. */
	private ?string $_timerId = null;

	/** @var array<int, array{0: TCancellationToken, 1: string}> The parent tokens and the subscriptions on them. */
	private array $_parents = [];

	/**
	 * Creates a token, with an optional timeout and parent tokens.
	 * @param ?float $timeout The seconds after which the token cancels itself, or null for none.
	 * @param TCancellationToken ...$parents Tokens whose cancellation cancels this one.
	 */
	public function __construct(?float $timeout = null, TCancellationToken ...$parents)
	{
		parent::__construct();
		$this->_timeout = $timeout;
		foreach ($parents as $parent) {
			$this->linkTo($parent);
		}
		if ($timeout !== null && $this->_reason === null) {
			$weak = \WeakReference::create($this);
			$this->_timerId = TAsync::getLoop()->delay($timeout, static function () use ($weak, $timeout): void {
				$weak->get()?->cancel(new TCancelledException('async_timeout', $timeout));
			});
		}
	}

	/**
	 * Cancels the timeout and the parent subscriptions.
	 */
	public function __destruct()
	{
		$this->detach();
		parent::__destruct();
	}

	/**
	 * Returns the seconds after construction at which the token cancels itself.
	 * @return ?float The timeout, or null for none.
	 */
	public function getTimeout(): ?float
	{
		return $this->_timeout;
	}

	/**
	 * Indicates whether the token is cancelled.
	 * @return bool Whether the token is cancelled.
	 */
	public function isCancelled(): bool
	{
		return $this->_reason !== null;
	}

	/**
	 * Returns the cancellation reason.
	 * @return ?TCancelledException The reason, or null while the token is live.
	 */
	public function getReason(): ?TCancelledException
	{
		return $this->_reason;
	}

	/**
	 * Throws the reason when the token is cancelled.
	 * @throws TCancelledException When the token is cancelled.
	 */
	public function throwIfCancelled(): void
	{
		if ($this->_reason !== null) {
			throw $this->_reason;
		}
	}

	/**
	 * Cancels the token.  A second call is ignored.
	 * @param ?\Throwable $reason The reason; a {@see TCancelledException} is used as is, any other
	 *   exception becomes the previous of a new one, and null gives the plain reason.
	 */
	public function cancel(?\Throwable $reason = null): void
	{
		if ($this->_reason !== null) {
			return;
		}
		if ($reason instanceof TCancelledException) {
			$this->_reason = $reason;
		} elseif ($reason !== null) {
			$this->_reason = new TCancelledException('async_cancelled', $reason);
		} else {
			$this->_reason = new TCancelledException('async_cancelled');
		}
		$this->detach();
		$this->onCancel($this->_reason);
		$subscribers = $this->_subscribers;
		$this->_subscribers = [];
		foreach ($subscribers as $subscriber) {
			$subscriber($this->_reason);
		}
	}

	/**
	 * Raised once when the token is cancelled.
	 * @param mixed $param The {@see TCancelledException} reason.
	 */
	public function onCancel(mixed $param): void
	{
		$this->raiseEvent('onCancel', $this, $param);
	}

	/**
	 * Registers a callback for the cancellation.  A cancelled token calls it at once.
	 * @param callable $callback Called as `(TCancelledException $reason)`.
	 * @return string The subscription id, for {@see unsubscribe()}.
	 */
	public function subscribe(callable $callback): string
	{
		$id = (string) $this->_nextId++;
		if ($this->_reason !== null) {
			$callback($this->_reason);
			return $id;
		}
		$this->_subscribers[$id] = $callback(...);
		return $id;
	}

	/**
	 * Removes a subscription.  An unknown id is ignored.
	 * @param string $id The subscription id.
	 */
	public function unsubscribe(string $id): void
	{
		unset($this->_subscribers[$id]);
	}

	/**
	 * Cancels this token when the parent is cancelled.  A cancelled parent cancels at once.
	 * @param TCancellationToken $parent The parent token.
	 */
	protected function linkTo(TCancellationToken $parent): void
	{
		$weak = \WeakReference::create($this);
		$id = $parent->subscribe(static function (TCancelledException $reason) use ($weak): void {
			$weak->get()?->cancel($reason);
		});
		if ($this->_reason === null) {
			$this->_parents[] = [$parent, $id];
		}
	}

	/**
	 * Cancels the timeout callback and the parent subscriptions.
	 */
	private function detach(): void
	{
		if ($this->_timerId !== null) {
			TAsync::getLoop()->cancel($this->_timerId);
			$this->_timerId = null;
		}
		foreach ($this->_parents as [$parent, $id]) {
			$parent->unsubscribe($id);
		}
		$this->_parents = [];
	}
}
