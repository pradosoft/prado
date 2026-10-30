<?php

/**
 * TRevoltEventLoop class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Async;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TNotSupportedException;
use Prado\IO\IResource;
use Prado\TComponent;
use Revolt\EventLoop;
use Revolt\EventLoop\Internal\AbstractDriver;
use Revolt\EventLoop\InvalidCallbackError;
use Revolt\EventLoop\Suspension;
use Revolt\EventLoop\UncaughtThrowable;
use Revolt\EventLoop\UnsupportedFeatureException;

/**
 * TRevoltEventLoop class.
 *
 * TRevoltEventLoop is the default {@see IEventLoop}, a thin adapter over the process-wide
 * `revolt/event-loop` driver ({@see \Revolt\EventLoop}).  Revolt selects the best installed
 * driver: `ext-uv`, `ext-ev`, or `ext-event` when present, otherwise its `stream_select()`
 * driver.  Every instance addresses the same driver, so two instances share one set of
 * callbacks and one {@see run()}.
 *
 * Differences from {@see \Prado\IO\Socket\TSocketReactor}:
 *  - timers read Revolt's monotonic clock; an injected {@see \Prado\Util\Clock\IClock} does not
 *    advance them.
 *  - {@see run()} throws when called from a worker fiber.
 *  - there is no {@see ITickableEventLoop::tick() tick()}.
 *
 * An {@see IResource} passed to {@see onReadable()} or {@see onWritable()} is unwrapped to its
 * PHP stream for the driver, and the callback receives the object as registered.  A callback's
 * return value is discarded, where the driver rejects a non-null return.  An exception a callback
 * throws without an {@see setErrorHandler() ErrorHandler} leaves {@see run()} as the original
 * exception, unwrapped from Revolt's {@see UncaughtThrowable}.
 *
 * {@see isRunning()} is true while a {@see run()} of this class is in progress or while the
 * driver's loop fiber executes, which runs every callback and every resume of a suspended fiber.
 * The driver's own `isRunning()` stays true while its loop fiber is suspended after an interrupt
 * (a resumed main-fiber suspension or an uncaught exception), so it is not used alone.
 * {@see stop()} acts only while the loop is running.
 *
 * A forked child calls {@see installFreshDriver()} before its first callback, so it does not
 * service the parent's callbacks.  {@see \Revolt\EventLoop::setDriver()} throws while a driver
 * runs; a child forked outside a callback unwinds the inherited driver first, and a child forked
 * from inside a callback keeps the inherited driver with every callback cancelled.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TRevoltEventLoop extends TComponent implements IEventLoop
{
	/** @var int The number of {@see run()} calls in progress. */
	private static int $_runDepth = 0;

	/** @var array<int, array<string, true>> The signal callbacks registered through this class, keyed by signal. */
	private static array $_signalIds = [];

	/** @var array<int, mixed> The pcntl handlers in place before the first callback of each signal. */
	private static array $_priorSignalHandlers = [];

	/** @var ?\Closure The reader of a driver's loop fiber, bound to the driver's scope. */
	private static ?\Closure $_loopFiberReader = null;

	/**
	 * Gives a forked child a driver with no callbacks.  The inherited driver's callbacks are
	 * cancelled and its error handler unset.  Outside a fiber the inherited driver is then run to
	 * unwind its loop fiber and a new driver replaces it; inside a fiber the driver cannot be
	 * replaced, so the emptied inherited driver stays.
	 */
	public static function installFreshDriver(): void
	{
		$driver = EventLoop::getDriver();
		if ($driver->isRunning()) {
			foreach ($driver->getIdentifiers() as $id) {
				$driver->cancel($id);
			}
			$driver->setErrorHandler(null);
			if (\Fiber::getCurrent() !== null) {
				return;
			}
			$driver->run();
		}
		EventLoop::setDriver((new EventLoop\DriverFactory())->create());
	}

	/**
	 * Wraps a callback in a closure that discards its return value, as the driver requires.
	 * @param callable $callback The callback.
	 * @return \Closure The void closure.
	 */
	protected static function wrap(callable $callback): \Closure
	{
		return static function (mixed ...$args) use ($callback): void {
			$callback(...$args);
		};
	}

	/**
	 * Returns the PHP stream behind a watched stream.
	 * @param mixed $stream A stream resource or an {@see IResource}.
	 * @return mixed The stream resource.
	 */
	protected static function resourceOf(mixed $stream): mixed
	{
		return ($stream instanceof IResource) ? $stream->getResource() : $stream;
	}

	/**
	 * Queues a callback for the next loop iteration.
	 * @param callable $callback Called as `(string $callbackId)`.
	 * @return string The callback identifier.
	 */
	public function defer(callable $callback): string
	{
		return EventLoop::defer(static::wrap($callback));
	}

	/**
	 * Schedules a callback once, after a delay.
	 * @param float $delay The seconds to wait, zero or more.
	 * @param callable $callback Called as `(string $callbackId)`.
	 * @throws TInvalidDataValueException When the delay is negative.
	 * @return string The callback identifier.
	 */
	public function delay(float $delay, callable $callback): string
	{
		if ($delay < 0) {
			throw new TInvalidDataValueException('eventloop_delay_invalid', $delay);
		}
		return EventLoop::delay($delay, static::wrap($callback));
	}

	/**
	 * Schedules a callback every interval.
	 * @param float $interval The seconds between runs, zero or more.
	 * @param callable $callback Called as `(string $callbackId)`.
	 * @throws TInvalidDataValueException When the interval is negative.
	 * @return string The callback identifier.
	 */
	public function repeat(float $interval, callable $callback): string
	{
		if ($interval < 0) {
			throw new TInvalidDataValueException('eventloop_delay_invalid', $interval);
		}
		return EventLoop::repeat($interval, static::wrap($callback));
	}

	/**
	 * Watches a stream for readability.
	 * @param mixed $stream A stream resource or an {@see IResource}.
	 * @param callable $callback Called as `(string $callbackId, mixed $stream)`.
	 * @return string The callback identifier.
	 */
	public function onReadable(mixed $stream, callable $callback): string
	{
		return EventLoop::onReadable(static::resourceOf($stream), static function (string $id) use ($callback, $stream): void {
			$callback($id, $stream);
		});
	}

	/**
	 * Watches a stream for writability.
	 * @param mixed $stream A stream resource or an {@see IResource}.
	 * @param callable $callback Called as `(string $callbackId, mixed $stream)`.
	 * @return string The callback identifier.
	 */
	public function onWritable(mixed $stream, callable $callback): string
	{
		return EventLoop::onWritable(static::resourceOf($stream), static function (string $id) use ($callback, $stream): void {
			$callback($id, $stream);
		});
	}

	/**
	 * Watches a process signal.  The driver installs its own `pcntl_signal()` handler once the
	 * loop runs; when the last callback of the signal registered through this class is
	 * {@see cancel() cancelled}, the handler in place before the first one is restored, as the
	 * driver leaves `SIG_DFL`.
	 * @param int $signal The signal number.
	 * @param callable $callback Called as `(string $callbackId, int $signal)`.
	 * @throws TNotSupportedException When the driver has no signal handling.
	 * @return string The callback identifier.
	 */
	public function onSignal(int $signal, callable $callback): string
	{
		try {
			$id = EventLoop::onSignal($signal, static::wrap($callback));
		} catch (UnsupportedFeatureException $e) {
			throw new TNotSupportedException('eventloop_signals_unsupported');
		}
		if (!isset(self::$_signalIds[$signal])) {
			self::$_priorSignalHandlers[$signal] = pcntl_signal_get_handler($signal);
		}
		self::$_signalIds[$signal][$id] = true;
		return $id;
	}

	/**
	 * Enables a disabled callback.
	 * @param string $callbackId The callback identifier.
	 * @throws TInvalidDataValueException When the identifier is unknown.
	 * @return string The callback identifier.
	 */
	public function enable(string $callbackId): string
	{
		try {
			return EventLoop::enable($callbackId);
		} catch (InvalidCallbackError $e) {
			throw new TInvalidDataValueException('eventloop_callback_invalid', $callbackId);
		}
	}

	/**
	 * Disables a callback without removing it.
	 * @param string $callbackId The callback identifier.
	 * @throws TInvalidDataValueException When the identifier is unknown.
	 * @return string The callback identifier.
	 */
	public function disable(string $callbackId): string
	{
		try {
			return EventLoop::disable($callbackId);
		} catch (InvalidCallbackError $e) {
			throw new TInvalidDataValueException('eventloop_callback_invalid', $callbackId);
		}
	}

	/**
	 * Removes a callback.  An unknown identifier is ignored.
	 * @param string $callbackId The callback identifier.
	 */
	public function cancel(string $callbackId): void
	{
		EventLoop::cancel($callbackId);
		$this->forgetSignalCallback($callbackId);
	}

	/**
	 * Drops a cancelled callback from the signal registry and restores the prior handler of a
	 * signal that has no callback of this class left.
	 * @param string $callbackId The cancelled callback identifier.
	 */
	private function forgetSignalCallback(string $callbackId): void
	{
		foreach (self::$_signalIds as $signal => $ids) {
			if (!isset($ids[$callbackId])) {
				continue;
			}
			unset(self::$_signalIds[$signal][$callbackId]);
			if (self::$_signalIds[$signal] === []) {
				unset(self::$_signalIds[$signal]);
				$prior = self::$_priorSignalHandlers[$signal] ?? \SIG_DFL;
				unset(self::$_priorSignalHandlers[$signal]);
				if ($prior !== \SIG_DFL && pcntl_signal_get_handler($signal) === \SIG_DFL) {
					pcntl_signal($signal, $prior);
				}
			}
			return;
		}
	}

	/**
	 * Returns the identifiers of every registered callback.
	 * @return string[] The callback identifiers.
	 */
	public function getIdentifiers(): array
	{
		return EventLoop::getIdentifiers();
	}

	/**
	 * Returns the suspension of the current fiber.
	 * @return Suspension The current fiber's suspension.
	 */
	public function suspension(): Suspension
	{
		return EventLoop::getSuspension();
	}

	/**
	 * Runs the loop until {@see stop()} is called or no enabled callback remains.
	 * @throws \Throwable The exception a callback threw with no {@see setErrorHandler() ErrorHandler}.
	 * @throws \Error When called from a worker fiber or while the loop runs.
	 */
	public function run(): void
	{
		self::$_runDepth++;
		try {
			EventLoop::run();
		} catch (UncaughtThrowable $e) {
			throw $e->getPrevious() ?? $e;
		} finally {
			self::$_runDepth--;
		}
	}

	/**
	 * Stops the running loop after the current callback returns.  Outside a run it does nothing;
	 * the driver keeps a stop until its loop fiber next runs, which would end the next wait.
	 * A worker fiber started outside a run is outside it.
	 */
	public function stop(): void
	{
		if ($this->isRunning()) {
			EventLoop::getDriver()->stop();
		}
	}

	/**
	 * Indicates whether a {@see run()} is in progress or the current fiber is a driver callback.
	 * @return bool Whether the loop is running.
	 */
	public function isRunning(): bool
	{
		return self::$_runDepth > 0 || (\Fiber::getCurrent() !== null && static::isDriverExecuting());
	}

	/**
	 * Indicates whether the driver's loop fiber executes, so the current code runs inside the loop.
	 * Revolt exposes only whether that fiber is running or suspended, so its state is read from
	 * the driver; a driver of another class falls back to the driver's `isRunning()`.
	 * @return bool Whether the driver's loop fiber executes.
	 */
	protected static function isDriverExecuting(): bool
	{
		$driver = EventLoop::getDriver();
		if (!($driver instanceof AbstractDriver) || !property_exists(AbstractDriver::class, 'fiber')) {
			return $driver->isRunning();
		}
		self::$_loopFiberReader ??= \Closure::bind(static fn (AbstractDriver $driver): \Fiber => $driver->fiber, null, AbstractDriver::class);
		return (self::$_loopFiberReader)($driver)->isRunning();
	}

	/**
	 * Returns the handler that receives exceptions thrown by callbacks.
	 * @return ?callable The handler, or null.
	 */
	public function getErrorHandler(): ?callable
	{
		return EventLoop::getErrorHandler();
	}

	/**
	 * Sets the handler that receives exceptions thrown by callbacks.
	 * @param ?callable $handler The handler, called as `(\Throwable $exception)`, or null to unset it.
	 */
	public function setErrorHandler(?callable $handler): void
	{
		EventLoop::setErrorHandler($handler === null ? null : $handler(...));
	}
}
