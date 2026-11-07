# Web/Services/ServiceWorker/TServiceWorkerDefinition

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [Services](../INDEX.md) / [ServiceWorker](./INDEX.md) / **`TServiceWorkerDefinition`**

## Class Info
**Location:** `framework/Web/Services/ServiceWorker/TServiceWorkerDefinition.php`
**Namespace:** `Prado\Web\Services\ServiceWorker`
**Extends:** `TApplicationComponent`

## Overview
The configuration of one worker of [TServiceWorkerModule](TServiceWorkerModule.md).

## Properties

| Property | Default | Use |
|---|---|---|
| `Name` | `default` | `[A-Za-z0-9_-]+`; in the script URL (`w`) and cache names |
| `Scope` | entry script directory | Absolute path kept; relative resolved against the entry directory |
| `Enabled` | true | False serves the self-removing worker |
| `Contributors` | empty (all) | Module IDs admitted; `admitsContributor($id)` |
| `CacheAssets` | false | Cache first under the asset base URL |
| `NetworkFirst` | empty | Page paths and `/` URL prefixes |
| `NetworkTimeout` | 3.0 s | 0 waits for the network; negative throws |
| `OfflinePage` | empty | Page path precached and shown for failed navigations |
| `NavigationPreload` | false | `registration.navigationPreload` on activate |
| `Manifest` | lazy [TWebAppManifest](TWebAppManifest.md) | `Manifest.Name` etc. as subproperties |
| `ManifestFile` | empty | `Manifest->File` |

`getHasManifest()`; `getManifestData()` adds `start_url` and `scope` (the worker scope) when missing, or null without a manifest. `__clone()` deep-copies the manifest (named workers start as clones of `default`).

## Tests
- `tests/unit/Web/Services/ServiceWorker/TServiceWorkerDefinitionTest.php`

**@since 4.4.0**
