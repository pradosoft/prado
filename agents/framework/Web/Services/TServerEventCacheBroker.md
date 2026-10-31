# Web/Services/TServerEventCacheBroker

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [Services](./INDEX.md) / **`TServerEventCacheBroker`**

## Class Info
**Location:** `framework/Web/Services/TServerEventCacheBroker.php`
**Namespace:** `Prado\Web\Services`
**Extends:** [`TServerEventBroker`](TServerEventBroker.md)
**Uses:** `TCacheModuleIDTrait`
**Since:** 4.4.0

## Overview
Keeps each channel's state in one `ICache` entry (`CACHE_KEY_PREFIX . channel`, expire `EventLifetime`), so an event published by one PHP process reaches the streams of the others. `receive()` reads the entry, then sleeps `min(PollInterval, remaining)` through the clock until an event follows the ID or the timeout passes. The clock's `sleep()` yields to the event loop in a fiber.

```xml
<module id="cache" class="Prado\Caching\TRedisCache" />
<module id="serverevents" class="Prado\Web\Services\TServerEventCacheBroker" PollInterval="0.5" />
```

| Property | Default | Notes |
|---|---|---|
| `CacheModuleID` | `''` | the application's primary cache when empty |
| `PollInterval` | 1.0 | seconds; > 0 (`servereventcachebroker_pollinterval_invalid`) |
| `BufferSize`, `EventLifetime` | 100, 300 | from the base |

## Publish lock
`storeEvent()` claims `<key>:lock` with `claimCacheKey()` (`ICache::add()`, TTL `LOCK_TTL` 2 s), retrying `LOCK_ATTEMPTS` (50) times every `LOCK_RETRY_DELAY` (10 ms), then reads, appends, writes, and deletes the lock. A lock it could not claim is left alone and the publish proceeds without it.

## Gotchas
- The claim is atomic on APCu, Memcache, Redis, and etcd; on `TFileCache` and `TDbCache` two concurrent publishes on one channel can lose an event.
- A per-process cache (APCu across FPM pools, `TMemoryCache`) does not carry events between processes.
- Each open stream reads the cache every `PollInterval`; the read rate is streams / PollInterval.
- `getCache()` is the seam tests override.
