# Web/Services/ServiceWorker/TServiceWorkerModule

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [Services](../INDEX.md) / [ServiceWorker](./INDEX.md) / **`TServiceWorkerModule`**

## Class Info
**Location:** `framework/Web/Services/ServiceWorker/TServiceWorkerModule.php`
**Namespace:** `Prado\Web\Services\ServiceWorker`
**Extends:** `TModule` · **Implements:** `IServiceWorkerContributor`

## Overview
The one configuration point of the application's service workers. The module's worker properties configure the `default` worker; `<worker>` elements configure named workers. It links or registers [TServiceWorkerService](TServiceWorkerService.md), builds scripts from the contributors, and adds the registration and manifest link to pages.

## Service resolution (`init()`)

| `ServiceID` | Registered service | Result |
|---|---|---|
| `Auto` (default) | A `TServiceWorkerService` under any ID | Adopted |
| `Auto` | None | Registers `sw` (`TServiceWorkerService::SERVICE_ID`) with `AutoRegistered`, `ModuleID` |
| An ID | `TServiceWorkerService` under that ID | Used |
| An ID | None | Registered under that ID |
| An ID, or `sw` for `Auto` | Another class | `serviceworkermodule_service_invalid` |

Same pattern as `THttpHeadersManager::ReportingServiceId` with `TCspReportingService`. `getResolvedServiceID()` is the ID in use.

## Properties

| Property | Default | Use |
|---|---|---|
| `ServiceID` | `Auto` | Service, as above |
| `Enabled` | true | False: no page additions; the service serves self-removing workers |
| `AutoRegister` | true | Attach to `onBeginRequest` → `TPageService::onPreRunPage` → page `OnInitComplete` |
| `DefaultWorker` | `default` | Worker of pages that select none |
| `ExcludePages` | empty | Page paths without additions; `Admin` covers `Admin.Users` |
| `ClearSiteDataOnLogout` | empty | `Clear-Site-Data` types (`cache`, `storage`, `cookies`, `executionContexts`, `*`) on `TAuthManager::onLogout` |
| `Scope`, `Contributors`, `CacheAssets`, `NetworkFirst`, `NetworkTimeout`, `OfflinePage`, `NavigationPreload`, `Manifest.*`, `ManifestFile` | | Forward to the `default` [TServiceWorkerDefinition](TServiceWorkerDefinition.md) |

## Workers

- XML: `<worker name="admin" Scope="/admin/" … />`; PHP: `'workers' => [['name' => 'admin', 'Scope' => …]]` or with a `properties` sub-array.
- A named worker is a clone of the `default` worker (manifest deep-copied) with its scope cleared, then its attributes. `<worker name="default">` configures the default worker.
- Errors: `serviceworkermodule_worker_name_required`, `_worker_scope_required`, `_worker_duplicate`, `_scope_duplicate`.
- `getControllingWorker($path)` is the longest scope prefixing the path, as the browser matches.

## A page's worker

`getPageWorkerName($page, $worker)`: `TServiceWorker::Worker` → `TPage::ServiceWorker` (also `<pages ServiceWorker>` of a directory) → `TPageService::ServiceWorker` → `DefaultWorker`.

`getPageWorker()` returns the worker when it exists, is enabled (`getIsWorkerEnabled()`: module and worker), and is the controlling worker of the request path; otherwise null, with a warning log for a missing worker or a scope mismatch.

## Contributors

- `getContributorObjects()`: application modules implementing `IServiceWorkerContributor` (this module among them, lazy modules loaded) + `addContributor($c, $id)` objects (an unnamed one is admitted only by workers admitting all).
- `createScript($worker)`: excludes the worker's script and manifest URLs, calls admitted contributors (the module always), then `dyContributeServiceWorker($script)`.
- `renderScript($worker)`: `createScript()->render()`, or `TServiceWorkerScript::renderUnregister()` when disabled.
- `getRequestHandler($name)`: the contributor implementing `IServiceWorkerRequestHandler` that claims a non-reserved name.

## Strategies fragment

`contributeServiceWorker()` adds `worker-strategies.js` as fragment `strategies` (priority 0) with `{assets, networkFirst, networkTimeout (ms), offline, navigationPreload}`. `NetworkFirst` and `OfflinePage` page paths resolve through `constructUrl()` of the page service; `/` entries stay URL paths. The offline URL is precached.

## URLs

`getScriptUrl()`, `getManifestUrl($worker, $encodeAmpersand)`, `constructServiceUrl()`: `constructUrl($serviceID, 'worker'|'manifest', ['w' => name] for named workers)`.

## Gotchas

- Uniform access: subclasses override `getEnabled()` and the rest; the module reads its own getters (the harness module overrides `getEnabled()`).
- `Scope` defaults to the entry script's directory at the time it is read (`getApplicationUrl()`).
- Page additions only happen for `TPageService`; another service's pages register nothing automatically.

## Tests
- `tests/unit/Web/Services/ServiceWorker/TServiceWorkerModuleTest.php`

**@since 4.4.0**
