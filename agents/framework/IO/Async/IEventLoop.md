# IO/Async/IEventLoop

### Directories
[framework](../../INDEX.md) / [IO](../INDEX.md) / [Async](./INDEX.md) / **`IEventLoop`**

## Class Info
**Location:** `framework/IO/Async/IEventLoop.php`
**Namespace:** `Prado\IO\Async`
**Since:** 4.4.0

## Overview
`IEventLoop` is PRADO's event loop contract. A loop runs callbacks one at a time from `run()`: deferred callbacks on the next iteration, one-shot and repeating timers, stream readiness, and process signals. Every registration returns a string identifier for `enable()`, `disable()`, and `cancel()`. A fiber waits on the loop through `suspension()`.

Implemented by [`TRevoltEventLoop`](TRevoltEventLoop.md) (default) and [`TSocketReactor`](../Socket/TSocketReactor.md) (fallback, also [`ITickableEventLoop`](ITickableEventLoop.md)).

## Methods

| Method | Description |
|--------|-------------|
| `defer(callable $cb): string` | Runs `$cb(string $id)` on the next iteration. |
| `delay(float $delay, callable $cb): string` | Runs `$cb(string $id)` once after `$delay` seconds (≥ 0, else `TInvalidDataValueException`). |
| `repeat(float $interval, callable $cb): string` | Runs `$cb(string $id)` every `$interval` seconds, starting one interval from now. |
| `onReadable(mixed $stream, callable $cb): string` | Runs `$cb(string $id, mixed $stream)` when the stream is readable. `$stream` is a PHP stream resource or an `IResource`; the callback receives it as registered. Several callbacks may watch one stream. |
| `onWritable(mixed $stream, callable $cb): string` | Same for writability. A connected socket is almost always writable: disable or cancel once the output drains. |
| `onSignal(int $signal, callable $cb): string` | Runs `$cb(string $id, int $signal)` from the loop when the process receives the signal. `TNotSupportedException` without pcntl. |
| `enable(string $id): string` | Enables a disabled callback; a timer restarts its delay from now. Unknown id → `TInvalidDataValueException`. |
| `disable(string $id): string` | The callback does not run and does not keep `run()` alive. Unknown id → `TInvalidDataValueException`. |
| `cancel(string $id): void` | Removes the callback; an unknown id is ignored. |
| `getIdentifiers(): string[]` | Every registered callback, enabled or not. |
| `suspension(): Revolt\EventLoop\Suspension` | The current fiber's suspension, one per fiber for its lifetime. |
| `run(): void` | Runs until `stop()` or no enabled callback remains. Called from the main fiber. |
| `stop(): void` | Stops the running loop after the current callback. |
| `isRunning(): bool` | Whether `run()` is in progress. |
| `getErrorHandler(): ?callable` / `setErrorHandler(?callable)` | The handler for exceptions thrown by callbacks, called as `($exception)`. With a handler the loop continues; without one the exception leaves `run()` with the other callbacks still registered. |

## Patterns & Gotchas
- **Wait pattern:** register a one-shot callback that resumes the suspension, then `suspend()`. Never a `tick()` loop.
- **Exception isolation:** a throwing callback never corrupts the loop. One-shot callbacks are removed and repeating timers rescheduled before the callback runs.
- **Callback return values** are ignored by both implementations.
- **`run()` returns when empty**, so a loop that only holds disabled callbacks returns at once.
