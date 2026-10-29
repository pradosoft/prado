# IO/Async/TAsync

### Directories
[framework](../../INDEX.md) / [IO](../INDEX.md) / [Async](./INDEX.md) / **`TAsync`**

## Class Info
**Location:** `framework/IO/Async/TAsync.php`
**Namespace:** `Prado\IO\Async`
**Type:** final class, static facade
**Since:** 4.4.0

## Overview
The static facade of the async API. `Loop` is the process-wide [`IEventLoop`](IEventLoop.md) every primitive waits on: a [`TRevoltEventLoop`](TRevoltEventLoop.md) unless `setLoop()` installs another, such as a `TSocketReactor` in a test.

| Method | Description |
|---|---|
| `getLoop(): IEventLoop` / `setLoop(?IEventLoop)` | The loop. Null returns to the default on the next `getLoop()`. |
| `run(callable $fn, mixed ...$args): TFuture` | Starts `$fn` in a new fiber at once (it runs until its first suspend or return) with a copy of the caller's [`TFiberContext`](TFiberContext.md); the return value resolves the future, an uncaught exception rejects it. |
| `await(TFuture $f, ?TCancellationToken $token = null): mixed` | `$f->await($token)`. |
| `all(iterable $futures): TFuture` | Resolves with the values keyed as the input once all resolve; rejects with the first rejection. Empty input resolves `[]`. |
| `any(iterable $futures): TFuture` | Resolves with the first value; rejects with the last rejection once all reject; empty input rejects (`async_any_empty`). |
| `timeout(TFuture $f, float $seconds): TFuture` | Takes the input's result, or rejects with `TCancelledException('async_timeout')` when the deadline passes first; the input stays pending and the timer is cancelled on completion. |
| `sleep(float\|int $seconds): void` | Pauses the current fiber on the loop. Non-positive returns at once. |
| `resetLoopForChild(): void` | For a forked child: `TRevoltEventLoop::installFreshDriver()`, and a non-Revolt `Loop` is replaced by a new instance of its class. Called by `TProcessHelper::fork()`. |

## Sleep policy
`\usleep()` appears in the framework only inside `TAsync::sleep()`, as the fallback where no fiber switch is possible. Every other pause calls `TAsync::sleep()` or `IClock::sleep()`, which delegates to it:

| Site | Pause |
|---|---|
| `TClockTrait::sleep()` (and so `TNativeClock`, `TClockDecorator`, `TOffsetClock`, `TTimezoneDecorator`, `TUTCClock`) | `TAsync::sleep()` |
| `TMockClock::sleep()` | pinned: advances the pin; unpinned: `TAsync::sleep()` |
| `TProcessCronTask::startProcess()` launch grace | `TAsync::sleep(LAUNCH_GRACE_USEC / 1e6)` |
| `TProcess::wait()` | a suspension on the loop: `SIGCHLD` plus a repeating status poll at the poll interval |
| `TSocketReactor::tick()` idle | `select()` on the self-pipe, never the clock's `sleep()`, so the reactor does not re-enter itself through `TAsync` |
| `TActivePager` `usleep(1)` | not a pause (forces distinct `microtime()` boundaries); out of scope |

From a worker fiber `sleep()` yields to the loop. From the main fiber it runs the loop until the timer fires, which also runs the other due callbacks. From the main fiber inside a destructor or a GC run (no fiber switch on PHP 8.2 and 8.3) a probe fiber fails to start and the pause is a plain `\usleep()`; a worker fiber's failed `Fiber::suspend()` throws `FiberError`, the suspension resets, and the pause is a plain `\usleep()`. The probe matters: a Revolt main suspension left pending by a `FiberError` would poison every later main-fiber wait.

## Gotchas
- `run()` starts the fiber synchronously, so a body with no suspend completes before `run()` returns.
- `all()`/`any()`/`timeout()` callbacks run on whichever fiber settles the inputs.
- `TAsync::getLoop()` is a static; tests that inject a reactor call `TAsync::setLoop(null)` in `tearDown()`.
