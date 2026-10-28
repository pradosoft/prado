# IO/Async/TFiberContext

### Directories
[framework](../../INDEX.md) / [IO](../INDEX.md) / [Async](./INDEX.md) / **`TFiberContext`**

## Class Info
**Location:** `framework/IO/Async/TFiberContext.php`
**Namespace:** `Prado\IO\Async`
**Type:** final class, static API
**Since:** 4.4.0

## Overview
Per-fiber state: a `WeakMap<\Fiber, array>` keyed by the current fiber, plus an array for the main fiber. [`TAsync::run()`](TAsync.md) copies the parent's context into the new fiber at start, so a value set before the fiber starts is visible inside it and a change inside stays inside. A fiber started with `new \Fiber()` directly starts empty.

| Method | Description |
|---|---|
| `get(string $key, mixed $default = null): mixed` / `has(string $key): bool` | Read. |
| `set(string $key, mixed $value): void` / `remove(string $key): void` | Write. |
| `all(): array` / `replace(array $values): void` / `clear(): void` | The whole map. |

## Planned uses
- `user`: the user a cron task runs as; `TApplication::getUser()` consults it (PR `async-cron`).
- A per-task log category.
