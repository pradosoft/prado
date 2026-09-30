# Caching/TCacheProxy

### Directories
[framework](../INDEX.md) / [Caching](./INDEX.md) / **`TCacheProxy`**

## Class Info
**Location:** `framework/Caching/TCacheProxy.php`
**Namespace:** `Prado\Caching`
**Extends:** [`TCache`](./TCache.md); implements `IModuleDependency`, [`IProxy`](../IProxy.md); uses [`TComponentProxyTrait`](../TComponentProxyTrait.md) (@since 4.4.0)

## Overview
Transparent cache module that delegates every `ICache` operation to the `TCache` module named by `BackingCacheId`. Lets the primary application cache be swapped in configuration without touching consumers.

## Configuration

```xml
<modules>
    <module id="cache" class="Prado\Caching\TCacheProxy" BackingCacheId="fileCache" PrimaryCache="true" />
    <module id="fileCache" class="Prado\Caching\TFileCache" Directory="Application.runtime.cache" PrimaryCache="false" />
</modules>
```

Only one of the two modules may be `PrimaryCache` (`cache_primary_duplicated` otherwise).

## Properties

- `BackingCacheId` (string) — required by `init()` (`cacheproxy_backing_cache_id_required`). Replacing a non-empty ID logs a `TLogger::WARNING` (`prado.caching`) and drops the resolved cache.
- `Cache` (read-only `TCache`) — resolves the backing on first call; throws `cacheproxy_cache_not_found` or `cacheproxy_invalid_cache_type`, then calls `attachProxy()`.

## Behavior

- `get()`, `set()`, `add()`, `delete()`, `flush()` call the backing's public methods: the backing's `KeyPrefix`, TTL, and dependency handling apply; the proxy's `KeyPrefix` does not. `ArrayAccess` goes through the same methods.
- `getIsAvailable()` is `true`; the backing reports its own prerequisites.
- `getValue()`/`setValue()`/`addValue()`/`deleteValue()` are stubs that are never reached.
- `getModuleDependencies()` returns the backing ID so the backing initializes first.

## See Also
- [TCache](./TCache.md), [TModuleProxy](../TModuleProxy.md)
