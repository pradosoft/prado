# Web/TAssetManagerProxy

### Directories
[framework](../INDEX.md) / [Web](./INDEX.md) / **`TAssetManagerProxy`**

## Class Info
**Location:** `framework/Web/TAssetManagerProxy.php`
**Namespace:** `Prado\Web`
**Extends:** [`TAssetManager`](./TAssetManager.md); implements `IModuleDependency`, [`IProxy`](../IProxy.md); uses [`TComponentProxyTrait`](../TComponentProxyTrait.md) (@since 4.4.0)

## Overview
Transparent asset manager module that delegates every public `TAssetManager` operation to the `TAssetManager` module named by `BackingAssetManagerId`. Lets the application asset manager slot be swapped in configuration without touching the consumers that publish through it.

## Configuration

```xml
<modules>
    <module id="asset" class="Prado\Web\TAssetManagerProxy" BackingAssetManagerId="fileAssets" />
    <module id="fileAssets" class="Prado\Web\TAssetManager" BasePath="Application.assets" BaseUrl="/assets" />
</modules>
```

The backing module owns `BasePath` and `BaseUrl`; the proxy sets none of its own.

## Properties

- `BackingAssetManagerId` (string) — required by `init()` (`assetmanagerproxy_backing_asset_manager_id_required`). Replacing a non-empty ID calls `detachProxy()`, logs a `TLogger::WARNING` (`prado.web`), and drops the resolved asset manager.
- `AssetManager` (read-only `TAssetManager`) — resolves `$app->getModule($id)` on first call; throws `assetmanagerproxy_asset_manager_not_found` or `assetmanagerproxy_invalid_asset_manager_type`, then calls `attachProxy()`.
- Every `TAssetManager` property (`BasePath`, `BaseUrl`, `LinkAssets`, `ForceCopy`, `Atomic`, `AppendTimestamp`, `TimestampVar`, `HashCallback`, `BeforeCopy`, `AfterCopy`, `AssetMap`, `Only`, `Except`, `CaseSensitive`, `FileMode`, `DirMode`) reads and writes the backing. The backing's `assertUninitialized` guard on `BasePath`/`BaseUrl` still applies.

## Behavior

- `init()` registers the proxy as the application asset manager (`setAppAssetManager()`), raises `dyInit` through `TModule::init()`, and marks the proxy initialized. `TAssetManager::init()` is bypassed: it would resolve a default `BasePath`/`BaseUrl` from the request and validate a publishing directory the proxy does not own.
- `publishFilePath()`, `publish()`, `getPublished()`, `getPublishedPath()`, `getPublishedUrl()`, `validateSymlinks()`, `copyDirectory()`, `resolveAsset()`, and `publishTarFile()` call the backing's public methods, so the backing's publish cache, hash, and options apply. Methods defined on the base class are not reached by `__call`, so each is overridden explicitly.
- Other names, including the backing's extra properties, methods, and `on` events, reach the backing through `TComponentProxyTrait`.
- `getModuleDependencies()` returns the backing ID so the backing initializes first.
- `_getZappableSleepProps()` excludes the forwarder list, the resolved backing (re-resolved after `unserialize()`), and an empty backing ID.

## Tests
`tests/unit/Web/TAssetManagerProxyTest.php` builds a backing `TAssetManager` on a temp directory registered as a path alias (`BasePath` takes namespace form) under the `Caching/mockapp` application.

## See Also
- [TAssetManager](./TAssetManager.md), [TModuleProxy](../TModuleProxy.md), [TCacheProxy](../Caching/TCacheProxy.md)
