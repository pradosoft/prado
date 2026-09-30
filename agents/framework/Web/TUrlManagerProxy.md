# Web/TUrlManagerProxy

### Directories
[framework](../INDEX.md) / [Web](./INDEX.md) / **`TUrlManagerProxy`**

## Class Info
**Location:** `framework/Web/TUrlManagerProxy.php`
**Namespace:** `Prado\Web`
**Extends:** [`TUrlManager`](./TUrlManager.md); implements `IModuleDependency`, [`IProxy`](../IProxy.md); uses [`TComponentProxyTrait`](../TComponentProxyTrait.md) (@since 4.4.0)

## Overview
Transparent URL manager module that delegates `constructUrl()` and `parseUrl()` to the `TUrlManager` module named by `BackingUrlManagerId`. `THttpRequest::UrlManager` names one module ID and checks `instanceof TUrlManager`, so the proxy lets the configuration swap the rule set without touching the request module.

## Configuration

```xml
<module id="request" class="Prado\Web\THttpRequest" UrlManager="urls" />
<module id="urls" class="Prado\Web\TUrlManagerProxy" BackingUrlManagerId="friendlyUrls" />
<module id="friendlyUrls" class="Prado\Web\TUrlMapping" EnableCustomUrl="true">
    <url ServiceParameter="Home" pattern="home" />
</module>
```

## Properties

- `BackingUrlManagerId` (string) — required by `init()` (`urlmanagerproxy_backing_url_manager_id_required`). Replacing a non-empty ID logs a `TLogger::WARNING` (`prado.web`) and drops the resolved backing.
- `UrlManager` (read-only `TUrlManager`) — resolves the backing on first call; throws `urlmanagerproxy_url_manager_not_found` or `urlmanagerproxy_invalid_url_manager_type`, then calls `attachProxy()`.

## Behavior

- `constructUrl()` and `parseUrl()` call the backing's methods; other properties, methods, and events reach the backing through the trait.
- `getModuleDependencies()` returns the backing ID so the backing initializes first.

## See Also
- [TUrlManager](./TUrlManager.md), [TModuleProxy](../TModuleProxy.md)
