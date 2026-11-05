# IO/Async/TLoopSuspension

### Directories
[framework](../../INDEX.md) / [IO](../INDEX.md) / [Async](./INDEX.md) / **`TLoopSuspension`**

## Class Info
**Location:** `framework/IO/Async/TLoopSuspension.php`
**Namespace:** `Prado\IO\Async`
**Implements:** `Revolt\EventLoop\Suspension`
**Since:** 4.4.0

## Overview
The `Suspension` of an [`ITickableEventLoop`](ITickableEventLoop.md). [`TSocketReactor::suspension()`](../Socket/TSocketReactor.md) creates one per fiber (a `WeakMap` for worker fibers, one object for the main fiber) and returns the same object for the fiber's lifetime.

| Fiber | `suspend()` | `resume()` / `throw()` |
|---|---|---|
| worker | `\Fiber::suspend()` | queues a `defer()` on the loop that calls `$fiber->resume()` or `$fiber->throw()` when the fiber is still suspended |
| main | ticks the loop with `tick(null)` until settled; throws `\Error` when the loop `isEmpty()` first | records the value or exception; the pump returns after the current tick |

## Methods

| Method | Description |
|--------|-------------|
| `__construct(ITickableEventLoop $loop)` | Binds to the loop and to `\Fiber::getCurrent()` (null for main) through a `WeakReference`. |
| `suspend(): mixed` | Waits for `resume()`/`throw()`. `\Error` from another fiber, when a suspend is pending, or when the loop empties first. |
| `resume(mixed $value = null): void` | Delivers a value. `\Error` when no suspend is pending. |
| `throw(\Throwable $t): void` | Delivers an exception to `suspend()`. `\Error` when no suspend is pending. |
| `isPending(): bool` | Whether `suspend()` is waiting. |

## Gotchas
- The same rules as Revolt's `DriverSuspension`: one pending `suspend()` at a time, `resume()` only while pending.
- A worker fiber resumed from the loop runs inside the deferred callback; an exception it leaves uncaught goes to the loop's error path (the `ErrorHandler`, else out of `tick()`/`run()`).
