# Caching/TPsrCache

### Directories
[framework](../INDEX.md) / [Caching](./INDEX.md) / **`TPsrCache`**

## Class Info
**Location:** `framework/Caching/TPsrCache.php`
**Namespace:** `Prado\Caching`
**Extends:** [`TCache`](./TCache.md); implements `IModuleDependency` (@since 4.4.0)

## Overview
A [`TCache`](./TCache.md) module whose storage is a PSR-16 `Psr\SimpleCache\CacheInterface` or a PSR-6 `Psr\Cache\CacheItemPoolInterface`, such as a Symfony or Laminas cache adapter. The PSR cache serves Prado as the primary cache or as any other cache module, with `TCache`'s key prefixing, dependencies, and `ArrayAccess` on top. [`TPsrSimpleCache`](./TPsrSimpleCache.md) and [`TPsrCacheItemPool`](./TPsrCacheItemPool.md) are the reverse adapters.

## Configuration

```xml
<module id="symfonyCache" class="App\Caching\SymfonyCacheModule" />
<module id="cache" class="Prado\Caching\TPsrCache" BackingCacheId="symfonyCache" />
```

```php
$cache = new TPsrCache();
$cache->setBackingCache(new \Symfony\Component\Cache\Psr16Cache($adapter));
$cache->setPrimaryCache(false);
$cache->init(null);
```

## Properties

- `BackingCacheId` (string) — module ID of a module implementing either PSR interface; declared through `getModuleDependencies()` so the module initializes first. Replacing a non-empty ID logs a `TLogger::WARNING` (`prado.caching`) and drops the current backing.
- `BackingCache` (`CacheInterface|CacheItemPoolInterface|null`) — the backing, set directly or resolved from `BackingCacheId` on first use; `null` drops it so the next operation resolves the ID again. `init()` requires one of the two (`psrcache_backing_cache_required`); resolution throws `psrcache_backing_cache_not_found` or `psrcache_invalid_backing_cache_type`.

## Storage

| Operation | PSR-16 | PSR-6 |
|---|---|---|
| `getValue()` | `get($key, false)` | `getItem($key)`; the value on a hit, `false` otherwise |
| `setValue()` | `set($key, $data, $ttl)` | `getItem($key)->set($data)->expiresAfter($ttl)`, then `save()` |
| `addValue()` | `getValue()` then `setValue()`; a concurrent request can win the race | same |
| `deleteValue()` | `delete($key)` | `deleteItem($key)` |
| `flush()` | `clear()` | `clear()` |

- A Prado expiry of `0` becomes a `null` TTL, the PSR cache's own default.
- The PSR cache receives the hashed `generateUniqueKey()` keys, which both standards accept; `$data` is `TCache`'s `[$value, $dependency]` pair.
- `getIsAvailable()` is `true` when either PSR interface is installed; both are composer requirements.
- A backing that is a `TPsrSimpleCache` or `TPsrCacheItemPool` whose own backing is this cache throws `psrcache_backing_cache_recursive`, since it would read and write back into the same cache.

## See Also
- [TCache](./TCache.md), [TPsrSimpleCache](./TPsrSimpleCache.md), [TPsrCacheItemPool](./TPsrCacheItemPool.md)
- [TPsrLogRoute](../Util/Log/TPsrLogRoute.md) — the PSR-3 reverse adapter of the same shape
