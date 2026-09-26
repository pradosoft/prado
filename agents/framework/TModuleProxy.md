# TModuleProxy

### Directories
[framework](./INDEX.md) / **`TModuleProxy`**

## Class Info
**Location:** `framework/TModuleProxy.php`
**Namespace:** `Prado`
**Extends:** [`TModule`](./TModule.md); implements `IModuleDependency`, [`IProxy`](./IProxy.md); uses [`TComponentProxyTrait`](./TComponentProxyTrait.md) (@since 4.4.0)

## Overview
Proxy module over another application module named by `BackingComponentId`. Consumers keep a fixed module ID while the configuration decides which module serves it.

```xml
<module id="myService" class="Prado\TModuleProxy" BackingComponentId="myRealService" />
<module id="myRealService" class="MyApp\MyService" />
```

## Properties

- `BackingComponentId` (string) — module ID of the backing; required by `init()` (`componentproxy_backing_component_id_required`). Replacing a non-empty ID calls `detachProxy()`, logs a `TLogger::WARNING` (`prado.component`), and drops the resolved backing.
- `BackingComponent` (read-only) — resolves `$app->getModule($id)` on first call, throws `componentproxy_component_not_found` when absent, and calls `attachProxy()` once resolved.
- `ID` — the proxy's own module ID; not forwarded.

## Behavior

- `getModuleDependencies()` returns the backing ID (or `null`), so `TApplication` initializes the backing first in every pass.
- The backing is excluded from serialization and re-resolved after `unserialize()`.

## Typed variants
[`TCacheProxy`](./Caching/TCacheProxy.md) (`BackingCacheId`, must be a `TCache`) and [`TDataSourceConfigProxy`](./Data/TDataSourceConfigProxy.md) (`BackingDataSourceId`, must be a `TDataSourceConfig`) follow the same pattern on their own base classes.
