<?php

/**
 * TSocketReactor class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Socket;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TNotSupportedException;
use Prado\IO\Async\ITickableEventLoop;
use Prado\IO\Async\TLoopSuspension;
use Prado\IO\IResource;
use Prado\TComponent;
use Prado\Util\Clock\IClock;
use Prado\Util\Clock\TClockAwareTrait;
use Revolt\EventLoop\Suspension;

/**
 * TSocketReactor class
 *
 * A single-threaded event loop over one {@see TSocketServer::select() select()} that needs no
 * extension beyond PHP's streams.  It is the dependency-free {@see \Prado\IO\Async\IEventLoop}
 * and the {@see ITickableEventLoop} whose {@see tick()} runs one iteration at a time, so a test
 * drives it step by step.  {@see \Prado\IO\Async\TRevoltEventLoop} is the default loop.
 *
 * Callbacks register through the {@see \Prado\IO\Async\IEventLoop} methods and return string
 * identifiers.  A stream is a PHP stream resource or an {@see IResource} such as a
 * {@see TSocketServer} or a {@see TSocketStream}; several callbacks may watch one stream.  A
 * stream whose resource has closed is pruned on the next tick.  {@see onExcept()} adds the
 * out-of-band readiness `select()` reports.
 *
 * The source API is sugar for a socket server: {@see register()} attaches up to three
 * callbacks to one source, with the writable callback {@see wantWrite() armed} only while output
 * waits, and {@see registerServer()} folds a listener's accepted connections into the loop.
 *
 * Each {@see tick()} runs the {@see defer() deferred} callbacks, delivers pending signals to
 * their PHP handlers (`pcntl_signal_dispatch()`), waits in `select()` up to the timeout or the
 * nearest timer, runs the caught signals' callbacks and the due timers in deadline order, then
 * dispatches the ready streams.  A self-pipe joins every `select()`, so a
 * {@see stop()} or a registration from a signal handler ends the wait at once.  A callback that
 * throws goes to the {@see setErrorHandler() ErrorHandler} when one is set; without one the
 * exception leaves `tick()` or {@see run()} after the loop's bookkeeping is complete, so the
 * other callbacks stay registered and the next tick continues.
 *
 * Timers read the injected {@see \Prado\Util\Clock\IClock} when one is set, so a pinned
 * {@see \Prado\Util\Clock\TMockClock} advances them.  Without an injected clock they read
 * {@see \hrtime()}, which a wall-clock step does not move.  {@see setClock()} re-bases pending
 * timers onto the new clock.
 *
 * {@see suspension()} returns a {@see TLoopSuspension}: a worker fiber that suspends yields to
 * the loop and is re-entered from a deferred callback; the main fiber ticks the loop until it
 * is resumed.  The reactor runs callbacks on the fiber that ticks it, so {@see run()} nests from
 * a callback where Revolt's does not.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TSocketReactor extends TComponent implements ITickableEventLoop
{
	use TClockAwareTrait;

	/** The callback type of {@see defer()}. */
	public const TYPE_DEFER = 'defer';

	/** The callback type of {@see delay()}. */
	public const TYPE_DELAY = 'delay';

	/** The callback type of {@see repeat()}. */
	public const TYPE_REPEAT = 'repeat';

	/** The callback type of {@see onReadable()}. */
	public const TYPE_READABLE = 'readable';

	/** The callback type of {@see onWritable()}. */
	public const TYPE_WRITABLE = 'writable';

	/** The callback type of {@see onExcept()}. */
	public const TYPE_EXCEPT = 'except';

	/** The callback type of {@see onSignal()}. */
	public const TYPE_SIGNAL = 'signal';

	/** The select key of the self-pipe reader; callback identifiers are numeric. */
	private const WAKE_KEY = 'wake';

	/** @var array<string, array{type: string, callback: \Closure, enabled: bool, stream: mixed, delay: float, when: float, signal: int}> The callbacks, keyed by identifier. */
	private array $_callbacks = [];

	/** @var string[] The deferred callback identifiers, in registration order. */
	private array $_deferQueue = [];

	/** @var array<int, array{source: IResource, ids: string[]}> The sources of {@see register()}, keyed by object id. */
	private array $_sources = [];

	/** @var array<int, array<string, true>> The signal callback identifiers, keyed by signal. */
	private array $_signals = [];

	/** @var array<int, mixed> The signal handlers in place before the reactor's, keyed by signal. */
	private array $_priorSignalHandlers = [];

	/** @var array<int, true> The signals caught since the last dispatch. */
	private array $_pendingSignals = [];

	/** @var int The next callback identifier. */
	private int $_nextId = 1;

	/** @var bool[] One flag per nested {@see run()}; {@see stop()} clears the innermost. */
	private array $_runStack = [];

	/** @var bool Whether {@see tick()} is inside `select()`, so a {@see wake()} is needed. */
	private bool $_selecting = false;

	/** @var ?\Closure The handler for exceptions thrown by callbacks. */
	private ?\Closure $_errorHandler = null;

	/** @var ?TSocketStream The read end of the self-pipe. */
	private ?TSocketStream $_wakeReader = null;

	/** @var ?TSocketStream The write end of the self-pipe. */
	private ?TSocketStream $_wakeWriter = null;

	/** @var ?\WeakMap<\Fiber, TLoopSuspension> The suspensions of worker fibers. */
	private ?\WeakMap $_suspensions = null;

	/** @var ?TLoopSuspension The suspension of the main fiber. */
	private ?TLoopSuspension $_mainSuspension = null;

	/**
	 * Closes the self-pipe and returns the watched signals to their prior handlers.
	 */
	public function __destruct()
	{
		foreach (array_keys($this->_signals) as $signal) {
			$this->restoreSignalHandler($signal);
		}
		$this->_wakeReader?->close();
		$this->_wakeWriter?->close();
	}

	// =========================================================================
	// Clock
	// =========================================================================

	/**
	 * Returns the clock timers read, creating the default clock on first use.
	 * @return IClock The clock.
	 */
	public function getClock(): IClock
	{
		if ($this->_clock === null) {
			$this->setClock($this->createClock());
		}
		return $this->_clock;
	}

	/**
	 * Sets the clock timers read and re-bases the pending timers onto it, so each keeps its
	 * remaining delay.  Null returns the reactor to {@see \hrtime()}.
	 * @param ?IClock $clock The clock, or null for the monotonic default.
	 * @return static The current reactor.
	 */
	public function setClock(?IClock $clock): static
	{
		$before = $this->now();
		$this->_clock = $clock;
		$delta = $this->now() - $before;
		foreach ($this->_callbacks as $id => $callback) {
			if ($callback['type'] === self::TYPE_DELAY || $callback['type'] === self::TYPE_REPEAT) {
				$this->_callbacks[$id]['when'] += $delta;
			}
		}
		return $this;
	}

	/**
	 * Returns the current time on the timer base: the injected clock's
	 * {@see \Prado\Util\Clock\IClock::microtime() microtime()}, or {@see \hrtime()} seconds
	 * without one.
	 * @return float The current time in seconds.
	 */
	public function now(): float
	{
		return ($this->_clock === null) ? \hrtime(true) / 1_000_000_000 : $this->_clock->microtime();
	}

	// =========================================================================
	// IEventLoop
	// =========================================================================

	/**
	 * Queues a callback for the next tick.
	 * @param callable $callback Called as `(string $callbackId)`.
	 * @return string The callback identifier.
	 */
	public function defer(callable $callback): string
	{
		$id = $this->add(self::TYPE_DEFER, $callback);
		$this->_deferQueue[] = $id;
		$this->wake();
		return $id;
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
		return $this->addTimer(self::TYPE_DELAY, $delay, $callback);
	}

	/**
	 * Schedules a callback every interval, starting one interval from now.
	 * @param float $interval The seconds between runs, zero or more.
	 * @param callable $callback Called as `(string $callbackId)`.
	 * @throws TInvalidDataValueException When the interval is negative.
	 * @return string The callback identifier.
	 */
	public function repeat(float $interval, callable $callback): string
	{
		return $this->addTimer(self::TYPE_REPEAT, $interval, $callback);
	}

	/**
	 * Watches a stream for readability.
	 * @param mixed $stream A stream resource or an {@see IResource}.
	 * @param callable $callback Called as `(string $callbackId, mixed $stream)`.
	 * @return string The callback identifier.
	 */
	public function onReadable(mixed $stream, callable $callback): string
	{
		return $this->add(self::TYPE_READABLE, $callback, ['stream' => $stream]);
	}

	/**
	 * Watches a stream for writability.  A connected socket is writable almost always, so the
	 * watcher is {@see disable() disabled} or {@see cancel() cancelled} once the output drains.
	 * @param mixed $stream A stream resource or an {@see IResource}.
	 * @param callable $callback Called as `(string $callbackId, mixed $stream)`.
	 * @return string The callback identifier.
	 */
	public function onWritable(mixed $stream, callable $callback): string
	{
		return $this->add(self::TYPE_WRITABLE, $callback, ['stream' => $stream]);
	}

	/**
	 * Watches a stream for out-of-band data.
	 * @param mixed $stream A stream resource or an {@see IResource}.
	 * @param callable $callback Called as `(string $callbackId, mixed $stream)`.
	 * @return string The callback identifier.
	 */
	public function onExcept(mixed $stream, callable $callback): string
	{
		return $this->add(self::TYPE_EXCEPT, $callback, ['stream' => $stream]);
	}

	/**
	 * Watches a process signal.  The reactor installs its own `pcntl_signal()` handler for the
	 * signal while a callback watches it and restores the prior handler when the last callback is
	 * cancelled.  The callback runs from {@see tick()}, not from the handler.
	 * @param int $signal The signal number.
	 * @param callable $callback Called as `(string $callbackId, int $signal)`.
	 * @throws TNotSupportedException When the pcntl extension is not loaded.
	 * @return string The callback identifier.
	 */
	public function onSignal(int $signal, callable $callback): string
	{
		if (!function_exists('pcntl_signal')) {
			throw new TNotSupportedException('eventloop_signals_unsupported');
		}
		$id = $this->add(self::TYPE_SIGNAL, $callback, ['signal' => $signal]);
		if (!isset($this->_signals[$signal])) {
			$this->_priorSignalHandlers[$signal] = pcntl_signal_get_handler($signal);
			pcntl_signal($signal, function (int $caught): void {
				$this->_pendingSignals[$caught] = true;
				$this->wake();
			});
		}
		$this->_signals[$signal][$id] = true;
		return $id;
	}

	/**
	 * Enables a disabled callback.  A deferred callback is queued again and a timer starts its
	 * delay from now.
	 * @param string $callbackId The callback identifier.
	 * @throws TInvalidDataValueException When the identifier is unknown.
	 * @return string The callback identifier.
	 */
	public function enable(string $callbackId): string
	{
		$callback = $this->_callbacks[$callbackId] ?? throw new TInvalidDataValueException('eventloop_callback_invalid', $callbackId);
		if ($callback['enabled']) {
			return $callbackId;
		}
		$this->_callbacks[$callbackId]['enabled'] = true;
		if ($callback['type'] === self::TYPE_DEFER) {
			$this->_deferQueue[] = $callbackId;
		} elseif ($callback['type'] === self::TYPE_DELAY || $callback['type'] === self::TYPE_REPEAT) {
			$this->_callbacks[$callbackId]['when'] = $this->now() + $callback['delay'];
		}
		$this->wake();
		return $callbackId;
	}

	/**
	 * Disables a callback without removing it.
	 * @param string $callbackId The callback identifier.
	 * @throws TInvalidDataValueException When the identifier is unknown.
	 * @return string The callback identifier.
	 */
	public function disable(string $callbackId): string
	{
		$callback = $this->_callbacks[$callbackId] ?? throw new TInvalidDataValueException('eventloop_callback_invalid', $callbackId);
		$this->_callbacks[$callbackId]['enabled'] = false;
		if ($callback['type'] === self::TYPE_DEFER) {
			$this->dequeueDeferred($callbackId);
		}
		return $callbackId;
	}

	/**
	 * Removes a callback.  An unknown identifier is ignored.
	 * @param string $callbackId The callback identifier.
	 */
	public function cancel(string $callbackId): void
	{
		$callback = $this->_callbacks[$callbackId] ?? null;
		if ($callback === null) {
			return;
		}
		unset($this->_callbacks[$callbackId]);
		if ($callback['type'] === self::TYPE_DEFER) {
			$this->dequeueDeferred($callbackId);
		} elseif ($callback['type'] === self::TYPE_SIGNAL) {
			unset($this->_signals[$callback['signal']][$callbackId]);
			if ($this->_signals[$callback['signal']] === []) {
				$this->restoreSignalHandler($callback['signal']);
			}
		}
	}

	/**
	 * Returns the identifiers of every registered callback.
	 * @return string[] The callback identifiers.
	 */
	public function getIdentifiers(): array
	{
		return array_map('strval', array_keys($this->_callbacks));
	}

	/**
	 * Returns the suspension of the current fiber, one per fiber.
	 * @return Suspension The current fiber's {@see TLoopSuspension}.
	 */
	public function suspension(): Suspension
	{
		$fiber = \Fiber::getCurrent();
		if ($fiber === null) {
			return $this->_mainSuspension ??= new TLoopSuspension($this);
		}
		$this->_suspensions ??= new \WeakMap();
		return $this->_suspensions[$fiber] ??= new TLoopSuspension($this);
	}

	/**
	 * Ticks until {@see stop()} is called or no enabled callback remains.  A call from a
	 * callback nests; its {@see stop()} ends the inner run only.
	 * @throws \Throwable The exception a callback threw with no {@see setErrorHandler() ErrorHandler}.
	 */
	public function run(): void
	{
		$this->_runStack[] = true;
		$depth = array_key_last($this->_runStack);
		try {
			while ($this->_runStack[$depth] && !$this->isEmpty()) {
				$this->tick(null);
			}
		} finally {
			unset($this->_runStack[$depth]);
		}
	}

	/**
	 * Stops the innermost {@see run()} after the current tick.
	 */
	public function stop(): void
	{
		if ($this->_runStack !== []) {
			$this->_runStack[array_key_last($this->_runStack)] = false;
			$this->wake();
		}
	}

	/**
	 * Indicates whether a {@see run()} is in progress.
	 * @return bool Whether the loop is running.
	 */
	public function isRunning(): bool
	{
		return $this->_runStack !== [];
	}

	/**
	 * Returns the handler that receives exceptions thrown by callbacks.
	 * @return ?callable The handler, or null.
	 */
	public function getErrorHandler(): ?callable
	{
		return $this->_errorHandler;
	}

	/**
	 * Sets the handler that receives exceptions thrown by callbacks.
	 * @param ?callable $handler The handler, called as `(\Throwable $exception)`, or null to unset it.
	 */
	public function setErrorHandler(?callable $handler): void
	{
		$this->_errorHandler = ($handler === null) ? null : $handler(...);
	}

	// =========================================================================
	// ITickableEventLoop
	// =========================================================================

	/**
	 * Indicates whether no enabled callback remains.
	 * @return bool Whether the loop has nothing to run.
	 */
	public function isEmpty(): bool
	{
		foreach ($this->_callbacks as $callback) {
			if ($callback['enabled']) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Runs one iteration: prunes closed streams, runs the deferred callbacks, waits for readiness
	 * up to the timeout or the next timer, then runs the caught signals, the due timers, and the
	 * ready streams.  An empty loop returns at once when the timeout is null.
	 * @param ?float $timeout The maximum seconds to wait; null waits for the next event.
	 * @throws \Throwable The exception a callback threw with no {@see setErrorHandler() ErrorHandler}.
	 * @return int The number of ready streams, or 0 on timeout or select error.
	 * @phpstan-impure
	 */
	public function tick(?float $timeout = null): int
	{
		$this->pruneClosed();
		$this->runDeferred();
		if ($timeout === null && $this->isEmpty()) {
			return 0;
		}
		$this->catchSignals();

		[$read, $write, $except] = $this->collectStreams();
		$read[self::WAKE_KEY] = $this->getWakeReader();
		[$seconds, $microseconds] = $this->splitDelay($this->resolveTimeout($timeout));
		$write = $write ?: null;
		$except = $except ?: null;
		$this->_selecting = true;
		try {
			$count = TSocketServer::select($read, $write, $except, $seconds, $microseconds);
		} finally {
			$this->_selecting = false;
		}
		if ($count === false) {
			$read = $write = $except = [];
		}
		if (isset($read[self::WAKE_KEY])) {
			$this->drainWake();
			unset($read[self::WAKE_KEY]);
		}

		$this->dispatchSignals();
		$this->runDueTimers();
		$this->dispatchStreams($read, self::TYPE_READABLE);
		$this->dispatchStreams($write ?? [], self::TYPE_WRITABLE);
		$this->dispatchStreams($except ?? [], self::TYPE_EXCEPT);
		return count($read) + count($write ?? []) + count($except ?? []);
	}

	// =========================================================================
	// Source API
	// =========================================================================

	/**
	 * Registers a source with up to three callbacks.  Re-registering a source replaces them.  The
	 * writable callback starts disarmed; {@see wantWrite()} arms it.
	 * @param IResource $source The server or connection to watch.
	 * @param ?callable $onReadable Called as `(string $callbackId, IResource $source)` when readable.
	 * @param ?callable $onWritable Called as `(string $callbackId, IResource $source)` when writable and armed.
	 * @param ?callable $onExcept Called as `(string $callbackId, IResource $source)` on out-of-band data.
	 */
	public function register(IResource $source, ?callable $onReadable = null, ?callable $onWritable = null, ?callable $onExcept = null): void
	{
		$this->unregister($source);
		$ids = [];
		if ($onReadable !== null) {
			$ids[] = $this->onReadable($source, $onReadable);
		}
		if ($onWritable !== null) {
			$ids[] = $id = $this->onWritable($source, $onWritable);
			$this->_callbacks[$id]['enabled'] = false;
		}
		if ($onExcept !== null) {
			$ids[] = $this->onExcept($source, $onExcept);
		}
		$this->_sources[spl_object_id($source)] = ['source' => $source, 'ids' => $ids];
	}

	/**
	 * Removes a source and its callbacks.
	 * @param IResource $source The source to remove.
	 */
	public function unregister(IResource $source): void
	{
		$key = spl_object_id($source);
		foreach ($this->_sources[$key]['ids'] ?? [] as $id) {
			$this->cancel($id);
		}
		unset($this->_sources[$key]);
	}

	/**
	 * Indicates whether a source is registered through {@see register()}.
	 * @param IResource $source The source to test.
	 * @return bool Whether the source is registered.
	 */
	public function isRegistered(IResource $source): bool
	{
		return isset($this->_sources[spl_object_id($source)]);
	}

	/**
	 * Arms or disarms a source's writable callback.  A connected socket is writable almost
	 * always, so it is watched only while armed: arm it to finish a connect, advance a TLS
	 * handshake, or flush buffered output, and disarm it once the output drains.
	 * @param IResource $source The registered source.
	 * @param bool $want Whether to watch the source for writability.
	 */
	public function wantWrite(IResource $source, bool $want = true): void
	{
		foreach ($this->_sources[spl_object_id($source)]['ids'] ?? [] as $id) {
			if (($this->_callbacks[$id]['type'] ?? null) === self::TYPE_WRITABLE) {
				$want ? $this->enable($id) : $this->disable($id);
			}
		}
	}

	/**
	 * Returns the number of sources registered through {@see register()}.
	 * @return int The number of registered sources.
	 */
	public function getSourceCount(): int
	{
		return count($this->_sources);
	}

	/**
	 * Watches a listening server and folds its accepted connections into the loop.  The server
	 * is set non-blocking and registered with a readable callback that
	 * {@see TSocketServer::accept() accepts} each pending connection, sets it non-blocking, and
	 * registers it with $onData as its readable callback.  {@see unregister()} of the server
	 * undoes it; the connections stay registered.
	 * @param TSocketServer $server The listening server.
	 * @param callable $onData Called as `(string $callbackId, TSocketStream $connection)` when a connection has bytes to read.
	 */
	public function registerServer(TSocketServer $server, callable $onData): void
	{
		$server->setBlocking(false);
		$this->register($server, onReadable: function () use ($server, $onData): void {
			$connection = $server->accept(0.0);
			if ($connection !== null) {
				$connection->setBlocking(false);
				$this->register($connection, onReadable: $onData);
			}
		});
	}

	// =========================================================================
	// Internals
	// =========================================================================

	/**
	 * Records a callback and returns its identifier.
	 * @param string $type The callback type, one of the `TYPE_*` constants.
	 * @param callable $callback The callback.
	 * @param array $fields The type-specific fields: `stream`, `delay`, `when`, or `signal`.
	 * @return string The callback identifier.
	 */
	private function add(string $type, callable $callback, array $fields = []): string
	{
		$id = (string) $this->_nextId++;
		$this->_callbacks[$id] = array_merge([
			'type' => $type,
			'callback' => $callback(...),
			'enabled' => true,
			'stream' => null,
			'delay' => 0.0,
			'when' => 0.0,
			'signal' => 0,
		], $fields);
		return $id;
	}

	/**
	 * Records a timer due one delay from now.
	 * @param string $type {@see TYPE_DELAY} or {@see TYPE_REPEAT}.
	 * @param float $delay The seconds to wait, zero or more.
	 * @param callable $callback The callback.
	 * @throws TInvalidDataValueException When the delay is negative.
	 * @return string The callback identifier.
	 */
	private function addTimer(string $type, float $delay, callable $callback): string
	{
		if ($delay < 0) {
			throw new TInvalidDataValueException('eventloop_delay_invalid', $delay);
		}
		$id = $this->add($type, $callback, ['delay' => $delay, 'when' => $this->now() + $delay]);
		$this->wake();
		return $id;
	}

	/**
	 * Removes a deferred callback from the queue.
	 * @param string $callbackId The callback identifier.
	 */
	private function dequeueDeferred(string $callbackId): void
	{
		$this->_deferQueue = array_values(array_filter($this->_deferQueue, fn ($id) => $id !== $callbackId));
	}

	/**
	 * Runs a callback, routing an exception to the error handler or out of the tick.
	 * @param string $callbackId The callback identifier.
	 * @param mixed ...$args The arguments after the identifier.
	 * @throws \Throwable The callback's exception when no error handler is set.
	 */
	private function invoke(string $callbackId, mixed ...$args): void
	{
		$callback = $this->_callbacks[$callbackId] ?? null;
		if ($callback === null || !$callback['enabled']) {
			return;
		}
		try {
			($callback['callback'])($callbackId, ...$args);
		} catch (\Throwable $e) {
			if ($this->_errorHandler === null) {
				throw $e;
			}
			($this->_errorHandler)($e);
		}
	}

	/**
	 * Runs the deferred callbacks queued before this tick.  A callback deferred by one of them
	 * runs on the next tick.  A throwing callback leaves the rest queued.
	 */
	private function runDeferred(): void
	{
		$count = count($this->_deferQueue);
		while ($count-- > 0 && $this->_deferQueue !== []) {
			$id = array_shift($this->_deferQueue);
			$callback = $this->_callbacks[$id] ?? null;
			if ($callback === null) {
				continue;
			}
			unset($this->_callbacks[$id]);
			if (!$callback['enabled']) {
				continue;
			}
			try {
				($callback['callback'])($id);
			} catch (\Throwable $e) {
				if ($this->_errorHandler === null) {
					throw $e;
				}
				($this->_errorHandler)($e);
			}
		}
	}

	/**
	 * Runs the due timers in deadline order.  A one-shot timer is removed and a repeating timer is
	 * rescheduled before its callback runs, so a throwing callback leaves the schedule consistent.
	 */
	private function runDueTimers(): void
	{
		$now = $this->now();
		$due = [];
		foreach ($this->_callbacks as $id => $callback) {
			if ($callback['enabled'] && $callback['when'] <= $now && ($callback['type'] === self::TYPE_DELAY || $callback['type'] === self::TYPE_REPEAT)) {
				$due[$id] = $callback['when'];
			}
		}
		asort($due);
		foreach (array_keys($due) as $id) {
			$id = (string) $id;
			$callback = $this->_callbacks[$id] ?? null;
			if ($callback === null || !$callback['enabled']) {
				continue;
			}
			if ($callback['type'] === self::TYPE_DELAY) {
				unset($this->_callbacks[$id]);
				try {
					($callback['callback'])($id);
				} catch (\Throwable $e) {
					if ($this->_errorHandler === null) {
						throw $e;
					}
					($this->_errorHandler)($e);
				}
			} else {
				$this->_callbacks[$id]['when'] = $now + $callback['delay'];
				$this->invoke($id);
			}
		}
	}

	/**
	 * Dispatches the ready streams of one type to their callbacks.
	 * @param array<int|string, mixed> $ready The ready streams, keyed by callback identifier.
	 * @param string $type The callback type the keys belong to.
	 */
	private function dispatchStreams(array $ready, string $type): void
	{
		foreach ($ready as $id => $stream) {
			$id = (string) $id;
			if (($this->_callbacks[$id]['type'] ?? null) === $type) {
				$this->invoke($id, $stream);
			}
		}
	}

	/**
	 * Collects the enabled stream callbacks into the three select sets, keyed by identifier.
	 * @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: array<string, mixed>} The readable, writable, and except sets.
	 */
	private function collectStreams(): array
	{
		$sets = [self::TYPE_READABLE => [], self::TYPE_WRITABLE => [], self::TYPE_EXCEPT => []];
		foreach ($this->_callbacks as $id => $callback) {
			if ($callback['enabled'] && isset($sets[$callback['type']])) {
				$sets[$callback['type']][$id] = $callback['stream'];
			}
		}
		return array_values($sets);
	}

	/**
	 * Cancels the stream callbacks whose resource has closed and forgets their sources.
	 */
	private function pruneClosed(): void
	{
		foreach ($this->_callbacks as $id => $callback) {
			if ($callback['stream'] !== null && !is_resource(self::resourceOf($callback['stream']))) {
				$this->cancel((string) $id);
			}
		}
		foreach ($this->_sources as $key => $entry) {
			if (!is_resource($entry['source']->getResource())) {
				$this->unregister($entry['source']);
			}
		}
	}

	/**
	 * Returns the PHP stream behind a watched stream.
	 * @param mixed $stream A stream resource or an {@see IResource}.
	 * @return mixed The stream resource.
	 */
	private static function resourceOf(mixed $stream): mixed
	{
		return ($stream instanceof IResource) ? $stream->getResource() : $stream;
	}

	/**
	 * Delivers pending signals to the PHP handlers, so handlers installed in sync mode, such as
	 * the {@see \Prado\Util\TSignalsDispatcher}, run during a tick.
	 */
	private function catchSignals(): void
	{
		if (function_exists('pcntl_signal_dispatch')) {
			pcntl_signal_dispatch();
		}
	}

	/**
	 * Runs the callbacks of the signals caught since the last dispatch.
	 */
	private function dispatchSignals(): void
	{
		$this->catchSignals();
		$pending = $this->_pendingSignals;
		$this->_pendingSignals = [];
		foreach (array_keys($pending) as $signal) {
			foreach (array_keys($this->_signals[$signal] ?? []) as $id) {
				$this->invoke((string) $id, $signal);
			}
		}
	}

	/**
	 * Returns a signal to the handler in place before {@see onSignal()}.
	 * @param int $signal The signal number.
	 */
	private function restoreSignalHandler(int $signal): void
	{
		unset($this->_signals[$signal], $this->_pendingSignals[$signal]);
		if (array_key_exists($signal, $this->_priorSignalHandlers) && function_exists('pcntl_signal')) {
			pcntl_signal($signal, $this->_priorSignalHandlers[$signal]);
			unset($this->_priorSignalHandlers[$signal]);
		}
	}

	/**
	 * Returns the read end of the self-pipe, creating the pair on first use.
	 * @return TSocketStream The self-pipe reader.
	 */
	private function getWakeReader(): TSocketStream
	{
		if ($this->_wakeReader === null) {
			[$reader, $writer] = TSocketStream::pair();
			$reader->setBlocking(false);
			$writer->setBlocking(false);
			$this->_wakeReader = $reader;
			$this->_wakeWriter = $writer;
		}
		return $this->_wakeReader;
	}

	/**
	 * Ends a `select()` in progress by writing to the self-pipe.  A write that fails means a wake
	 * is already pending, so it is ignored.
	 */
	private function wake(): void
	{
		if (!$this->_selecting || $this->_wakeWriter === null) {
			return;
		}
		try {
			$this->_wakeWriter->write("\0");
		} catch (\Throwable $e) {
		}
	}

	/**
	 * Empties the self-pipe after a wake.
	 */
	private function drainWake(): void
	{
		try {
			$this->_wakeReader?->read(1024);
		} catch (\Throwable $e) {
		}
	}

	/**
	 * Resolves the wait to the smaller of the caller's timeout and the nearest enabled timer, or
	 * zero when deferred callbacks or caught signals wait.
	 * @param ?float $timeout The caller's maximum wait, or null to wait for the next event.
	 * @return ?float The seconds to wait, or null to wait for the next event.
	 */
	private function resolveTimeout(?float $timeout): ?float
	{
		if ($this->_deferQueue !== [] || $this->_pendingSignals !== []) {
			return 0.0;
		}
		$deadline = null;
		foreach ($this->_callbacks as $callback) {
			if ($callback['enabled'] && ($callback['type'] === self::TYPE_DELAY || $callback['type'] === self::TYPE_REPEAT) && ($deadline === null || $callback['when'] < $deadline)) {
				$deadline = $callback['when'];
			}
		}
		if ($deadline === null) {
			return $timeout;
		}
		$timerDelay = max(0.0, $deadline - $this->now());
		return ($timeout === null) ? $timerDelay : min($timeout, $timerDelay);
	}

	/**
	 * Splits a float-second delay into the integer seconds and microseconds `select()` takes.
	 * @param ?float $delay The seconds to wait, or null to wait for the next event.
	 * @return array{0: ?int, 1: int} The seconds (null to wait for the next event) and microseconds.
	 */
	private function splitDelay(?float $delay): array
	{
		if ($delay === null) {
			return [null, 0];
		}
		$seconds = (int) $delay;
		$microseconds = (int) round(($delay - $seconds) * 1000000);
		if ($microseconds >= 1000000) {
			$seconds++;
			$microseconds -= 1000000;
		}
		return [$seconds, $microseconds];
	}
}
