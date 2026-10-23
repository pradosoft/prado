# Caching/TPsrSimpleCache

### Directories
[framework](../INDEX.md) / [Caching](./INDEX.md) / **`TPsrSimpleCache`**

## Class Info
**Location:** `framework/Caching/TPsrSimpleCache.php`
**Namespace:** `Prado\Caching`
**Extends:** [`TModule`](../TModule.md); implements `Psr\SimpleCache\CacheInterface`, `IModuleDependency`; uses [`TPsrCacheBackingTrait`](./TPsrCacheBackingTrait.md) (@since 4.4.0)

## Overview
PSR-16 adapter module over a Prado [`ICache`](./ICache.md). A library that accepts a `Psr\SimpleCache\CacheInterface` reads and writes the backing Prado cache through it. [`TPsrCacheItemPool`](./TPsrCacheItemPool.md) is the PSR-6 adapter of the same backing; [`TPsrCache`](./TPsrCache.md) is the reverse adapter.

## Configuration

```xml
<module id="cache" class="Prado\Caching\TFileCache" />
<module id="psrcache" class="Prado\Caching\TPsrSimpleCache" BackingCacheId="cache" KeyPrefix="lib." DefaultExpire="3600" />
```

```php
$library->setCache($app->getModule('psrcache'));

$psr = new TPsrSimpleCache();
$psr->setBackingCache($app->getCache());
$psr->set('token', $value, 300);
```

## Properties
`BackingCacheId`, `BackingCache`, `KeyPrefix`, and `DefaultExpire` come from [`TPsrCacheBackingTrait`](./TPsrCacheBackingTrait.md), which also resolves the backing (`BackingCache`, then the `BackingCacheId` module, then the primary application cache).

## Behavior

| Method | Backing call |
|---|---|
| `get($key, $default)` | `ICache::get()`; `$default` on a miss |
| `set($key, $value, $ttl)` | `ICache::set()` with the TTL in seconds; a `null` TTL uses `DefaultExpire`; a TTL of zero or less deletes the key |
| `has($key)` | `ICache::get()` returned the envelope |
| `delete($key)` | `ICache::delete()` |
| `clear()` | `ICache::flush()`; `false` when the backing throws `TNotSupportedException` |
| `getMultiple()`, `setMultiple()`, `deleteMultiple()` | every key validated first, then one backing call per key; `false` when any write or delete fails |

- Values travel in a one-element array envelope, so `null`, `false`, `0`, `''`, and `[]` are cached and found.
- `clear()` flushes the whole backing cache, including entries written by its other users.
- An illegal key throws [`TPsrCacheInvalidArgumentException`](./TPsrCacheInvalidArgumentException.md); a missing backing throws [`TPsrCacheException`](./TPsrCacheException.md).
- `getModuleDependencies()` returns `BackingCacheId` so the backing initializes first.

## Prado 5
All caching in Prado 5 is PSR compliant. Code that reaches the cache through this module carries over unchanged. A new application accesses its cache through this module or `TPsrCacheItemPool`, and an application that intends to upgrade moves its cache access to them.

## See Also
- [TPsrCacheBackingTrait](./TPsrCacheBackingTrait.md), [TPsrCacheItemPool](./TPsrCacheItemPool.md), [TPsrCache](./TPsrCache.md)
- [TPsrLogger](../Util/Log/TPsrLogger.md) — the PSR-3 adapter of the same shape
