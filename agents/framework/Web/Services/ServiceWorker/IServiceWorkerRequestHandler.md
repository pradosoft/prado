# Web/Services/ServiceWorker/IServiceWorkerRequestHandler

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [Services](../INDEX.md) / [ServiceWorker](./INDEX.md) / **`IServiceWorkerRequestHandler`**

## Class Info
**Location:** `framework/Web/Services/ServiceWorker/IServiceWorkerRequestHandler.php`
**Namespace:** `Prado\Web\Services\ServiceWorker`

## Overview
A contributor of [TServiceWorkerModule](TServiceWorkerModule.md) that answers `?sw=<name>` requests of [TServiceWorkerService](TServiceWorkerService.md), such as Web Push `subscribe` and `unsubscribe` (#1479).

## Methods

| Method | Use |
|---|---|
| `getServiceWorkerRequestNames(): array` | Names claimed; `worker` and `manifest` (`TServiceWorkerService::RESERVED_REQUESTS`) are never routed to a handler |
| `runServiceWorkerRequest(string $name, TServiceWorkerService $service): void` | Writes the response through `$service->getResponse()` |

The first contributor that claims a name answers it. CSRF is the handler's concern (`$service->ensureCsrfToken()` with `EnableCsrfValidation`).

**@since 4.4.0**
