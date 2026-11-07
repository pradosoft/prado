# Web/Services/ServiceWorker/INDEX.md

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [Services](../INDEX.md) / **`ServiceWorker`**

## Purpose

The application's browser service workers (#1478). `TServiceWorkerModule` is the one configuration point: it links or registers `TServiceWorkerService`, defines the workers, collects the script fragments of the contributors, and adds the worker registration ([TServiceWorker](../../UI/WebControls/TServiceWorker.md)) and the manifest link ([TWebManifest](../../UI/WebControls/TWebManifest.md)) to every page of `TPageService`. **Since 4.4.0.**

```xml
<module id="serviceworker" class="Prado\Web\Services\ServiceWorker\TServiceWorkerModule"
    CacheAssets="true" OfflinePage="Offline" NavigationPreload="true"
    NetworkFirst="Catalog, Help" NetworkTimeout="3"
    Manifest.Name="Orders" Manifest.ShortName="Orders" Manifest.ThemeColor="#2b5797">
    <worker name="admin" Scope="/admin/" OfflinePage="Admin.Offline" NetworkFirst="" />
</module>
```

## Classes

- **[TServiceWorkerModule](TServiceWorkerModule.md)** — Configuration point. `ServiceID` resolution (`Auto` adopts or registers `sw`), the `default` worker from its own properties and named workers from `<worker>` elements, contributor collection, page selection of a worker, scope check, `AutoRegister` page additions, `ExcludePages`, `Enabled` kill switch, `ClearSiteDataOnLogout`. Contributes the fetch strategies fragment.
- **[TServiceWorkerService](TServiceWorkerService.md)** — `TService` serving `?sw=worker[&w=<name>]` (script), `?sw=manifest[&w=<name>]` (manifest), and `?sw=<name>` of a contributor; `Service-Worker-Allowed`, `Cache-Control: no-cache`, `ETag`/304.
- **[TServiceWorkerDefinition](TServiceWorkerDefinition.md)** — One worker: `Name`, `Scope`, `Enabled`, `Contributors`, fetch strategies, `Manifest`.
- **[TServiceWorkerScript](TServiceWorkerScript.md)** — Script builder handed to each contributor: fragments by priority, precache URLs, excluded paths, `getVersion()` hash, `render()`, `renderUnregister()`.
- **[TWebAppManifest](TWebAppManifest.md)** — Web app manifest members from properties, a JSON `File`, and `Members`.
- **[IServiceWorkerContributor](IServiceWorkerContributor.md)** — `contributeServiceWorker(TServiceWorkerScript)`.
- **[IServiceWorkerRequestHandler](IServiceWorkerRequestHandler.md)** — Claims `?sw=<name>` request names.

## The browser model

| Constraint | Consequence |
|---|---|
| Secure context (HTTPS, or localhost and 127.0.0.1) | `navigator.serviceWorker` is undefined on plain HTTP |
| Scope is a URL path prefix; the longest matching scope controls a page | Workers split by path, not by query: `?page=` and `?admin=` services share one worker. `TUrlMapping` path URLs or a second entry script give a subpath its own worker |
| Script's maximum scope is its URL directory, widened by `Service-Worker-Allowed` | The service sends the header with the worker's scope, so `Path` URL formats work |
| One registration per scope | Features contribute fragments to one script per worker |
| The browser re-fetches the script on navigation and every 24 h | `Cache-Control: no-cache`; the embedded `VERSION` hash changes with any fragment |
| A new worker waits for the old one's pages to close | `prado:serviceworkerupdate` and `detail.activate()` |
| A registered worker outlives deployments | `Enabled="false"` serves a self-removing worker |
| An idle worker is terminated | No live connection in the worker; see [Prado.SharedConnection](../../Javascripts/packages.md) for tabs sharing one connection |
| Workers of an origin share Cache Storage, IndexedDB, `BroadcastChannel`, and cookies | Cache names are `prado-<worker>-<version>-<name>`; `activate` deletes only the worker's older versions |

## Request flow

1. A page of `TPageService` runs; `TServiceWorkerModule::attachPage()` (from `onPreRunPage`) adds a `TServiceWorker` (head, else page) and, when the worker has a manifest and the page has no manifest link, a `TWebManifest` to the head at `OnInitComplete`.
2. `TServiceWorker::onPreRender()` resolves the page's worker and its scope, then registers `Prado.ServiceWorker.register(url, {scope, worker})` as an end script, or renders it in place on a page without a form.
3. The browser requests `?sw=worker`; the service calls `TServiceWorkerModule::renderScript()`, which builds a `TServiceWorkerScript` from the admitted contributors (the module's strategies fragment always).

## JavaScript

| File | Runs in | Role |
|---|---|---|
| `source/prado/serviceworker/worker.js` | Worker | Core runtime: the `prado` API (`onFetch`, `onMessage`, `onInstall`, `onActivate`, `isExcluded`, `isCacheable`, `fromNetwork`, `store`, `fromCache`, `clearCaches`, `postToClients`, `start`), precache, cache cleanup, `clients.claim()`, message router (`version`, `skipWaiting`, `clearCache`) |
| `source/prado/serviceworker/worker-strategies.js` | Worker | Fragment of the module: assets cache first, network first with timeout, offline page, navigation preload |
| `source/prado/serviceworker/worker-unregister.js` | Worker | Served for a disabled worker: skip waiting, delete own caches, unregister, reload windows |
| `source/prado/serviceworker/serviceworker.js` | Page | `Prado.ServiceWorker` (package `serviceworker`): `register()`, `postMessage()` with replies, `activate()`, document events |

The worker files are not script packages; the service reads them with `TServiceWorkerScript::getSourcePath()`. `eslint.config.js` lints `worker*.js` with service worker globals.

## Conventions

- **Never cached:** non-GET, other origins, `Accept: text/event-stream`, `PRADO_CALLBACK_TARGET`, the service's own URLs (`exclude`), `Cache-Control: no-store`/`private`, non-OK and opaque responses. PHP's default `session.cache_limiter` sends `no-store`, so session pages stay uncached under `NetworkFirst`.
- **No fetch listener** is added unless a fragment calls `prado.onFetch()` before `prado.start()`.
- **Pages build no script.** The registration URL is fixed; only the script request runs the contributors.
- **Security:** a worker persists after its page; the service is the only script source, the scripts echo no request input, CSP `worker-src 'self'` and `manifest-src 'self'` ([TCspDirective](../../HttpHeaders/TCspDirective.md)), and `ClearSiteDataOnLogout` (`storage` clears Cache Storage and registrations).
- **Web Push** is #1479 (a contributor and request handler).

## Tests

- `tests/unit/Web/Services/ServiceWorker/` — module, service, script, definition, manifest; `TServiceWorkerTestContributor` is the shared contributor and request handler fixture.
- `tests/js/serviceworker/` — runtime, strategies, removal script (`workerHarness.js` runs them as the service renders them, against a fake worker scope), and the page client.
- `tests/playwright/service-worker/` with the `tests/harness/service-worker` app — registration, manifest, messages, offline page, uncached callbacks, update with `activate()`, removal, the admin worker on `index.php/admin/`, and the two-tab shared connection.
