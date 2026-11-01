# Web/Services/ServerEvents/TServerEventCacheBroker

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [Services](../INDEX.md) / [ServerEvents](./INDEX.md) / **`TServerEventCacheBroker`**

## Class Info
**Location:** `framework/Web/Services/ServerEvents/TServerEventCacheBroker.php`
**Namespace:** `Prado\Web\Services\ServerEvents`
**Extends:** [`TServerEventBroker`](TServerEventBroker.md)
**Uses:** `TCacheModuleIDTrait`, [`TMonotonicClockAwareTrait`](../../../Util/Clock/TMonotonicClockAwareTrait.md)
**Since:** 4.4.0

## Overview
Keeps each channel's state in one `ICache` entry (`CACHE_KEY_PREFIX . channel`, expire `EventLifetime`), so an event published by one PHP process reaches the streams of the others. `receive()` reads the entry, then sleeps `min(PollInterval, remaining)` through the clock until an event follows the ID or the timeout passes. The clock's `sleep()` yields to the event loop in a fiber. The timeout is elapsed time on `getMonotonicClock()`; event times and `EventLifetime` stay on the wall clock, which processes share.

```xml
<module id="cache" class="Prado\Caching\TRedisCache" />
<module id="serverevents" class="Prado\Web\Services\ServerEvents\TServerEventCacheBroker" PollInterval="0.5" />
```

| Property | Default | Notes |
|---|---|---|
| `CacheModuleID` | `''` | the application's primary cache when empty |
| `PollInterval` | 1.0 | seconds; > 0 (`servereventcachebroker_pollinterval_invalid`) |
| `MutexID` | `''` | an [`IMutex`](../../../Util/Mutex/IMutex.md) module; empty for the default mutex of the cache (`servereventcachebroker_mutexid_invalid`) |
| `LockTimeout` | 5.0 | seconds a publish waits for the lock; >= 0 (`servereventcachebroker_locktimeout_invalid`) |
| `BufferSize`, `EventLifetime` | 100, 300 | from the base |

## Publish lock
`storeEvent()` acquires `CACHE_KEY_PREFIX . channel` on `getMutex()` within `LockTimeout`, reads the entry, appends the event, writes it, and releases in `finally`. Without the lock in time it throws `TMutexException` (`mutex_acquire_failed`) and stores nothing. Any `IMutex` works; `synchronize()` is not part of the interface.

`getMutex()` resolves once: the `MutexID` module, else `createMutex($cache)`:

| Cache | Default mutex | Excludes |
|---|---|---|
| `TFileCache` | [`TFileMutex`](../../../Util/Mutex/TFileMutex.md), `MutexPath` = cache `Directory` + `/mutex` (`FILE_MUTEX_FOLDER`) | processes of the host |
| `TDbCache` | [`TDbMutex`](../../../Util/Mutex/TDbMutex.md) on the cache's `getDbConnection()`; the driver's server lock, else the `pradomutex` lock table | connections to the database |
| any other `ICache` | [`TCacheMutex`](../../../Util/Mutex/TCacheMutex.md) on the cache (lease 30 s) | clients of the cache; atomic where `add()` is (APCu, Memcache, Redis, etcd) |

`configureMutex()` sets a default mutex's `RetryDelay` to `MUTEX_RETRY_DELAY` (5 ms). `TServerEventCacheBrokerTest::testConcurrentPublishesKeepEveryEvent` runs four processes publishing 25 events each at once on a `TFileCache` and an sqlite `TDbCache` (`tests/unit/Web/Services/ServerEvents/concurrent-publish.php`); every event is kept, in each worker's order. Without the lock the same run keeps about a quarter of them.

## Gotchas
- A `TFileCache` on a network share spans hosts, but `flock()` does not; name a `TRedisMutex` or `TDbMutex` module through `MutexID`.
- A wrapped or custom cache gets `TCacheMutex`; its exclusion is only as atomic as that cache's `add()`.
- A per-process cache (APCu across FPM pools, `TMemoryCache`) does not carry events between processes.
- Each open stream reads the cache every `PollInterval`; the read rate is streams / PollInterval.
- `getCache()` is the seam tests override.
