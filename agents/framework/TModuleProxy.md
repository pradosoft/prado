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
Each follows the same pattern on its own base class so the consumer's `instanceof` gate still passes: [`TCacheProxy`](./Caching/TCacheProxy.md) (`BackingCacheId`), [`TDataSourceConfigProxy`](./Data/TDataSourceConfigProxy.md) (`BackingDataSourceId`), [`TAssetManagerProxy`](./Web/TAssetManagerProxy.md) (`BackingAssetManagerId`), [`TUrlManagerProxy`](./Web/TUrlManagerProxy.md) (`BackingUrlManagerId`), [`TSecurityManagerProxy`](./Security/TSecurityManagerProxy.md) (`BackingSecurityManagerId`), [`TGlobalizationProxy`](./I18N/TGlobalizationProxy.md) (`BackingGlobalizationId`), [`TLogRouterProxy`](./Util/Log/TLogRouterProxy.md) (`BackingLogRouterId`), and [`TPermissionsManagerProxy`](./Security/Permissions/TPermissionsManagerProxy.md) (`BackingPermissionsManagerId`). [`TUserManagerProxy`](./Security/TUserManagerProxy.md) (`BackingUserManagerId`) extends `TModule` and implements `IUserManager`, the interface its consumers check.
