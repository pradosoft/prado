# Web/Services/TServerEventMemoryBroker

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [Services](./INDEX.md) / **`TServerEventMemoryBroker`**

## Class Info
**Location:** `framework/Web/Services/TServerEventMemoryBroker.php`
**Namespace:** `Prado\Web\Services`
**Extends:** [`TServerEventBroker`](TServerEventBroker.md)
**Since:** 4.4.0

## Overview
Keeps each channel's state in the process. `receive()` returns buffered events at once; otherwise it registers a `TDeferred` waiter and awaits `TAsync::timeout()` of it, so a worker fiber yields to the [loop](../../IO/Async/INDEX.md) and the main fiber runs it. `publish()` resolves the channel's waiters, which then read the new events. `getWaiterCount($channel)` reports the waiting receivers.

```php
$broker = new TServerEventMemoryBroker();
$future = TAsync::run(fn () => $broker->receive('orders', '', 30.0));
$broker->publish('orders', 'changed', ['id' => 7]);
$events = $future->await();
```

## Gotchas
- It reaches only streams of the same process: a long-running event-loop process that hosts the streams in fibers and publishes from the same process, and tests. Under PHP-FPM, mod_php, or the built-in server each request is its own process; use `TServerEventCacheBroker` there.
- PRADO has no in-loop HTTP host; `TSocketServerModule` serves raw sockets. A host that serves `TServerEventService` from fibers is the application's.
- A channel whose events have all expired is dropped when next read, so idle channels hold no memory.
