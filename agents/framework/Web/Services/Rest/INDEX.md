# Web/Services/Rest/INDEX.md

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [Services](../INDEX.md) / **`Rest`**

## Purpose

A JSON HTTP API service for PRADO, the backend of a single-page application or of any API client. Resources declare which verb and URL pattern each handler method serves; the service matches the request and calls the handler with the path parameters. Errors are RFC 9457 problem documents. **Since 4.4.0.** `TRestClient` in `framework/IO/Rest/` is the consumer side and shares the `{name}` placeholder syntax.

## Classes

- **[TRestService](TRestService.md)** — The `TService`. Reads `<resource>`, `<route>`, `<group>`, `<include>` and `<authorization>` configuration, builds the route table, matches the path then the verb (`404`, `405` with `Allow`), dispatches with typed path parameters, writes JSON or a problem document. Raises `onPreRunResource` and `onError`. `constructUrl($name, $params)` is reverse routing.
- **[TRestResource](TRestResource.md)** — Base class of a resource (`TApplicationComponent`, implements `IRestResource`). `getRoutes()` reads the `#[TRestRoute]` attributes up the parent classes, or is overridden to return a table. Request helpers over `THttpRequest::getParsedBody()`, status and header helpers, exception helpers, `validate()` over `TInputValidator`.
- **[IRestResource](IRestResource.md)** — The service-to-resource contract: `getRoutes()`, `getRoutePrefix()`, `setRoute()`, `authorize()`, `getStatusCode()`, `getResponseHeaders()`.
- **[TRestRoute](TRestRoute.md)** — The PHP attribute (`TARGET_CLASS | TARGET_METHOD`, repeatable) declaring verbs, pattern, parameter and query constraints, and a route name.
- **[TRestRoutePattern](TRestRoutePattern.md)** — One route: a `TUrlMappingPattern` subclass with inline `{id:\d+}` constraints, `matchPath()` apart from the verb, `acceptsVerb()`, `constructPath()`, the handler class and method, and the authorization rules.
- **[TRestException](TRestException.md)** — `THttpException` (so an `IUserException`) with `title`, `detail`, `errors`; `toArray()` is the problem document; static factories per status.
- **[TRestPagination](TRestPagination.md)** — `page` / `per_page` helper producing `offset` / `limit` and a `{data, meta}` envelope.

## Conventions

- **Pattern syntax is the URL manager's.** `{name}`, `parameters.name`, inline `{name:regex}`, `query.name`, `Verbs`, `CaseSensitive`. A placeholder without a constraint matches one segment.
- **Path first, verb second.** The first route whose path and verb match wins; a path matched by other verbs is `405` with `Allow`. `HEAD` runs the `GET` handler without a body.
- **Routes inherit.** An override without `#[TRestRoute]` keeps the parent's route; the class prefix comes from the nearest ancestor carrying the attribute; `getRoutes()` may be overridden (`parent::getRoutes() + [...]`).
- **One instance per resource class per request**, created at `init()` to read its routes and reused for dispatch; declare a class once.
- **Authorization** rules of the matched route are merged into `TApplication::getAuthorizationRules()` at `init()`, enforced by `TAuthManager`; the service writes the `401` problem document from its `onAuthorization` handler. `IRestResource::authorize()` is the per-resource hook.
- **CSRF**: `TService.EnableCsrfValidation` applies through `ensureCsrfToken()`, a `400` problem document on failure.
- **CORS** is not here: `Prado\Web\HttpHeaders\TCorsModule` with `PathPrefix` equal to `BasePath`.
- **Problem documents** go out as `application/problem+json` (`TMediaType::PROBLEM_JSON`). A `500` carries the message only when `ExposeErrors` is on (Debug mode default).
- **Tests** live in `tests/unit/Web/Services/Rest/`; `UsersFixtureResource` is the shared attribute-declared fixture and `fixtures/rest-include.{xml,php}` the include files.
