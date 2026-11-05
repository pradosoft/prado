# IO/Async/TFuture

### Directories
[framework](../../INDEX.md) / [IO](../INDEX.md) / [Async](./INDEX.md) / **`TFuture`**

## Class Info
**Location:** `framework/IO/Async/TFuture.php`
**Namespace:** `Prado\IO\Async`
**Extends:** `Prado\TComponent`
**Since:** 4.4.0

## Overview
A value that arrives later. A future completes once, with a value or an exception. `await()` suspends the current fiber on [`TAsync::getLoop()`](TAsync.md) until then: a worker fiber yields to the loop, the main fiber runs the loop. A complete future returns or throws at once.

Created by [`TDeferred`](TDeferred.md) (the producer side), by [`TAsync::run()`](TAsync.md) (a fiber's result), by the static factories, or by the constructor's executor.

## Methods

| Method | Description |
|---|---|
| `__construct(?callable $executor = null)` | The executor receives `$resolve(mixed $value = null)` and `$reject(\Throwable $e)`; an exception it throws rejects the future. |
| `static complete(mixed $value = null): static` / `static error(\Throwable $e): static` | Complete futures. |
| `isComplete(): bool` / `isResolved(): bool` / `isRejected(): bool` | State. |
| `await(?TCancellationToken $token = null): mixed` | Waits for the result. A cancelled token throws its `TCancelledException` from the wait and leaves the future pending; a later `await()` still gets the result. |
| `onComplete(callable $cb): static` | `$cb(?\Throwable $error, mixed $value)`, in registration order, on the fiber that settles the future; at once when already complete. |
| `map(callable $mapper): TFuture` | Derived future from the value; a rejection passes through; a throwing mapper rejects. No flattening of a returned future. |
| `catch(callable $handler): TFuture` | Derived future that resolves with `$handler($error)`; a value passes through. |
| `finally(callable $cb): TFuture` | Runs `$cb()` and passes the result through; a throwing callback rejects. |

## Internals
- `settle()` is private; the constructor's executor is the only way to complete a future from outside, which is how `TDeferred` holds its resolver.
- The class is `@phpstan-consistent-constructor`: `complete()` and `error()` use `new static()`, so a subclass keeps the constructor signature.
- `await()` guards its suspension with a `$settled` flag, so a completion that arrives after a cancellation does not resume a reused suspension.

## Gotchas
- Callbacks run synchronously inside `resolve()`/`reject()`; an exception a plain `onComplete()` callback throws propagates to the settler. The derived-future methods catch their own callbacks.
- No `await()` inside `__destruct` on the 8.2 floor: no fiber switch is possible there and there is no blocking fallback for a value.
