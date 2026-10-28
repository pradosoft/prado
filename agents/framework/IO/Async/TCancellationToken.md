# IO/Async/TCancellationToken

### Directories
[framework](../../INDEX.md) / [IO](../INDEX.md) / [Async](./INDEX.md) / **`TCancellationToken`**

## Class Info
**Location:** `framework/IO/Async/TCancellationToken.php`
**Namespace:** `Prado\IO\Async`
**Extends:** `Prado\TComponent`
**Since:** 4.4.0

## Overview
Cooperative cancellation. A token is cancelled once: by the holder (`cancel()`), by its `Timeout` elapsing on [`TAsync::getLoop()`](TAsync.md), or by a parent token it was constructed with. Waiting code passes it to [`TFuture::await()`](TFuture.md), calls `throwIfCancelled()` at checkpoints, or subscribes.

```php
$token = new TCancellationToken(5.0);          // cancels itself after 5 s
$child = new TCancellationToken(null, $token); // cancels when $token does
$future->await($child);                        // throws TCancelledException on cancel
```

## Methods

| Method | Description |
|---|---|
| `__construct(?float $timeout = null, TCancellationToken ...$parents)` | Timeout in seconds registers a `delay()` on the loop; each parent is linked. A cancelled parent cancels at once. |
| `getTimeout(): ?float` | The timeout. |
| `isCancelled(): bool` / `getReason(): ?TCancelledException` / `throwIfCancelled(): void` | State. |
| `cancel(?\Throwable $reason = null): void` | Idempotent. A `TCancelledException` is the reason as is; another exception becomes the previous of `TCancelledException('async_cancelled')`; null gives the plain reason. Cancels the timer and parent subscriptions, raises `onCancel`, then calls the subscribers. |
| `onCancel(mixed $param)` | Event, raised once with the reason. |
| `subscribe(callable $cb): string` / `unsubscribe(string $id)` | `$cb(TCancelledException $reason)`; a cancelled token calls it at once. |

## Internals
- The timeout closure and the parent subscription hold a `WeakReference` to the token, so a dropped token is collected; `__destruct` cancels the timer and parent subscriptions (no fiber switch).
- A timeout cancels with `TCancelledException('async_timeout', $seconds)`.
