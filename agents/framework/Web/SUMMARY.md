# Web/SUMMARY.md

HTTP layer, URL routing, asset management, session handling, and all web UI components.

## Classes

- **`THttpRequest`** — Encapsulates incoming HTTP request; implements `ArrayAccess`/`IteratorAggregate` for unified GET+POST access; `getBrowser()` requires the PHP `browscap` directive and reports a configuration exception when absent.

- **`THttpResponse`** — HTTP response output; manages status codes, headers, cookies, content type, charset, output buffering, file downloads (`writeFile()`, with byte ranges), redirects.

- **`THttpSession`** — PHP session wrapper implementing `ArrayAccess`; properties: `AutoStart`, `SessionName`, `CookieMode`, `GCProbability`; supports custom storage via `THttpSessionHandler`.

- **`TAssetManager`** — Publishes private framework and application assets to a web-accessible directory; symlink publication tolerates a destination created concurrently and rethrows only when it remains absent.

- **`TUrlManager`** — Base URL manager; constructs URLs in `Get`, `Path`, and `HiddenPath` formats; parses incoming URLs into GET variables.

- **`TUrlMapping`** — Advanced SEF URL routing with regex-based patterns (`TUrlMappingPattern`), named parameter extraction, and query string matching.

- **`TUri`** — URI parsing and construction (scheme, host, port, path, query string).

- **`THttpCookie`** / **`THttpCookieCollection`** — Cookie objects with `Domain`, `Path`, `ExpireTime`, `HttpOnly`, `SameSite` attributes.
