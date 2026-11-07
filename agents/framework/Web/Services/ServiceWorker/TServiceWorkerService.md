# Web/Services/ServiceWorker/TServiceWorkerService

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [Services](../INDEX.md) / [ServiceWorker](./INDEX.md) / **`TServiceWorkerService`**

## Class Info
**Location:** `framework/Web/Services/ServiceWorker/TServiceWorkerService.php`
**Namespace:** `Prado\Web\Services\ServiceWorker`
**Extends:** `TService`

## Overview
Serves the worker scripts and manifests of [TServiceWorkerModule](TServiceWorkerModule.md) and passes other request names to contributors. The module registers it as `sw` when no `TServiceWorkerService` is configured.

## Requests (`run()`)

| Service parameter | Response |
|---|---|
| `worker` or empty | `renderScript()` of the `w` worker (`default` without `w`); `text/javascript`, `Service-Worker-Allowed: <scope>` |
| `manifest` | `getManifestData()` JSON, `application/manifest+json` (`TMediaType::MANIFEST_JSON`); 404 `serviceworkerservice_manifest_unknown` without a manifest |
| Other | `IServiceWorkerRequestHandler::runServiceWorkerRequest()`; 404 `serviceworkerservice_request_unknown` |
| Unknown `w` | 404 `serviceworkerservice_worker_unknown` |

`sendBody()`: `Cache-Control: no-cache`, `ETag` (sha256 prefix of the body), UTF-8; a matching `If-None-Match` (any of a list) is 304 with no body.

## Properties

| Property | Default | Use |
|---|---|---|
| `ModuleID` | `Auto` | The module; `Auto` is the first `TServiceWorkerModule` |
| `AutoRegistered` | false | Set by the module when it registered the service |

`getModule()` throws `serviceworkerservice_module_required` (none) or `serviceworkerservice_module_invalid` (wrong class).

Constants: `SERVICE_ID` (`sw`), `REQUEST_WORKER`, `REQUEST_MANIFEST`, `RESERVED_REQUESTS`, `WORKER_PARAMETER` (`w`).

## Tests
- `tests/unit/Web/Services/ServiceWorker/TServiceWorkerServiceTest.php` (fake request and response)

**@since 4.4.0**
