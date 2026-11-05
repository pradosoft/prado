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
| `resource` (`resources`) | `class`; optional `prefix` (replaces the class attribute prefix), `enabled`, `authorization`, `routes`; any other attribute, or a PHP `properties` array, is a resource property |
| `route` (`routes`) | `class`, `method`, `pattern`; optional `verbs`, `name`, `parameters.x`, `query.x`, `enabled`, `authorization` |
| `group` (`groups`) | `prefix`, `enabled`, plus nested resources, routes, groups, includes, authorization |
| `include` | `file` in PRADO namespace format, loaded in the application's configuration type (`.xml` or `.php`), root holding the same elements |
| `authorization` | `<allow>` / `<deny>` with `users`, `roles`, `verb`, `ips`, `priority`; PHP: `['action' => 'allow', ...]` |
| `enabled` | boolean or `Debug` (enabled in `TApplicationMode::Debug` only) |

The PHP form uses the plural keys `resources`, `routes`, `groups`, and `include` (string or list). The request reaches the service through the URL manager like any service; the service matches `getRoutePath()` itself.

## Route path
`getRoutePath()` is the path the routes (with `BasePath`) are matched against, and `constructUrl()` is its reverse:

| URL format | Request | Route path | `constructUrl()` |
|---|---|---|---|
| `Get` | `index.php?rest=users/42` | the service parameter, `users/42` | `index.php?rest=users/42&page=2` |
| `Path` | `index.php/rest/users/42` | `PathInfo` minus the leading service ID segment, `users/42` | `index.php/rest/users/42?page=2` |
| `HiddenPath` | `/rest/users/42` | `users/42` | `/rest/users/42` |
| `TUrlMapping` rule `pattern="api/"` for `ServiceID="rest"` | `index.php/api/users/42` | `PathInfo` as is, `api/users/42`; `BasePath="api"` | as the format above; override `constructUrl()` for the mapped URL |

## Properties
- `BasePath` (`''`) — prefix of every route, slashes trimmed.
- `ExposeErrors` — `500` carries the exception message; unset, it is true in Debug mode only. A configured value survives `init()`.
- `RouteClass` (`TRestRoutePattern`) — class of the compiled routes.
- `EnableCsrfValidation` (inherited) — `ensureCsrfToken()` runs inside the error handling, so a failure is a `400` problem document.

## Key Methods
- `getRoutes()`, `getRouteByName($name)`, `getResources()`, `getResource($class)` (namespace or dot notation).
- `getRoute()`, `getPathParameters()`, `getPathMatches()` — the match of the current request, resolved at `init()`.
- `getAllowedVerbs()` — verbs of the routes whose path matched (`STANDARD_VERBS` for a route without `Verbs`; `HEAD` with `GET`).
- `getRoutePath()` — the matched path (see *Route path*).
- `constructUrl($name, $parameters, $getItems, $encodeAmpersand)` — URL of a named route from the application entry script in the request's URL format.
- `handleUnauthorized($sender, $param)` — `onAuthorization` handler writing the `401` document when `TAuthManager` completed the request.
- Protected seams: `loadConfig()`, `xmlToArray()`, `loadRoutes()`, `registerResource()`, `registerRoute()`, `addRoute()`, `createResource()`, `applyResourceProperties()`, `createAuthorizationRules()`, `loadIncludeFile()`, `isEnabled()`, `joinPath()`, `getDefaultRouteName()`, `resolveRoute()`, `dispatch()`, `castParameter()`, `handleError()`, `sendJsonResponse()`, `sendHeaders()`, `sendErrorResponse()`.

## Events
- `onPreRunResource($resource)` — before `authorize()` and the handler; parameter is the `IRestResource`.
- `onError($exception)` — before the problem document is written; the `TRestException`, or the exception behind a `500`.

## Dispatch
The handler is resolved with `TComponentReflection::getReflectionMethodByType()`, so a behavior-added method serves too; a static or non-public method is `405`. Path parameters are passed by name and cast to the declared type: `int`, `float`, `bool`, or a backed enum through `tryFrom()`; a value that does not fit is `404`. A parameter typed `THttpRequest` or `TRestRoutePattern` is injected. A missing parameter uses its default, otherwise `TConfigurationException('restservice_parameter_unbound')` (a `500`). A handler returning `null`, or status `204`, or a `HEAD` request, sends no body. A `JsonSerializable` return value encodes as any array. A `500` logs the exception class and message under the `TRestService` category.

## Patterns & Gotchas
- `init()` matches the request before `run()`, so a module may read `getRoute()` from `onInitComplete` on.
- Routes are tried in declaration order; a `<route>` or group that must win goes first.
- A resource class is created once; each declaration applies its properties at `init()`, and `run()` applies the matched route's `ResourceProperties` again, so a class declared twice with different properties serves each route with its own.
- Messages: `restservice_class_required`, `restservice_resource_invalid`, `restservice_route_invalid`, `restservice_route_method_required`, `restservice_route_pattern_required`, `restservice_routeclass_invalid`, `restservice_include_not_found`, `restservice_route_unknown`, `restservice_parameter_unbound`, `restservice_http_error`.
