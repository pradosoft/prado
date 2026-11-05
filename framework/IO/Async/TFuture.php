<?php

/**
 * TFuture class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Async;

use Prado\Exceptions\TCancelledException;
use Prado\TComponent;

/**
 * TFuture class.
 *
 * TFuture is a value that arrives later.  It completes once, with a value or with an
 * exception, and {@see await()} suspends the current fiber until then: a worker fiber yields
 * to the {@see TAsync::getLoop() loop} and the main fiber runs the loop.  A future that is
 * already complete returns or throws at once.
 *
 * A future is created complete with {@see complete()} or {@see error()}, settled by a
 * {@see TDeferred}, or settled by the executor passed to the constructor, which receives a
 * `$resolve` and a `$reject` callable.  {@see TAsync::run()} returns the future of a fiber.
 *
 * {@see onComplete()} registers a callback for the result; {@see map()}, {@see catch()}, and
 * {@see finally()} derive a new future from it.  Callbacks run in registration order on the
 * fiber that settles the future, or at once when the future is already complete.
 *
 * ```php
 * $future = TAsync::run(fn () => fetch());
 * $future->map(fn ($rows) => count($rows))->await();
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @phpstan-consistent-constructor
 */
class TFuture extends TComponent
{
	/** @var bool Whether the future has its result. */
	private bool $_complete = false;

	/** @var mixed The value, when resolved. */
	private mixed $_value = null;

	/** @var ?\Throwable The exception, when rejected. */
	private ?\Throwable $_error = null;

	/** @var \Closure[] The callbacks waiting for the result, as `(?\Throwable $error, mixed $value)`. */
	private array $_callbacks = [];

	/**
	 * Creates a pending future.  The executor settles it through its two arguments; an exception
	 * the executor throws rejects the future.
	 * @param ?callable $executor Called as `(callable $resolve, callable $reject)`.
	 */
	public function __construct(?callable $executor = null)
	{
		parent::__construct();
		if ($executor !== null) {
			try {
				$executor(
					fn (mixed $value = null) => $this->settle($value, null),
					fn (\Throwable $error) => $this->settle(null, $error),
				);
			} catch (\Throwable $e) {
				$this->settle(null, $e);
			}
		}
	}

	/**
	 * Returns a future resolved with a value.
	 * @param mixed $value The value.
	 * @return static The complete future.
	 */
	public static function complete(mixed $value = null): static
	{
		return new static(static fn ($resolve) => $resolve($value));
	}

	/**
	 * Returns a future rejected with an exception.
	 * @param \Throwable $error The exception.
	 * @return static The complete future.
	 */
	public static function error(\Throwable $error): static
	{
		return new static(static fn ($resolve, $reject) => $reject($error));
	}

	/**
	 * Indicates whether the future has its result.
	 * @return bool Whether the future is complete.
	 */
	public function isComplete(): bool
	{
		return $this->_complete;
	}

	/**
	 * Indicates whether the future completed with a value.
	 * @return bool Whether the future is resolved.
	 */
	public function isResolved(): bool
	{
		return $this->_complete && $this->_error === null;
	}

	/**
	 * Indicates whether the future completed with an exception.
	 * @return bool Whether the future is rejected.
	 */
	public function isRejected(): bool
	{
		return $this->_error !== null;
	}

	/**
	 * Waits for the result.  A pending future suspends the current fiber through the loop's
	 * {@see IEventLoop::suspension() suspension}; from the main fiber this runs the loop.
	 * @param ?TCancellationToken $token A token whose cancellation ends the wait; the future stays pending.
	 * @throws TCancelledException When the token is cancelled before the future completes.
	 * @throws \Throwable The exception the future was rejected with.
	 * @return mixed The value the future was resolved with.
	 */
	public function await(?TCancellationToken $token = null): mixed
	{
		if (!$this->_complete) {
			$token?->throwIfCancelled();
			$suspension = TAsync::getLoop()->suspension();
			$settled = false;
			$this->onComplete(static function (?\Throwable $error, mixed $value) use (&$settled, $suspension): void {
				if ($settled) {
					return;
				}
				$settled = true;
				if ($error === null) {
					$suspension->resume($value);
				} else {
					$suspension->throw($error);
				}
			});
			$subscription = $token?->subscribe(static function (TCancelledException $reason) use (&$settled, $suspension): void {
				if ($settled) {
					return;
				}
				$settled = true;
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
		if ($this->_error !== null) {
			throw $this->_error;
		}
		return $this->_value;
	}

	/**
	 * Registers a callback for the result.  A complete future calls it at once.
	 * @param callable $callback Called as `(?\Throwable $error, mixed $value)`.
	 * @return static The current future.
	 */
	public function onComplete(callable $callback): static
	{
		if ($this->_complete) {
			$callback($this->_error, $this->_value);
		} else {
			$this->_callbacks[] = $callback(...);
		}
		return $this;
	}

	/**
	 * Derives a future from the value.  A rejection passes through; an exception the mapper
	 * throws rejects the derived future.
	 * @param callable $mapper Called as `(mixed $value): mixed`.
	 * @return TFuture The derived future.
	 */
	public function map(callable $mapper): TFuture
	{
		return new TFuture(function (callable $resolve, callable $reject) use ($mapper): void {
			$this->onComplete(static function (?\Throwable $error, mixed $value) use ($mapper, $resolve, $reject): void {
				if ($error !== null) {
					$reject($error);
					return;
				}
				try {
					$resolve($mapper($value));
				} catch (\Throwable $e) {
					$reject($e);
				}
			});
		});
	}

	/**
	 * Derives a future that recovers from a rejection.  A value passes through; the handler's
	 * return value resolves the derived future, and an exception it throws rejects it.
	 * @param callable $handler Called as `(\Throwable $error): mixed`.
	 * @return TFuture The derived future.
	 */
	public function catch(callable $handler): TFuture
	{
		return new TFuture(function (callable $resolve, callable $reject) use ($handler): void {
			$this->onComplete(static function (?\Throwable $error, mixed $value) use ($handler, $resolve, $reject): void {
				if ($error === null) {
					$resolve($value);
					return;
				}
				try {
					$resolve($handler($error));
				} catch (\Throwable $e) {
					$reject($e);
				}
			});
		});
	}

	/**
	 * Derives a future that runs a callback on completion and passes the result through.  An
	 * exception the callback throws rejects the derived future.
	 * @param callable $callback Called as `(): void`.
	 * @return TFuture The derived future.
	 */
	public function finally(callable $callback): TFuture
	{
		return new TFuture(function (callable $resolve, callable $reject) use ($callback): void {
			$this->onComplete(static function (?\Throwable $error, mixed $value) use ($callback, $resolve, $reject): void {
				try {
					$callback();
				} catch (\Throwable $e) {
					$reject($e);
					return;
				}
				if ($error !== null) {
					$reject($error);
				} else {
					$resolve($value);
				}
			});
		});
	}

	/**
	 * Records the result and runs the waiting callbacks in registration order.  A second
	 * settlement is ignored.
	 * @param mixed $value The value.
	 * @param ?\Throwable $error The exception, or null for a value.
	 */
	private function settle(mixed $value, ?\Throwable $error): void
	{
		if ($this->_complete) {
			return;
		}
		$this->_complete = true;
		$this->_value = $value;
		$this->_error = $error;
		$callbacks = $this->_callbacks;
		$this->_callbacks = [];
		foreach ($callbacks as $callback) {
			$callback($error, $value);
		}
	}
}
