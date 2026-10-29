# IO/Async/TChannel

### Directories
[framework](../../INDEX.md) / [IO](../INDEX.md) / [Async](./INDEX.md) / **`TChannel`**

## Class Info
**Location:** `framework/IO/Async/TChannel.php`
**Namespace:** `Prado\IO\Async`
**Extends:** `Prado\TComponent`
**Implements:** `IteratorAggregate`, `Countable`
**Since:** 4.4.0

## Overview
An ordered queue between fibers. `send()` gives the value to the longest waiting receiver, else buffers it while the buffer has room, else suspends the sender. `receive()` takes the next buffered value (admitting a waiting sender into the freed slot), else a waiting sender's value, else suspends the receiver. Every wait is a suspension on [`TAsync::getLoop()`](TAsync.md): a worker fiber yields, the main fiber runs the loop.

| Capacity | Meaning |
|---|---|
| `null` (default) | unbounded; a sender never waits |
| `0` | rendezvous; every send waits for its receive |
| `n` | `n` buffered values, then senders wait |

```php
$channel = new TChannel(16);
TAsync::run(function () use ($channel) {
    foreach ($rows as $row) {
        $channel->send($row);
    }
    $channel->close();
});
foreach ($channel as $row) {   // receive() until closed and drained
    $this->handle($row);
}
```

## Methods

| Method | Description |
|---|---|
| `__construct(?int $capacity = null)` | Negative capacity: `TInvalidDataValueException('async_channel_capacity_invalid')`. |
| `send(mixed $value, ?TCancellationToken $token = null): void` | Waits for room. Closed: `TInvalidOperationException('async_channel_closed')`. |
| `trySend(mixed $value): bool` | No wait; false when the buffer is full. |
| `receive(?TCancellationToken $token = null): mixed` | Waits for a value. Closed and drained: `TInvalidOperationException`. |
| `tryReceive(mixed &$value): bool` | No wait; false when nothing is available. |
| `close(): void` | Idempotent. Buffered values stay receivable; waiting senders and receivers are released with `TInvalidOperationException`. |
| `isClosed()`, `isEmpty()`, `count()`, `getCapacity()` | State. `isEmpty()` is false while a sender waits (rendezvous); `count()` is the buffered values only. |
| `getIterator(): \Generator` | Receives until closed and drained. |

## Gotchas
- A resume or throw of a waiter is queued on the loop, so a future that waits on the channel completes only once the loop runs; await it, do not inspect it right after `send()` or `close()`.
- A cancelled token throws its `TCancelledException` from the wait and removes the waiter; a cancelled sender's value is not sent.
- Waiters are served in order of arrival, senders and receivers alike.
