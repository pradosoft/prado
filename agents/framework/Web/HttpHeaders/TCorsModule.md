# Web/HttpHeaders/TCorsModule

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / HttpHeaders / **`TCorsModule`**

## Class Info
**Location:** `framework/Web/HttpHeaders/TCorsModule.php`
**Namespace:** `Prado\Web\HttpHeaders`
**Extends:** `TModule`
**Since:** 4.4.0

## Overview
Sends the Cross-Origin Resource Sharing response headers for every service from one module and answers preflight requests. `init()` validates the configuration and attaches `handleRequest()` to `TApplication::onBeginRequest`, so the headers go out before any service runs. A preflight (`OPTIONS` with `Access-Control-Request-Method`) gets status `204` and `TApplication::completeRequest()`, which stops the lifecycle before `runService`; `onEndRequest` still flushes the output. It replaces the per-service CORS code that `TRestService` carried.

```xml
<module id="cors" class="Prado\Web\HttpHeaders\TCorsModule" AllowOrigin="https://app.example.com" AllowCredentials="true" PathPrefix="api" />
```

## API
- `AllowOrigin` (`'*'`) — `'*'` allows every origin; otherwise a comma-separated allow-list of exact origins (no patterns), trimmed and compared case-insensitively by `isOriginAllowed()`. Never list `null` with credentials: it is the origin of every sandboxed document.
- `AllowMethods` (`'GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD'`), `AllowHeaders` (`'Content-Type, Authorization, X-Requested-With, Accept, X-Prado-Csrf-Token'`), `MaxAge` (`86400`) — preflight-only values; an empty `AllowMethods` sends no header and an empty `AllowHeaders` echoes the preflight's `Access-Control-Request-Headers` (`getRequestedHeaders()`, reduced to token characters).
- `ExposeHeaders` (`''`) — sent as `Access-Control-Expose-Headers` on an actual request when not empty; never on a preflight.
- `AllowCredentials` (`false`) — sends `Access-Control-Allow-Credentials: true`, on preflights too. `init()` and `handleRequest()` throw `TConfigurationException('corsmodule_credentials_wildcard')` when it is on while `AllowOrigin` is `'*'`.
- `PathPrefix` (`''`) — `appliesToPath($pathInfo)` strips the leading slash and surrounding slashes of the prefix, then requires equality or continuation at a `/` boundary: `api` matches `api` and `api/users`, not `apidocs`. It compares the raw `PathInfo`, so it needs the `Path` or `HiddenPath` URL format; in the `Get` format `PathInfo` is empty and a prefix matches nothing.
- `handleRequest($sender, $param)` — the `onBeginRequest` handler; public because `TComponent::raiseEvent()` calls it.
- Constants: `WILDCARD_ORIGIN`, `DEFAULT_ALLOW_METHODS`, `DEFAULT_ALLOW_HEADERS`, `DEFAULT_MAX_AGE`. The request header names are `THttpHeaderName::AccessControlRequestMethod` and `AccessControlRequestHeaders`.

| Request | Headers sent |
|---|---|
| No `Origin` header, or path outside `PathPrefix` | none |
| Origin outside the allow-list | `Vary: Origin` only |
| Allowed origin, `AllowOrigin` is `*`, credentials off | `Access-Control-Allow-Origin: *` |
| Allowed origin, allow-list or credentials on | `Access-Control-Allow-Origin: <origin>`, `Vary: Origin` |
| Credentials on | adds `Access-Control-Allow-Credentials: true` |
| Actual request, `ExposeHeaders` set | adds `Access-Control-Expose-Headers` |
| Preflight | adds `Access-Control-Allow-Methods` and `Access-Control-Allow-Headers` when not empty, `Access-Control-Max-Age`; status `204`; request completed |

## Gotchas
- The origin is echoed back (never the raw allow-list) whenever the value is not `*`, because browsers accept a single origin only. `Vary: Origin` is appended with `replace=false` so an existing `Vary` line is kept, and it goes out on every response whose allowed origin depends on the request origin, a refused origin included, so a shared cache never serves one origin's response to another.
- A plain `OPTIONS` without `Access-Control-Request-Method` is not a preflight: it gets the origin headers only and the service runs.
- The preflight answer does not compare `Access-Control-Request-Method` with `AllowMethods`; the browser enforces the lists. A preflight completes the request from `onBeginRequest`, before authentication, as a preflight carries no credentials.
- A disallowed origin gets no `Access-Control-*` headers, which is how the browser is told to block the response; the service still runs and the status is unchanged.
- Headers go through `THttpResponse::appendHeader()`, which calls `header()` directly. Tests substitute a recording `THttpResponse` via `TApplication::setResponse()`.
- `THttpRequest::init()` forces `REQUEST_METHOD` to `GET` under the CLI SAPI and caches `PathInfo`; the headers are read on the first `getHeaders()` call. Tests set `$_SERVER['PATH_INFO']` before `init()`, `HTTP_*` before the first header lookup, and the method after `init()`.
