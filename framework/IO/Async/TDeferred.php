<?php

/**
 * TDeferred class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Async;

use Prado\Exceptions\TInvalidOperationException;
use Prado\TComponent;

/**
 * TDeferred class.
 *
 * TDeferred is the producer side of a {@see TFuture}.  The producer keeps the deferred and
 * hands out {@see getFuture() Future}; {@see resolve()} or {@see reject()} completes the
 * future once, and a second completion throws.
 *
 * ```php
 * $deferred = new TDeferred();
 * $loop->delay(1.0, fn () => $deferred->resolve('ready'));
 * $deferred->getFuture()->await();   // 'ready'
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TDeferred extends TComponent
{
	/** @var TFuture The future the deferred completes. */
	private TFuture $_future;

	/** @var \Closure The future's resolver. */
	private \Closure $_resolve;

	/** @var \Closure The future's rejecter. */
	private \Closure $_reject;

	/**
	 * Creates the deferred and its pending future.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->_future = new TFuture(function (callable $resolve, callable $reject): void {
			$this->_resolve = $resolve(...);
			$this->_reject = $reject(...);
		});
	}

	/**
	 * Returns the future the deferred completes.
	 * @return TFuture The future.
	 */
	public function getFuture(): TFuture
	{
		return $this->_future;
	}

	/**
	 * Indicates whether the future is complete.
	 * @return bool Whether the deferred has been resolved or rejected.
	 */
	public function isComplete(): bool
	{
		return $this->_future->isComplete();
	}

	/**
	 * Resolves the future with a value.
	 * @param mixed $value The value.
	 * @throws TInvalidOperationException When the future is already complete.
	 */
	public function resolve(mixed $value = null): void
	{
		$this->assertPending();
		($this->_resolve)($value);
	}

	/**
	 * Rejects the future with an exception.
	 * @param \Throwable $error The exception.
	 * @throws TInvalidOperationException When the future is already complete.
	 */
	public function reject(\Throwable $error): void
	{
		$this->assertPending();
		($this->_reject)($error);
	}

	/**
	 * Throws when the future is already complete.
	 * @throws TInvalidOperationException When the future is already complete.
	 */
	private function assertPending(): void
	{
		if ($this->_future->isComplete()) {
			throw new TInvalidOperationException('async_deferred_complete');
		}
	}
}
