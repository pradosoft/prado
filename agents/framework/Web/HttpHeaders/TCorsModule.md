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
- `AllowOrigin` (`'*'`) — `'*'` allows every origin; otherwise a comma-separated allow-list, trimmed and compared case-insensitively by `isOriginAllowed()`.
- `AllowMethods` (`'GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD'`), `AllowHeaders` (`'Content-Type, Authorization, X-Requested-With, Accept, X-Prado-Csrf-Token'`), `MaxAge` (`86400`) — preflight-only values.
- `ExposeHeaders` (`''`) — sent as `Access-Control-Expose-Headers` when not empty.
- `AllowCredentials` (`false`) — sends `Access-Control-Allow-Credentials: true`. `init()` and `handleRequest()` throw `TConfigurationException('corsmodule_credentials_wildcard')` when it is on while `AllowOrigin` is `'*'`.
- `PathPrefix` (`''`) — `appliesToPath($pathInfo)` strips the leading slash and surrounding slashes of the prefix, then requires equality or continuation at a `/` boundary: `api` matches `api` and `api/users`, not `apidocs`.
- `handleRequest($sender, $param)` — the `onBeginRequest` handler. Public so it can be invoked directly in tests.
- Constants: `WILDCARD_ORIGIN`, `REQUEST_METHOD_HEADER` (`Access-Control-Request-Method`, which `THttpHeaderName` does not define), `DEFAULT_ALLOW_METHODS`, `DEFAULT_ALLOW_HEADERS`, `DEFAULT_MAX_AGE`.

| Request | Headers sent |
|---|---|
| No `Origin` header | none |
| Origin outside the allow-list, or path outside `PathPrefix` | none |
| Allowed origin, `AllowOrigin` is `*`, credentials off | `Access-Control-Allow-Origin: *` |
| Allowed origin, allow-list or credentials on | `Access-Control-Allow-Origin: <origin>`, `Vary: Origin` |
| Credentials on | adds `Access-Control-Allow-Credentials: true` |
| `ExposeHeaders` set | adds `Access-Control-Expose-Headers` |
| Preflight | adds `Access-Control-Allow-Methods`, `Access-Control-Allow-Headers`, `Access-Control-Max-Age`; status `204`; request completed |

## Gotchas
- The origin is echoed back (never the raw allow-list) whenever the value is not `*`, because browsers accept a single origin only. `Vary: Origin` is appended with `replace=false` so an existing `Vary` line is kept.
- A plain `OPTIONS` without `Access-Control-Request-Method` is not a preflight: it gets the origin headers only and the service runs.
- A disallowed origin gets no CORS headers at all, which is how the browser is told to block the response; the service still runs and the status is unchanged.
- Headers go through `THttpResponse::appendHeader()`, which calls `header()` directly. Tests substitute a recording `THttpResponse` via `TApplication::setResponse()`.
- `THttpRequest::init()` forces `REQUEST_METHOD` to `GET` under the CLI SAPI and caches `PathInfo` and the headers, so tests set `$_SERVER['PATH_INFO']` and `HTTP_*` before `init()` and the method after it.
