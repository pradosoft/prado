<?php

/**
 * IEventLoop interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Async;

use Revolt\EventLoop\Suspension;

/**
 * IEventLoop interface.
 *
 * IEventLoop is PRADO's event loop contract.  A loop schedules callbacks on four kinds of
 * event and runs them one at a time from {@see run()}: a {@see defer() deferred} callback runs
 * on the next iteration, a {@see delay() timer} runs once after a delay, a {@see repeat()
 * repeating timer} runs every interval, a {@see onReadable() stream} callback runs when a
 * stream is readable or {@see onWritable() writable}, and a {@see onSignal() signal} callback
 * runs when the process receives a signal.  Every registration returns a string identifier
 * for {@see enable()}, {@see disable()}, and {@see cancel()}.
 *
 * A fiber waits on the loop through a {@see suspension() Suspension}: it registers a one-shot
 * callback that resumes the suspension, then suspends.  From a worker fiber the suspend yields
 * to the loop; from the main fiber it runs the loop until the callback resumes it.  This is
 * the one wait pattern; a wait never polls the loop.
 *
 * Callback arguments:
 *  - defer, delay, repeat: `(string $callbackId)`.
 *  - onReadable, onWritable: `(string $callbackId, mixed $stream)`, with the stream as registered.
 *  - onSignal: `(string $callbackId, int $signal)`.
 *
 * An exception thrown by a callback goes to the {@see setErrorHandler() ErrorHandler} when one
 * is set, and the loop continues.  Without a handler the exception leaves {@see run()} (or the
 * `suspend()` that runs the loop) with the other callbacks still registered, so the loop runs
 * again afterwards.
 *
 * {@see \Prado\IO\Async\TRevoltEventLoop} is the default implementation over the
 * `revolt/event-loop` driver.  {@see \Prado\IO\Socket\TSocketReactor} is the dependency-free
 * `select()` fallback and also implements {@see ITickableEventLoop}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IEventLoop
{
	/**
	 * Queues a callback for the next loop iteration.
	 * @param callable $callback Called as `(string $callbackId)`.
	 * @return string The callback identifier.
	 */
	public function defer(callable $callback): string;

	/**
	 * Schedules a callback once, after a delay.
	 * @param float $delay The seconds to wait, zero or more.
	 * @param callable $callback Called as `(string $callbackId)`.
	 * @return string The callback identifier.
	 */
	public function delay(float $delay, callable $callback): string;

	/**
	 * Schedules a callback every interval, starting one interval from now.
	 * @param float $interval The seconds between runs, zero or more.
	 * @param callable $callback Called as `(string $callbackId)`.
	 * @return string The callback identifier.
	 */
	public function repeat(float $interval, callable $callback): string;

	/**
	 * Watches a stream for readability.  A stream is a PHP stream resource or an
	 * {@see \Prado\IO\IResource} that owns one.  Several callbacks may watch one stream.
	 * @param mixed $stream The stream to watch.
	 * @param callable $callback Called as `(string $callbackId, mixed $stream)`.
	 * @return string The callback identifier.
	 */
	public function onReadable(mixed $stream, callable $callback): string;

	/**
	 * Watches a stream for writability.  A connected socket is writable almost always, so a
	 * writable watcher is {@see disable() disabled} or {@see cancel() cancelled} once the output
	 * drains.
	 * @param mixed $stream The stream to watch.
	 * @param callable $callback Called as `(string $callbackId, mixed $stream)`.
	 * @return string The callback identifier.
	 */
	public function onWritable(mixed $stream, callable $callback): string;

	/**
	 * Watches a process signal.  The callback runs from the loop, not from the signal handler.
	 * @param int $signal The signal number, such as `SIGTERM`.
	 * @param callable $callback Called as `(string $callbackId, int $signal)`.
	 * @throws \Prado\Exceptions\TNotSupportedException When the platform has no signal handling.
	 * @return string The callback identifier.
	 */
	public function onSignal(int $signal, callable $callback): string;

	/**
	 * Enables a disabled callback.  A re-enabled timer starts its delay from now.
	 * @param string $callbackId The callback identifier.
	 * @throws \Prado\Exceptions\TInvalidDataValueException When the identifier is unknown.
	 * @return string The callback identifier.
	 */
	public function enable(string $callbackId): string;

	/**
	 * Disables a callback without removing it.  A disabled callback does not run and does not
	 * keep {@see run()} alive.
	 * @param string $callbackId The callback identifier.
	 * @throws \Prado\Exceptions\TInvalidDataValueException When the identifier is unknown.
	 * @return string The callback identifier.
	 */
	public function disable(string $callbackId): string;

	/**
	 * Removes a callback.  An unknown identifier is ignored.
	 * @param string $callbackId The callback identifier.
	 */
	public function cancel(string $callbackId): void;

	/**
	 * Returns the identifiers of every registered callback, enabled or not.
	 * @return string[] The callback identifiers.
	 */
	public function getIdentifiers(): array;

	/**
	 * Returns the suspension of the current fiber.  One suspension serves a fiber for its
	 * lifetime, so repeated calls from the same fiber return the same object.
	 * @return Suspension The current fiber's suspension.
	 */
	public function suspension(): Suspension;

	/**
	 * Runs the loop until {@see stop()} is called or no enabled callback remains.  It is called
	 * from the main fiber.
	 */
	public function run(): void;

	/**
	 * Stops the running loop after the current callback returns.
	 */
	public function stop(): void;

	/**
	 * Indicates whether {@see run()} is in progress.
	 * @return bool Whether the loop is running.
	 */
	public function isRunning(): bool;

	/**
	 * Returns the handler that receives exceptions thrown by callbacks.
	 * @return ?callable The handler, called as `(\Throwable $exception)`, or null.
	 */
	public function getErrorHandler(): ?callable;

	/**
	 * Sets the handler that receives exceptions thrown by callbacks.  With a handler set the loop
	 * continues after a throwing callback; without one the exception leaves {@see run()}.
	 * @param ?callable $handler The handler, called as `(\Throwable $exception)`, or null to unset it.
	 */
	public function setErrorHandler(?callable $handler): void;
}
