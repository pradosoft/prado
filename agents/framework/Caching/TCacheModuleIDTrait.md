# Caching/TCacheModuleIDTrait

### Directories
[framework](../INDEX.md) / [Caching](./INDEX.md) / **`TCacheModuleIDTrait`**

## Class Info
**Location:** `framework/Caching/TCacheModuleIDTrait.php`
**Namespace:** `Prado\Caching`
**Since:** 4.4.0

## Overview
Gives a class a `CacheModuleID` property (stored in a private field, not ViewState) and resolves the `ICache` it names.

- `resolveCacheModule(bool $required = true): ?ICache` - a set ID must name an `ICache` module (`cachemoduleid_invalid`, always thrown); an empty ID uses `TApplication::getCache()`, which is null when no cache is configured (`cachemoduleid_cache_required` when `$required`).
- `claimCacheKey(ICache, string $key, int $ttl): bool` - `add($key, 1, max(1, $ttl))`; true when this call stored the key. Backs single-use tokens.

`add()` is atomic on TAPCCache, TMemCache, TRedisCache, and TEtcdCache. TFileCache checks then writes, and TDbCache relies on the key constraint with a retry, so both leave a short race window. TMemoryCache is per process and does not persist across requests. `TCache::add()` stores nothing for an empty value with no TTL, which is why the claim stores `1` with a TTL of at least 1.

Users: [TCaptcha](../Web/UI/WebControls/TCaptcha.md) (optional cache), [TFormGuard](../Web/UI/WebControls/TFormGuard.md) (required for RateLimit), [TProofOfWork](../Web/UI/WebControls/TProofOfWork.md) (required). Older classes with their own `CacheModuleID`: TCachePageStatePersister, TOutputCache, TCacheHttpSession.

## See Also

- [ICache](./ICache.md)
- [TCache](./TCache.md)
