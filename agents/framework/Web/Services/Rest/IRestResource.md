# Web/Services/Rest/IRestResource

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [Services](../INDEX.md) / [Rest](INDEX.md) / **`IRestResource`**

## Class Info
**Location:** `framework/Web/Services/Rest/IRestResource.php`
**Namespace:** `Prado\Web\Services\Rest`
**Since:** 4.4.0

## Overview
The contract between `TRestService` and a resource. `TRestResource` implements it; a class created through `Prado::createComponent()` that implements it directly also serves (its properties from the configuration are applied only when it is a `TComponent`).

| Method | Called by the service |
|---|---|
| `getRoutes(): array<string, TRestRoute[]>` | at `init()`, to build the route table |
| `getRoutePrefix(): string` | at `init()`, unless the configuration gives `prefix` |
| `setRoute(TRestRoutePattern $route, array $parameters)` | before dispatch |
| `getRoute()`, `getPathParameters()` | by handlers |
| `authorize(TRestRoutePattern $route)` | after `onPreRunResource`, before the handler; throws to refuse |
| `getStatusCode()`, `getResponseHeaders()` | after the handler, to write the response |
