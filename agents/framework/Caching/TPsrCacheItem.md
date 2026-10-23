# Caching/TPsrCacheItem

### Directories
[framework](../INDEX.md) / [Caching](./INDEX.md) / **`TPsrCacheItem`**

## Class Info
**Location:** `framework/Caching/TPsrCacheItem.php`
**Namespace:** `Prado\Caching`
**Implements:** `Psr\Cache\CacheItemInterface`; uses `TClockAwareTrait` (@since 4.4.0)

## Overview
The PSR-6 item that [`TPsrCacheItemPool`](./TPsrCacheItemPool.md) hands out: a key, a value, whether the lookup hit, and an optional expiration. The pool turns the expiration into the backing cache's TTL at `save()`.

## Behavior

| Method | Result |
|---|---|
| `__construct(string $key, mixed $value = null, bool $isHit = false)` | the pool creates items; `setClock()` gives them the pool's clock |
| `getKey()`, `get()`, `isHit()` | the lookup; `get()` is `null` for a miss until `set()` |
| `set($value)` | the value to store; `isHit()` stays as looked up |
| `expiresAt(?DateTimeInterface)` | stores the instant as a `DateTimeImmutable`; `null` clears it |
| `expiresAfter(int\|DateInterval\|null)` | the clock's `now()` plus the period, microseconds kept; a negative period is in the past; `null` clears it |
| `getExpiration()` | `?DateTimeImmutable`; `null` means the pool's `DefaultExpire` applies |

## See Also
- [TPsrCacheItemPool](./TPsrCacheItemPool.md)
