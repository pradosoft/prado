# Caching/TPsrCacheBackingTrait

### Directories
[framework](../INDEX.md) / [Caching](./INDEX.md) / **`TPsrCacheBackingTrait`**

## Class Info
**Location:** `framework/Caching/TPsrCacheBackingTrait.php`
**Namespace:** `Prado\Caching`
**Type:** trait; uses `TApplicationClockAwareTrait` (@since 4.4.0)

## Overview
Gives a PSR cache adapter module its Prado [`ICache`](./ICache.md) backing and the rules PSR-6 and PSR-16 share. [`TPsrSimpleCache`](./TPsrSimpleCache.md) and [`TPsrCacheItemPool`](./TPsrCacheItemPool.md) use it; the using class declares `IModuleDependency`.

## Properties

| Property | Type | Default | Description |
|---|---|---|---|
| `BackingCacheId` | string | `''` | Module ID of the backing cache. Returned by `getModuleDependencies()`. Replacing a non-empty ID logs a `TLogger::WARNING` (`prado.caching`) and drops the current backing. |
| `BackingCache` | `?ICache` | `null` | The backing, set directly or resolved on first use; `null` drops it so the next operation resolves again. |
| `KeyPrefix` | string | `''` | Prepended to every key before it reaches the backing; separates adapters sharing one backing cache. |
| `DefaultExpire` | int | `0` | Seconds a value stored without a TTL lives; `0` and negatives mean no expiry. |

## Backing Resolution
`getBackingCache()` returns `BackingCache` when set; otherwise `resolveBackingCache()`:

| Configuration | Backing | Failure |
|---|---|---|
| `BackingCacheId` set | the application module with that ID | `psrcachebacking_cache_not_found`, `psrcachebacking_invalid_cache_type` (not an `ICache`) |
| `BackingCacheId` empty | the primary application cache | `psrcachebacking_cache_required` |

Each failure is a [`TPsrCacheException`](./TPsrCacheException.md).

## Keys
- `RESERVED_KEY_CHARACTERS` is `{}()/\@:`. `validateKey()` accepts a non-empty string without them, or an integer as its string form, and throws [`TPsrCacheInvalidArgumentException`](./TPsrCacheInvalidArgumentException.md) (`psrcachebacking_key_invalid`) otherwise. `validateKeys()` validates every key of an iterable before any is used.
- `toBackingKey()` prepends `KeyPrefix`. A `TCache` backing hashes the result under its own `KeyPrefix`, so key length is unlimited there.

## Entries
| Helper | Backing call |
|---|---|
| `readEntry($key)` | `get()`; the one-element envelope on a hit, `null` on a miss |
| `writeEntry($key, $value, $expire)` | `set()` with `[$value]` |
| `storeEntry($key, $value, $ttl)` | `null` TTL → `DefaultExpire`; seconds ≤ 0 → `deleteEntry()`; else `writeEntry()` |
| `deleteEntry($key)` | `delete()` |
| `clearEntries()` | `flush()`; `false` on `TNotSupportedException` |
| `toSeconds(int\|DateInterval)` | seconds; a `DateInterval` is measured from the clock's `now()` |

The envelope keeps `null`, `false`, `0`, `''`, and `[]` as cached values, which `TCache::set()` would otherwise delete and `ICache::get()` would report as a miss.

## Serialization
`_addPsrCacheBackingZappable()` excludes the properties at their defaults and, when `BackingCacheId` is set, the resolved backing; the using class calls it from `_getZappableSleepProps()`.

## See Also
- [TPsrSimpleCache](./TPsrSimpleCache.md), [TPsrCacheItemPool](./TPsrCacheItemPool.md), [TCacheModuleIDTrait](./TCacheModuleIDTrait.md)
