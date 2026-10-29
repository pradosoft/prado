# Web/Services/Rest/TRestService

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [Services](../INDEX.md) / [Rest](INDEX.md) / **`TRestService`**

## Class Info
**Location:** `framework/Web/Services/Rest/TRestService.php`
**Namespace:** `Prado\Web\Services\Rest`
**Extends:** `TService`
**Since:** 4.4.0

## Overview
Serves a JSON API from `IRestResource` classes. `init()` reads the configuration, creates each resource once, compiles the routes it declares into `TRestRoutePattern` objects under `BasePath`, matches the request, and merges the matched route's authorization rules into the application rules. `run()` answers `404` or `405` (with `Allow`), or dispatches to the handler and writes JSON; every error is an `application/problem+json` document.

## Configuration

```xml
<service id="rest" class="Prado\Web\Services\Rest\TRestService" BasePath="api">
  <resource class="App\Api\UsersResource" />
  <resource class="App\Api\PostsResource" prefix="posts" PageSize="50">
    <authorization><deny users="?" verb="POST,PUT,PATCH,DELETE" /></authorization>
  </resource>
  <route verbs="GET" pattern="health" class="App\Api\StatusResource" method="health" name="health" />
  <group prefix="v2"><include file="Application.config.rest-v2" /></group>
  <group prefix="debug" enabled="Debug"><resource class="App\Api\Debug\DumpResource" /></group>
</service>
```

| Element / key | Meaning |
|---|---|
| `resource` (`resources`) | `class`; optional `prefix` (replaces the class attribute prefix), `enabled`, `authorization`, `routes`; any other attribute is a resource property |
| `route` (`routes`) | `class`, `method`, `pattern`; optional `verbs`, `name`, `parameters.x`, `query.x`, `enabled`, `authorization` |
| `group` (`groups`) | `prefix`, `enabled`, plus nested resources, routes, groups, includes, authorization |
| `include` | `file` in PRADO namespace format, loaded in the application's configuration type (`.xml` or `.php`), root holding the same elements |
| `authorization` | `<allow>` / `<deny>` with `users`, `roles`, `verb`, `ips`, `priority`; PHP: `['action' => 'allow', ...]` |
| `enabled` | boolean or `Debug` (enabled in `TApplicationMode::Debug` only) |

The PHP form uses the plural keys `resources`, `routes`, `groups`, and `include` (string or list). The request reaches the service through the URL manager like any service; the service matches `PathInfo` itself.

## Properties
- `BasePath` (`''`) — prefix of every route, slashes trimmed.
- `ExposeErrors` — `500` carries the exception message; defaults to true in Debug mode.
- `RouteClass` (`TRestRoutePattern`) — class of the compiled routes.
- `EnableCsrfValidation` (inherited) — `ensureCsrfToken()` runs inside the error handling, so a failure is a `400` problem document.

## Key Methods
- `getRoutes()`, `getRouteByName($name)`, `getResources()`, `getResource($class)` (namespace or dot notation).
- `getRoute()`, `getPathParameters()`, `getPathMatches()` — the match of the current request, resolved at `init()`.
- `getAllowedVerbs()` — verbs of the routes whose path matched (`STANDARD_VERBS` for a route without `Verbs`; `HEAD` with `GET`).
- `constructUrl($name, $parameters, $getItems, $encodeAmpersand)` — URL of a named route from the application entry script, `HiddenPath` aware.
- `handleUnauthorized($sender, $param)` — `onAuthorization` handler writing the `401` document when `TAuthManager` completed the request.
- Protected seams: `loadConfig()`, `xmlToArray()`, `loadRoutes()`, `registerResource()`, `registerRoute()`, `addRoute()`, `createResource()`, `createAuthorizationRules()`, `loadIncludeFile()`, `isEnabled()`, `joinPath()`, `getDefaultRouteName()`, `resolveRoute()`, `dispatch()`, `castParameter()`, `handleError()`, `sendJsonResponse()`, `sendHeaders()`, `sendErrorResponse()`.

## Events
- `onPreRunResource($resource)` — before `authorize()` and the handler; parameter is the `IRestResource`.
- `onError($exception)` — before the problem document is written; the `TRestException`, or the exception behind a `500`.

## Dispatch
Path parameters are passed by name and cast to the declared scalar type (`int`, `float`, `bool`; a value that does not fit is `404`). A parameter typed `THttpRequest` or `TRestRoutePattern` is injected. A missing parameter uses its default, otherwise `TConfigurationException('restservice_parameter_unbound')` (a `500`). A handler returning `null`, or status `204`, or a `HEAD` request, sends no body. A `JsonSerializable` return value encodes as any array.

## Patterns & Gotchas
- `init()` matches the request before `run()`, so a module may read `getRoute()` from `onInitComplete` on.
- Routes are tried in declaration order; a `<route>` or group that must win goes first.
- A resource class is created once; a second declaration with different properties reuses the first instance.
- Messages: `restservice_class_required`, `restservice_resource_invalid`, `restservice_route_invalid`, `restservice_route_method_required`, `restservice_route_pattern_required`, `restservice_routeclass_invalid`, `restservice_include_not_found`, `restservice_route_unknown`, `restservice_parameter_unbound`, `restservice_http_error`.
