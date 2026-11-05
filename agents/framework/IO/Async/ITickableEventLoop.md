# IO/Async/ITickableEventLoop

### Directories
[framework](../../INDEX.md) / [IO](../INDEX.md) / [Async](./INDEX.md) / **`ITickableEventLoop`**

## Class Info
**Location:** `framework/IO/Async/ITickableEventLoop.php`
**Namespace:** `Prado\IO\Async`
**Extends:** [`IEventLoop`](IEventLoop.md)
**Since:** 4.4.0

## Overview
The optional capability of an `IEventLoop` that runs one iteration on demand. `tick()` is a test affordance; production code waits through a `Suspension`. [`TSocketReactor`](../Socket/TSocketReactor.md) implements it. [`TRevoltEventLoop`](TRevoltEventLoop.md) does not: the Revolt driver has no public single-iteration entry point.

## Methods

| Method | Description |
|--------|-------------|
| `tick(?float $timeout = null): int` | Waits for readiness up to `$timeout` or the next timer, whichever is sooner, runs the due callbacks, and returns the number of ready streams. An empty loop returns at once on a null timeout. |
| `isEmpty(): bool` | Whether no enabled callback remains, so `run()` returns. |

## Notes
- [`TLoopSuspension`](TLoopSuspension.md) is built on these two methods: the main fiber ticks until resumed and throws an `\Error` when the loop empties first.
