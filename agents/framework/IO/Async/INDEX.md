# IO/Async/INDEX.md

### Directories
[framework](../../INDEX.md) / [IO](../INDEX.md) / **`Async`**

## Purpose

PRADO's async API. A loop schedules callbacks on deferred work, timers, stream readiness, and signals, and a fiber waits on it through a `Revolt\EventLoop\Suspension`. The `revolt/event-loop` package (required, MIT, no dependencies) runs the default loop; `TSocketReactor` is the dependency-free fallback. On the loop sit the primitives: `TFuture`/`TDeferred`, `TCancellationToken`, `TFiberContext`, and the `TAsync` facade. Async I/O, `TChannel`, and the async HTTP client [`TCurlMultiHttpClient`](../HttpClient/TCurlMultiHttpClient.md) build on this contract; cron concurrency follows.

## Interfaces

- **[`IEventLoop`](./IEventLoop.md)** — The loop contract: `defer`, `delay`, `repeat`, `onReadable`, `onWritable`, `onSignal`, `enable`, `disable`, `cancel`, `getIdentifiers`, `suspension`, `run`, `stop`, `isRunning`, `ErrorHandler`. Every registration returns a string identifier.
- **[`ITickableEventLoop`](./ITickableEventLoop.md)** — Adds `tick(?float $timeout): int` and `isEmpty(): bool`, the single-iteration test affordance. Implemented by [`TSocketReactor`](../Socket/TSocketReactor.md), not by `TRevoltEventLoop`.

## Classes

- **[`TRevoltEventLoop`](./TRevoltEventLoop.md)** — The default `IEventLoop`, a thin adapter over the process-wide `Revolt\EventLoop` driver. Discards callback return values, unwraps `UncaughtThrowable` from `run()`, maps Revolt's errors to PRADO exceptions. `installFreshDriver()` for forked children.
- **[`TLoopSuspension`](./TLoopSuspension.md)** — The `Suspension` of an `ITickableEventLoop`: a worker fiber suspends to the loop and is re-entered from a deferred callback; the main fiber ticks the loop until resumed.
- **[`TFuture`](./TFuture.md)** — A value that arrives later; `await()` suspends on the loop, `map`/`catch`/`finally` derive futures, `onComplete` registers callbacks. Created by `TDeferred`, `TAsync::run()`, the factories, or an executor.
- **[`TDeferred`](./TDeferred.md)** — The producer side: `getFuture()`, `resolve()`, `reject()`, once.
- **[`TCancellationToken`](./TCancellationToken.md)** — Cooperative cancellation with a loop `Timeout` and linked parents; `cancel()`, `onCancel`, `subscribe()`, `throwIfCancelled()`; the reason is a `TCancelledException`.
- **[`TFiberContext`](./TFiberContext.md)** — Per-fiber key/value state in a `WeakMap`; `TAsync::run()` copies the parent's context into the child.
- **[`TAsync`](./TAsync.md)** — Static facade: `getLoop()`/`setLoop()`, `run()`, `await()`, `all()`, `any()`, `timeout()`, `sleep()`, `resetLoopForChild()`. Holds the sleep policy table.
- **[`TAsyncStream`](./TAsyncStream.md)** — A `TStreamDecorator` whose `read()`/`write()` wait for readiness on the loop; `''` only at end of stream, whole-string writes, optional `TCancellationToken`.
- **[`TChannel`](./TChannel.md)** — An ordered queue between fibers: `send()`/`receive()` suspend on the loop, a `Capacity` of null, 0 (rendezvous), or `n`; `close()` drains and releases waiters; iterable.

## Process waits

`TProcess::wait()` and `TProcessFork::wait()` suspend the current fiber on the loop. The exit is detected by `SIGCHLD` through `TProcessHelper::watchChildSignal()` and by a repeating status poll (`TProcess`: the poll interval; `TProcessFork::EXIT_POLL_INTERVAL`, 10 ms), which also covers a platform without signals. A channel-backed fork drains its channel through `onReadable()` and detects the exit by the channel's end of stream first.

`watchChildSignal()` keeps the `TSignalsDispatcher` in charge of `SIGCHLD` when it is installed: the callback is deferred from its `fxSignalChild` handler to the next loop iteration, because that handler runs inside `pcntl_signal_dispatch()`, which Revolt calls from within its dispatch phase. Loop work done there (a cancel, a resume) is not acted on until the current wait ends, and with no timer left Revolt sleeps indefinitely. The same rule holds for `TProcessFork::captureExit()`, which may run from the dispatcher's PID handler: it raises `onExit` at once and defers the watch removal and the waiter resumes. Without the dispatcher the callback is an `onSignal(SIGCHLD)` callback of the loop.

A forked child calls `TAsync::resetLoopForChild()` from `TProcessHelper::fork()`: the Revolt driver is replaced (`TRevoltEventLoop::installFreshDriver()`), and a `TAsync` loop of another class is replaced by a new instance, so the child services none of the parent's callbacks and shares no self-pipe.

## The wait pattern

```php
$suspension = $loop->suspension();
$loop->delay(1.0, fn () => $suspension->resume('done'));
$result = $suspension->suspend();   // 'done'
```

Register a one-shot callback that resumes the suspension, then suspend. From a worker fiber the suspend yields to the loop; from the main fiber it runs the loop until the callback fires. A `tick()` loop is never a wait. A bounded slice of the loop from the main fiber is `delay($t, resume)` plus `suspend()`.

## Loop differences

| Aspect | `TRevoltEventLoop` | `TSocketReactor` |
|---|---|---|
| Timer clock | Revolt's monotonic clock; a pinned `TMockClock` does not move it | the injected `IClock`, else `hrtime()`; a pinned `TMockClock` advances timers |
| `run()` from a worker fiber | throws | nests |
| `tick()` | none | `ITickableEventLoop` |
| Callback exception with no handler | leaves `run()` as the original exception | leaves `tick()`/`run()` as the original exception |
| Scope | one process-wide driver shared by every instance | one loop per instance |
| Fork | child calls `TRevoltEventLoop::installFreshDriver()` | child constructs a new reactor |

The conformance suite `tests/unit/IO/Async/EventLoopConformanceTestCase.php` runs against both loops (`TRevoltEventLoopTest`, `TSocketReactorEventLoopTest`). A test that pins a clock under scheduled work uses the reactor.

## Gotchas

- Revolt's `EventLoop::setDriver()` throws while the driver runs, and the driver's loop fiber stays alive after an interrupt (a resumed main suspension, an uncaught exception) until the next `run()` empties it. `installFreshDriver()` handles this; a child forked from inside a callback keeps the inherited driver with every callback cancelled.
- Revolt callbacks must return null; `TRevoltEventLoop` wraps every callback so a non-null return is discarded.
- A Revolt loop whose only enabled callbacks are signal callbacks sleeps indefinitely (`usleep(PHP_INT_MAX)`): `run()` does not return and nothing but a signal ends the wait. Keep a timer beside a signal callback, or `stop()` the loop.
- `TRevoltEventLoop::onSignal()` restores the `pcntl_signal()` handler in place before the first callback of a signal once the last one is cancelled; the driver itself leaves `SIG_DFL`.
- `IEventLoop::run()` returns when no enabled callback remains. A disabled callback does not keep it alive.
- No fiber switch inside `__destruct` on the 8.2 floor.
- Sleep policy: `\usleep()` lives only in `TAsync::sleep()`; `IClock::sleep()` and the framework's other pauses delegate to it (table in [`TAsync`](./TAsync.md)).
- No `await()` or main-fiber `suspend()` inside `__destruct` on the 8.2 floor; `TAsync::sleep()` probes for it and blocks instead.
