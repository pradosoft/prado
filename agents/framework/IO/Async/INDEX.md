# IO/Async/INDEX.md

### Directories
[framework](../../INDEX.md) / [IO](../INDEX.md) / **`Async`**

## Purpose

PRADO's event loop API. A loop schedules callbacks on deferred work, timers, stream readiness, and signals, and a fiber waits on it through a `Revolt\EventLoop\Suspension`. The `revolt/event-loop` package (required, MIT, no dependencies) runs the default loop; `TSocketReactor` is the dependency-free fallback. Futures, `TAsync`, async I/O, cron concurrency, and an async HTTP client follow on this contract.

## Interfaces

- **[`IEventLoop`](./IEventLoop.md)** — The loop contract: `defer`, `delay`, `repeat`, `onReadable`, `onWritable`, `onSignal`, `enable`, `disable`, `cancel`, `getIdentifiers`, `suspension`, `run`, `stop`, `isRunning`, `ErrorHandler`. Every registration returns a string identifier.
- **[`ITickableEventLoop`](./ITickableEventLoop.md)** — Adds `tick(?float $timeout): int` and `isEmpty(): bool`, the single-iteration test affordance. Implemented by [`TSocketReactor`](../Socket/TSocketReactor.md), not by `TRevoltEventLoop`.

## Classes

- **[`TRevoltEventLoop`](./TRevoltEventLoop.md)** — The default `IEventLoop`, a thin adapter over the process-wide `Revolt\EventLoop` driver. Discards callback return values, unwraps `UncaughtThrowable` from `run()`, maps Revolt's errors to PRADO exceptions. `installFreshDriver()` for forked children.
- **[`TLoopSuspension`](./TLoopSuspension.md)** — The `Suspension` of an `ITickableEventLoop`: a worker fiber suspends to the loop and is re-entered from a deferred callback; the main fiber ticks the loop until resumed.

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
- `IEventLoop::run()` returns when no enabled callback remains. A disabled callback does not keep it alive.
- No fiber switch inside `__destruct` on the 8.2 floor.
- `\usleep`/`\sleep` policy: PR 2 (`async-primitives`) routes every pause through `TAsync::sleep()`; this PR does not touch the sleep sites.
