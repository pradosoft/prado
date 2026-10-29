<?php

/**
 * TAsync class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Async;

use Prado\Exceptions\TCancelledException;

/**
 * TAsync class.
 *
 * TAsync is the static facade of the async API.  {@see getLoop() Loop} is the process-wide
 * {@see IEventLoop} the primitives wait on, a {@see TRevoltEventLoop} unless {@see setLoop()}
 * installs another, such as a {@see \Prado\IO\Socket\TSocketReactor} in a test.
 *
 * - {@see run()} starts a callable in a new fiber and returns its {@see TFuture}.
 * - {@see await()}, {@see all()}, {@see any()}, and {@see timeout()} combine futures.
 * - {@see sleep()} pauses the current fiber on the loop.  It is the one place in the framework
 *   that calls `\usleep()`, as the fallback when a fiber switch is impossible, such as inside a
 *   destructor on PHP 8.2 and 8.3.  Every other pause calls this method or
 *   {@see \Prado\Util\Clock\IClock::sleep()}, which delegates to it.
 *
 * ```php
 * $a = TAsync::run(fn () => $client->get($urlA));
 * $b = TAsync::run(fn () => $client->get($urlB));
 * [$pageA, $pageB] = TAsync::all([$a, $b])->await();
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
final class TAsync
{
	/** @var ?IEventLoop The loop the primitives wait on. */
	private static ?IEventLoop $_loop = null;

	/**
	 * Returns the loop the primitives wait on, a {@see TRevoltEventLoop} unless another is set.
	 * @return IEventLoop The loop.
	 */
	public static function getLoop(): IEventLoop
	{
		return self::$_loop ??= new TRevoltEventLoop();
	}

	/**
	 * Sets the loop the primitives wait on.
	 * @param ?IEventLoop $loop The loop, or null to return to the default on the next {@see getLoop()}.
	 */
	public static function setLoop(?IEventLoop $loop): void
	{
		self::$_loop = $loop;
	}

	/**
	 * Gives a forked child loops of its own.  The process-wide Revolt driver is replaced through
	 * {@see TRevoltEventLoop::installFreshDriver()}, and a {@see setLoop() loop} of another class
	 * is replaced by a new instance of that class, so the child services none of the parent's
	 * callbacks and shares none of its descriptors.  {@see \Prado\Util\Helpers\TProcessHelper::fork()}
	 * calls this in the child.
	 */
	public static function resetLoopForChild(): void
	{
		TRevoltEventLoop::installFreshDriver();
		if (self::$_loop !== null && !(self::$_loop instanceof TRevoltEventLoop)) {
			$class = self::$_loop::class;
			self::$_loop = new $class();
		}
	}

	/**
	 * Runs a callable in a new fiber and returns the future of its result.  The fiber starts at
	 * once and runs until it suspends or returns; its return value resolves the future and an
	 * uncaught exception rejects it.  The new fiber starts with a copy of the caller's
	 * {@see TFiberContext}.
	 * @param callable $callable The fiber body.
	 * @param mixed ...$args The arguments passed to the body.
	 * @return TFuture The future of the body's return value.
	 */
	public static function run(callable $callable, mixed ...$args): TFuture
	{
		$deferred = new TDeferred();
		$context = TFiberContext::all();
		$fiber = new \Fiber(static function () use ($callable, $args, $deferred, $context): void {
			TFiberContext::replace($context);
			try {
				$value = $callable(...$args);
			} catch (\Throwable $e) {
				$deferred->reject($e);
				return;
			}
			$deferred->resolve($value);
		});
		$fiber->start();
		return $deferred->getFuture();
	}

	/**
	 * Waits for a future.
	 * @param TFuture $future The future.
	 * @param ?TCancellationToken $token A token whose cancellation ends the wait.
	 * @throws TCancelledException When the token is cancelled before the future completes.
	 * @throws \Throwable The exception the future was rejected with.
	 * @return mixed The value the future was resolved with.
	 */
	public static function await(TFuture $future, ?TCancellationToken $token = null): mixed
	{
		return $future->await($token);
	}

	/**
	 * Combines futures into one that resolves with their values, keyed as the input, once every
	 * one resolves, or rejects with the first rejection.
	 * @param iterable<array-key, TFuture> $futures The futures.
	 * @return TFuture The combined future.
	 */
	public static function all(iterable $futures): TFuture
	{
		$deferred = new TDeferred();
		$values = [];
		$pending = 0;
		foreach ($futures as $key => $future) {
			$values[$key] = null;
			$pending++;
			$future->onComplete(static function (?\Throwable $error, mixed $value) use ($key, $deferred, &$values, &$pending): void {
				if ($deferred->isComplete()) {
					return;
				}
				if ($error !== null) {
					$deferred->reject($error);
					return;
				}
				$values[$key] = $value;
				if (--$pending === 0) {
					$deferred->resolve($values);
				}
			});
		}
		if ($pending === 0 && !$deferred->isComplete()) {
			$deferred->resolve($values);
		}
		return $deferred->getFuture();
	}

	/**
	 * Combines futures into one that resolves with the first value, or rejects with the last
	 * rejection once every one rejects.  An empty input rejects.
	 * @param iterable<array-key, TFuture> $futures The futures.
	 * @return TFuture The combined future.
	 */
	public static function any(iterable $futures): TFuture
	{
		$deferred = new TDeferred();
		$pending = 0;
		foreach ($futures as $future) {
			$pending++;
			$future->onComplete(static function (?\Throwable $error, mixed $value) use ($deferred, &$pending): void {
				if ($deferred->isComplete()) {
					return;
				}
				if ($error === null) {
					$deferred->resolve($value);
				} elseif (--$pending === 0) {
					$deferred->reject($error);
				}
			});
		}
		if ($pending === 0 && !$deferred->isComplete()) {
			$deferred->reject(new TCancelledException('async_any_empty'));
		}
		return $deferred->getFuture();
	}

	/**
	 * Bounds a future by a deadline.  The returned future takes the input's result, or rejects
	 * with a {@see TCancelledException} once the seconds elapse first; the input stays pending.
	 * @param TFuture $future The future.
	 * @param float $seconds The seconds until the deadline.
	 * @return TFuture The bounded future.
	 */
	public static function timeout(TFuture $future, float $seconds): TFuture
	{
		$deferred = new TDeferred();
		$loop = self::getLoop();
		$timerId = $loop->delay($seconds, static function () use ($deferred, $seconds): void {
			if (!$deferred->isComplete()) {
				$deferred->reject(new TCancelledException('async_timeout', $seconds));
			}
		});
		$future->onComplete(static function (?\Throwable $error, mixed $value) use ($deferred, $loop, $timerId): void {
			$loop->cancel($timerId);
			if ($deferred->isComplete()) {
				return;
			}
			if ($error !== null) {
				$deferred->reject($error);
			} else {
				$deferred->resolve($value);
			}
		});
		return $deferred->getFuture();
	}

	/**
	 * Pauses the current fiber on the loop.  A worker fiber yields to the loop; the main fiber
	 * runs the loop until the timer fires, which also runs the other due callbacks.  Where no
	 * fiber switch is possible, as inside a destructor on PHP 8.2 and 8.3, the pause falls back
	 * to `\usleep()`.  A non-positive value returns at once.
	 * @param float|int $seconds The seconds to pause, fractions allowed.
	 */
	public static function sleep(float|int $seconds): void
	{
		if ($seconds <= 0) {
			return;
		}
		if (\Fiber::getCurrent() === null && !self::canSwitchFibers()) {
			\usleep((int) \round($seconds * 1_000_000));
			return;
		}
		$loop = self::getLoop();
		$suspension = $loop->suspension();
		$timerId = $loop->delay((float) $seconds, static fn () => $suspension->resume());
		try {
			$suspension->suspend();
		} catch (\FiberError $e) {
			$loop->cancel($timerId);
			\usleep((int) \round($seconds * 1_000_000));
		}
	}

	/**
	 * Indicates whether the main fiber can start a fiber here.  A destructor or a garbage
	 * collection run cannot on PHP 8.2 and 8.3, and a main-fiber wait that started the loop
	 * there would leave the loop's suspension pending.  A worker fiber needs no probe: its
	 * suspend throws a {@see \FiberError} that the suspension recovers from.
	 * @return bool Whether a fiber switch is possible.
	 */
	private static function canSwitchFibers(): bool
	{
		try {
			(new \Fiber(static fn () => null))->start();
			return true;
		} catch (\FiberError $e) {
			return false;
		}
	}
}
