# IO/Async/TRevoltEventLoop

### Directories
[framework](../../INDEX.md) / [IO](../INDEX.md) / [Async](./INDEX.md) / **`TRevoltEventLoop`**

## Class Info
**Location:** `framework/IO/Async/TRevoltEventLoop.php`
**Namespace:** `Prado\IO\Async`
**Extends:** `Prado\TComponent`
**Implements:** [`IEventLoop`](IEventLoop.md)
**Since:** 4.4.0
**Requires:** `revolt/event-loop` (^1.0)

## Overview
The default `IEventLoop`: a thin adapter over the process-wide `Revolt\EventLoop` static driver. Revolt picks `ext-uv`, `ext-ev`, or `ext-event` when installed, else its `stream_select()` driver. Every instance addresses the same driver, so two instances share one set of callbacks and one `run()`.

## Adapter behavior

| Concern | Behavior |
|---|---|
| Callbacks | Wrapped so a return value is discarded (Revolt rejects a non-null return with `InvalidCallbackError`). |
| `IResource` streams | Unwrapped to the PHP stream for the driver; the callback receives the object as registered. |
| Errors | `InvalidCallbackError` → `TInvalidDataValueException('eventloop_callback_invalid')`; `UnsupportedFeatureException` on `onSignal()` → `TNotSupportedException('eventloop_signals_unsupported')`; a negative delay → `TInvalidDataValueException('eventloop_delay_invalid')`. |
| `run()` | `EventLoop::run()`; an `UncaughtThrowable` is unwrapped and the original exception rethrown. Throws `\Error` from a worker fiber. |
| `isRunning()` | True while a `run()` of this class is in progress or the current fiber is a driver callback. The driver's own `isRunning()` stays true after an interrupt until the next run, so it is not used alone. |
| `suspension()` | `EventLoop::getSuspension()`. |
| `ErrorHandler` | `EventLoop::setErrorHandler()` / `getErrorHandler()`. |

## Static

| Method | Description |
|---|---|
| `installFreshDriver(): void` | For a forked child. Cancels the inherited driver's callbacks and unsets its error handler; outside a fiber it then runs the driver once to unwind its loop fiber and installs a new `DriverFactory` driver. Inside a fiber `setDriver()` is refused, so the emptied inherited driver stays. |

## Gotchas
- Timers read Revolt's `hrtime()` clock; an injected `IClock` or a pinned `TMockClock` does not move them. Use `TSocketReactor` for clock-pinned tests.
- After an uncaught exception from `run()` the loop stays usable; the other callbacks remain registered.
- A `{main}` suspension that Revolt reports dead (loop emptied while main was suspended) throws from the next `suspend()`.
