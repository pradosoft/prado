<?php

/**
 * TSocketReactor class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Socket;

use Prado\IO\IResource;
use Prado\TComponent;

/**
 * TSocketReactor class
 *
 * A single-threaded event loop that multiplexes many I/O sources through one
 * {@see TSocketServer::select() select()}.  Servers (their listening sockets), accepted
 * connections, and outbound connections all {@see register()} with one reactor, so a single
 * process accepts inbound peers and drives outbound peers together rather than one blocking
 * loop per server.  It is a plain {@see TComponent}, free of {@see \Prado\TApplication}, so it
 * runs in a daemon, a test, or any entry point.
 *
 * A source is an {@see IResource} (a {@see TSocketServer} or a {@see TSocketStream}).  It is
 * registered with up to three callbacks, each invoked with the source when it is ready:
 *
 *  - readable: a listener has a connection to {@see TSocketServer::accept() accept}, or a
 *    connection has bytes to read.  A readable source is watched every tick.
 *  - writable: an outbound connection finished connecting, a TLS handshake may advance, or
 *    buffered output may flush.  A writable socket is almost always ready, so it is watched
 *    only while {@see wantWrite() armed}, avoiding a busy loop.
 *  - except: out-of-band/urgent data arrived.
 *
 * Timers run between I/O events: {@see scheduleAt()}/{@see after()}/{@see every()} register
 * callbacks, and the select timeout shrinks to the nearest due timer so pings, idle timeouts,
 * and reconnect backoff fire on schedule.  {@see tick()} runs one iteration (the testable unit);
 * {@see run()} ticks until {@see stop()}.  A source whose socket has closed is pruned
 * automatically on the next tick.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TSocketReactor extends TComponent
{
	/** @var array<int, array{source: IResource, read: ?callable, write: ?callable, except: ?callable}> Registered sources, keyed by object id. */
	private array $_sources = [];

	/** @var array<int, true> The object ids currently armed for writability. */
	private array $_wantWrite = [];

	/** @var array<int, array{when: float, interval: ?float, callback: callable}> Timers, keyed by timer id. */
	private array $_timers = [];

	/** @var int The next timer id. */
	private int $_nextTimerId = 1;

	/** @var bool Whether {@see run()} is looping. */
	private bool $_running = false;

	/**
	 * Registers a source with the loop.  Re-registering the same source replaces its callbacks.
	 * @param IResource $source The server or connection to watch.
	 * @param ?callable $onReadable Called with the source when it is readable; null to not watch reads.
	 * @param ?callable $onWritable Called with the source when it is writable (only while {@see wantWrite() armed}).
	 * @param ?callable $onExcept Called with the source when out-of-band data arrives.
	 */
	public function register(IResource $source, ?callable $onReadable = null, ?callable $onWritable = null, ?callable $onExcept = null): void
	{
		$this->_sources[spl_object_id($source)] = [
			'source' => $source,
			'read' => $onReadable,
			'write' => $onWritable,
			'except' => $onExcept,
		];
	}

	/**
	 * Removes a source from the loop.
	 * @param IResource $source The source to remove.
	 */
	public function unregister(IResource $source): void
	{
		$id = spl_object_id($source);
		unset($this->_sources[$id], $this->_wantWrite[$id]);
	}

	/**
	 * Indicates whether a source is registered.
	 * @param IResource $source The source to test.
	 * @return bool Whether the source is registered.
	 */
	public function isRegistered(IResource $source): bool
	{
		return isset($this->_sources[spl_object_id($source)]);
	}

	/**
	 * Arms or disarms a source for writability.  A writable socket is almost always ready, so a
	 * source is watched for writes only while armed: arm it to finish a connect, advance a TLS
	 * handshake, or flush buffered output, and disarm it once the output drains.
	 * @param IResource $source The registered source.
	 * @param bool $want Whether to watch the source for writability.
	 */
	public function wantWrite(IResource $source, bool $want = true): void
	{
		$id = spl_object_id($source);
		if ($want) {
			$this->_wantWrite[$id] = true;
		} else {
			unset($this->_wantWrite[$id]);
		}
	}

	/**
	 * Returns the number of registered sources.
	 * @return int The number of registered sources.
	 */
	public function getSourceCount(): int
	{
		return count($this->_sources);
	}

	/**
	 * Watches a listening server and folds its accepted connections into the loop, so the
	 * accept-then-register path is one call.  This is sugar over {@see register()}: the server is set
	 * non-blocking and registered with a readable callback that {@see TSocketServer::accept() accepts}
	 * each pending connection, sets it non-blocking, and registers it with $onData as its readable
	 * callback.  Nothing is attached to the server, so {@see unregister()} undoes it by removing the
	 * listener.
	 * @param TSocketServer $server The listening server.
	 * @param callable $onData Called with each accepted connection when it has bytes to read.
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

	/**
	 * Schedules a one-shot callback at an absolute time.
	 * @param float $when The {@see microtime()} timestamp to fire at.
	 * @param callable $callback The callback to run.
	 * @return int The timer id, for {@see cancelTimer()}.
	 */
	public function scheduleAt(float $when, callable $callback): int
	{
		$id = $this->_nextTimerId++;
		$this->_timers[$id] = ['when' => $when, 'interval' => null, 'callback' => $callback];
		return $id;
	}

	/**
	 * Schedules a one-shot callback a delay from now.
	 * @param float $delay The seconds to wait.
	 * @param callable $callback The callback to run.
	 * @return int The timer id, for {@see cancelTimer()}.
	 */
	public function after(float $delay, callable $callback): int
	{
		return $this->scheduleAt(microtime(true) + $delay, $callback);
	}

	/**
	 * Schedules a repeating callback every interval, starting one interval from now.
	 * @param float $interval The seconds between runs.
	 * @param callable $callback The callback to run.
	 * @return int The timer id, for {@see cancelTimer()}.
	 */
	public function every(float $interval, callable $callback): int
	{
		$id = $this->_nextTimerId++;
		$this->_timers[$id] = ['when' => microtime(true) + $interval, 'interval' => $interval, 'callback' => $callback];
		return $id;
	}

	/**
	 * Cancels a scheduled timer.
	 * @param int $id The timer id returned by {@see scheduleAt()}/{@see after()}/{@see every()}.
	 */
	public function cancelTimer(int $id): void
	{
		unset($this->_timers[$id]);
	}

	/**
	 * Runs one iteration: waits for readiness up to the timeout (or the next timer, whichever is
	 * sooner), fires due timers, then dispatches each ready source to its callback.  Sources whose
	 * socket has closed are pruned first.
	 * @param ?float $timeout The maximum seconds to wait; null blocks until activity or a timer.
	 * @return int The number of ready sources, or 0 on timeout or select error.
	 */
	public function tick(?float $timeout = null): int
	{
		$this->pruneClosed();

		$read = $write = $except = [];
		foreach ($this->_sources as $id => $entry) {
			if ($entry['read'] !== null) {
				$read[$id] = $entry['source'];
			}
			if ($entry['write'] !== null && isset($this->_wantWrite[$id])) {
				$write[$id] = $entry['source'];
			}
			if ($entry['except'] !== null) {
				$except[$id] = $entry['source'];
			}
		}

		$delay = $this->resolveTimeout($timeout);
		$ready = [$read, $write, $except];
		if ($read || $write || $except) {
			$r = $read ?: null;
			$w = $write ?: null;
			$e = $except ?: null;
			[$seconds, $microseconds] = $this->splitDelay($delay);
			$count = TSocketServer::select($r, $w, $e, $seconds, $microseconds);
			$ready = [$r ?? [], $w ?? [], $e ?? []];
			$count = $count === false ? 0 : $count;
		} else {
			// Nothing to select on; honor the delay so pending timers still fire on schedule.
			if ($delay !== null && $delay > 0) {
				usleep((int) ($delay * 1000000));
			}
			$count = 0;
		}

		$this->runDueTimers();
		$this->dispatch($ready[0], 'read');
		$this->dispatch($ready[1], 'write');
		$this->dispatch($ready[2], 'except');
		return $count;
	}

	/**
	 * Loops {@see tick()} until {@see stop()} is called.
	 * @param ?float $tickTimeout The per-tick timeout; a finite value keeps {@see stop()} responsive.
	 */
	public function run(?float $tickTimeout = null): void
	{
		$this->_running = true;
		while ($this->_running) {
			$this->tick($tickTimeout);
		}
	}

	/**
	 * Stops the {@see run()} loop after the current tick.
	 */
	public function stop(): void
	{
		$this->_running = false;
	}

	/**
	 * Indicates whether the {@see run()} loop is active.
	 * @return bool Whether the loop is running.
	 */
	public function isRunning(): bool
	{
		return $this->_running;
	}

	/**
	 * Dispatches ready sources to a callback slot, skipping any unregistered by a prior callback.
	 * @param array<int, IResource> $ready The ready sources, keyed by object id.
	 * @param string $slot The callback slot ('read', 'write' or 'except').
	 */
	private function dispatch(array $ready, string $slot): void
	{
		foreach ($ready as $id => $source) {
			$callback = $this->_sources[$id][$slot] ?? null;
			if ($callback !== null) {
				$callback($source);
			}
		}
	}

	/**
	 * Removes sources whose underlying socket has closed.
	 */
	private function pruneClosed(): void
	{
		foreach ($this->_sources as $id => $entry) {
			if (!is_resource($entry['source']->getResource())) {
				unset($this->_sources[$id], $this->_wantWrite[$id]);
			}
		}
	}

	/**
	 * Runs and reschedules timers whose deadline has passed.
	 */
	private function runDueTimers(): void
	{
		if ($this->_timers === []) {
			return;
		}
		$now = microtime(true);
		foreach ($this->_timers as $id => $timer) {
			if ($timer['when'] > $now || !isset($this->_timers[$id])) {
				continue;
			}
			if ($timer['interval'] !== null) {
				$this->_timers[$id]['when'] = $now + $timer['interval'];
			} else {
				unset($this->_timers[$id]);
			}
			($timer['callback'])();
		}
	}

	/**
	 * Resolves the wait to the smaller of the caller's timeout and the nearest timer.
	 * @param ?float $timeout The caller's maximum wait, or null to block.
	 * @return ?float The seconds to wait, or null to block.
	 */
	private function resolveTimeout(?float $timeout): ?float
	{
		$deadline = null;
		foreach ($this->_timers as $timer) {
			if ($deadline === null || $timer['when'] < $deadline) {
				$deadline = $timer['when'];
			}
		}
		if ($deadline === null) {
			return $timeout;
		}
		$timerDelay = max(0.0, $deadline - microtime(true));
		return $timeout === null ? $timerDelay : min($timeout, $timerDelay);
	}

	/**
	 * Splits a float-second delay into the integer seconds and microseconds {@see select()} takes.
	 * @param ?float $delay The seconds to wait, or null to block.
	 * @return array{0: ?int, 1: int} The seconds (null to block) and microseconds.
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
