# Caching/TPsrCacheInvalidArgumentException

### Directories
[framework](../INDEX.md) / [Caching](./INDEX.md) / **`TPsrCacheInvalidArgumentException`**

## Class Info
**Location:** `framework/Caching/TPsrCacheInvalidArgumentException.php`
**Namespace:** `Prado\Caching`
**Extends:** [`TInvalidDataValueException`](../Exceptions/TInvalidDataValueException.md); implements `Psr\Cache\InvalidArgumentException`, `Psr\SimpleCache\InvalidArgumentException` (@since 4.4.0)

## Overview
Thrown by [`TPsrSimpleCache`](./TPsrSimpleCache.md) and [`TPsrCacheItemPool`](./TPsrCacheItemPool.md) for a key that is not a legal PSR cache key (`psrcachebacking_key_invalid`): not a string, empty, or containing one of `{}()/\@:`. Both standards require the PSR interfaces it implements.

## See Also
- [TPsrCacheException](./TPsrCacheException.md), [TPsrCacheBackingTrait](./TPsrCacheBackingTrait.md)
