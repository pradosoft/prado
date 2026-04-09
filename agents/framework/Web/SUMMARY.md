# SUMMARY.md

HTTP layer, URL routing, asset management, session handling, and all web UI components.

## Classes

- **`THttpRequest`** — Encapsulates incoming HTTP request; implements `ArrayAccess`/`IteratorAggregate` for unified GET+POST access; properties: `RequestType`, `Url`, `ServerPort`, `IsSecureConnection`, `PathInfo`.

- **`THttpResponse`** — HTTP response output; manages status codes, headers, cookies, content type, charset, output buffering, file downloads (`writeFile()`), redirects.

- **`THttpSession`** — PHP session wrapper implementing `ArrayAccess`; properties: `AutoStart`, `SessionName`, `CookieMode`, `GCProbability`; supports custom storage via `THttpSessionHandler`.

- **`TAssetManager`** — Publishes private `framework/` and application assets to web-accessible `assets/` directory; uses timestamp-based caching; methods: `getPublishedPath($dir)`.

- **`TUrlManager`** — Base URL manager; constructs URLs in `Get`, `Path`, and `HiddenPath` formats; parses incoming URLs into GET variables.

- **`TUrlMapping`** — Advanced SEF URL routing with regex-based patterns (`TUrlMappingPattern`) and named parameter extraction.

- **`TUri`** — URI parsing and construction (scheme, host, port, path, query string).

- **`THttpCookie`** / **`THttpCookieCollection`** — Cookie objects with `Domain`, `Path`, `ExpireTime`, `HttpOnly`, `SameSite` attributes.
