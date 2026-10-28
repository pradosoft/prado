# IO/Async/TDeferred

### Directories
[framework](../../INDEX.md) / [IO](../INDEX.md) / [Async](./INDEX.md) / **`TDeferred`**

## Class Info
**Location:** `framework/IO/Async/TDeferred.php`
**Namespace:** `Prado\IO\Async`
**Extends:** `Prado\TComponent`
**Since:** 4.4.0

## Overview
The producer side of a [`TFuture`](TFuture.md). The producer keeps the deferred and hands out `Future`; `resolve()` or `reject()` completes the future once.

| Method | Description |
|---|---|
| `getFuture(): TFuture` | The future (component property `Future`). |
| `isComplete(): bool` | Whether the future is settled. |
| `resolve(mixed $value = null): void` | Resolves; `TInvalidOperationException('async_deferred_complete')` on a second completion. |
| `reject(\Throwable $e): void` | Rejects; same guard. |

## Notes
- Built on the future's constructor executor: the deferred captures `$resolve`/`$reject` and nothing else can settle the future.
- [`TAsync::all()`](TAsync.md), `any()`, `timeout()`, and `run()` are written on a deferred.
