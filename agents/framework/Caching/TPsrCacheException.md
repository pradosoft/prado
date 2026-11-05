# Caching/TPsrCacheException

### Directories
[framework](../INDEX.md) / [Caching](./INDEX.md) / **`TPsrCacheException`**

## Class Info
**Location:** `framework/Caching/TPsrCacheException.php`
**Namespace:** `Prado\Caching`
**Extends:** [`TConfigurationException`](../Exceptions/TConfigurationException.md); implements `Psr\Cache\CacheException`, `Psr\SimpleCache\CacheException` (@since 4.4.0)

## Overview
Thrown by [`TPsrSimpleCache`](./TPsrSimpleCache.md) and [`TPsrCacheItemPool`](./TPsrCacheItemPool.md) when their backing cache is missing or is not an `ICache` (`psrcachebacking_cache_required`, `psrcachebacking_cache_not_found`, `psrcachebacking_invalid_cache_type`). A PSR consumer catches it as a cache error; a Prado caller catches it as a configuration error.

## See Also
- [TPsrCacheInvalidArgumentException](./TPsrCacheInvalidArgumentException.md), [TPsrCacheBackingTrait](./TPsrCacheBackingTrait.md)
