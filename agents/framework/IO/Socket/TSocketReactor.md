# IO/Socket/TSocketReactor

### Directories
[framework](../../INDEX.md) / [IO](../INDEX.md) / [Socket](./INDEX.md) / **`TSocketReactor`**

## Class Info
**Location:** `framework/IO/Socket/TSocketReactor.php`
**Namespace:** `Prado\IO\Socket`
**Extends:** `Prado\TComponent`
**Implements:** [`ITickableEventLoop`](../Async/ITickableEventLoop.md) (so [`IEventLoop`](../Async/IEventLoop.md))
**Uses:** `TClockAwareTrait`
**Since:** 4.4.0

## Overview
A single-threaded event loop over one `TSocketServer::select()` that needs no extension beyond PHP's streams. It is the dependency-free fallback to [`TRevoltEventLoop`](../Async/TRevoltEventLoop.md) and the loop whose `tick()` a test drives one iteration at a time.

## Tick

`tick(?float $timeout)`:
1. `pruneClosed()` cancels stream callbacks whose resource closed and forgets their sources.
2. `runDeferred()` runs the callbacks deferred before this tick (a defer made by one of them runs next tick; a throwing one leaves the rest queued).
3. An empty loop returns 0 on a null timeout.
4. `pcntl_signal_dispatch()` when signal callbacks exist.
5. `select()` over the enabled stream callbacks plus the self-pipe reader, for the smaller of the timeout and the nearest enabled timer (0 when deferred callbacks or caught signals wait).
6. Drains the self-pipe, runs the caught signals' callbacks, the due timers in deadline order, then the ready streams (readable, writable, except).

`run()` ticks until `stop()` or `isEmpty()`; a `run()` from a callback nests and `stop()` ends the innermost. `isRunning()` is true inside any run.

## Callback table

| Field | Meaning |
|---|---|
| `type` | `TYPE_DEFER`, `TYPE_DELAY`, `TYPE_REPEAT`, `TYPE_READABLE`, `TYPE_WRITABLE`, `TYPE_EXCEPT`, `TYPE_SIGNAL` |
| `callback` | the closure, called `(string $id)`, `(string $id, mixed $stream)`, or `(string $id, int $signal)` |
| `enabled` | `enable()`/`disable()`; a disabled callback does not run or keep `run()` alive |
| `stream` | the resource or `IResource` as registered |
| `delay` / `when` | the timer interval and its next deadline on the time base |
| `signal` | the signal number |

Identifiers are decimal strings from one counter. `cancel()` of an unknown id is a no-op; `enable()`/`disable()` of one throws `TInvalidDataValueException('eventloop_callback_invalid')`.

## Time base
`now()` reads the injected `IClock::microtime()` when `setClock()` was called, else `hrtime()`. `setClock()` re-bases pending timers so each keeps its remaining delay; `getClock()` creates the default clock through `setClock()` and so re-bases too. A pinned `TMockClock` therefore advances reactor timers; `TMockClock::sleep()` or `setMicrotime()` plus `tick(0)` fires them deterministically.

## Source API (sugar over the callbacks)

| Method | Description |
|---|---|
| `register(IResource $source, ?callable $onReadable, ?callable $onWritable, ?callable $onExcept)` | Up to three callbacks on one source; re-registering replaces them. The writable callback starts disabled. |
| `unregister(IResource)` | Cancels the source's callbacks. |
| `isRegistered(IResource)` / `getSourceCount()` | Sources registered through `register()` only. |
| `wantWrite(IResource, bool $want = true)` | Enables or disables the source's writable callback. |
| `registerServer(TSocketServer, callable $onData)` | Accepts each pending connection non-blocking and registers it with `$onData` as its readable callback. |
| `onExcept(mixed $stream, callable)` | Out-of-band readiness, reactor only. |

## Signals
`onSignal()` installs the reactor's `pcntl_signal()` handler for the signal, remembering the prior handler (`pcntl_signal_get_handler()`), and restores it when the last callback for that signal is cancelled or the reactor is destroyed. The handler marks the signal pending and wakes the self-pipe; the callbacks run from `tick()`. Without pcntl `onSignal()` throws `TNotSupportedException('eventloop_signals_unsupported')`. While the reactor watches a signal, `TSignalsDispatcher` does not receive it.

## Self-pipe
A `TSocketStream::pair()` created on the first tick. The reader joins every `select()`; `wake()` writes a byte only while `select()` is in progress (`_selecting`), which happens from a signal handler calling `defer()`, `enable()`, a timer registration, or `stop()`. A failed write means a wake is already pending.

## Suspension
`suspension()` returns a [`TLoopSuspension`](../Async/TLoopSuspension.md) per fiber (`WeakMap` for workers, one object for main).

## Gotchas
- Callbacks run on the fiber that ticks; a worker fiber resumed from a deferred callback runs inside that callback.
- `register()` callbacks receive `(string $id, IResource $source)`; a `fn () =>` closure ignores both.
- `pruneClosed()` is per tick: a resource closed inside a callback is cancelled on the next tick, and `TSocketServer::select()` skips closed resources meanwhile.
- No fiber switch inside `__destruct`; it only closes the self-pipe and restores signal handlers.
