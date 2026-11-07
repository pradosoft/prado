# Web/Services/ServiceWorker/IServiceWorkerContributor

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [Services](../INDEX.md) / [ServiceWorker](./INDEX.md) / **`IServiceWorkerContributor`**

## Class Info
**Location:** `framework/Web/Services/ServiceWorker/IServiceWorkerContributor.php`
**Namespace:** `Prado\Web\Services\ServiceWorker`

## Overview
Adds code to worker scripts. [TServiceWorkerModule](TServiceWorkerModule.md) collects every application module implementing it; `addContributor()` adds other objects. Chosen over a global `fx` event so contributions are typed and listable (`getContributorObjects()`).

## Method

`contributeServiceWorker(TServiceWorkerScript $script): void` — called once per admitted worker when the browser requests that worker's script. `$script->getWorker()` names the worker; a contributor adds code only to the workers it serves. A worker's `Contributors` restricts admission by module ID.

## Fragment API (in the worker)

A fragment body receives `prado` and `config`: `prado.onFetch((event, request) => promise|undefined)` (before `start()`), `prado.onMessage(type, handler)`, `prado.onInstall(task)`, `prado.onActivate(task)`, `prado.cacheName(name)`, `prado.store()`, `prado.fromCache()`, `prado.fromNetwork(event)`, `prado.isExcluded()`, `prado.isCacheable()`, `prado.postToClients(type, data)`; `self.addEventListener()` for `push`, `notificationclick`, and other events.

## See Also
- [IServiceWorkerRequestHandler](IServiceWorkerRequestHandler.md)
- [TServiceWorkerScript](TServiceWorkerScript.md)

**@since 4.4.0**
