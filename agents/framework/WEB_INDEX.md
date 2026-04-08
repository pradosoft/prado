# Web/INDEX.md - WEB_INDEX.md

This file provides guidance to Agents when working with code in this repository.

### Subdirectories

| Directory | Purpose |
|---|---|
| [`Behaviors/`](WEB_BEHAVIORS_INDEX.md) | `TRequestConnectionUpgrade` — WebSocket HTTP 101 upgrade handler |
| [`Javascripts/`](WEB_JAVASCRIPTS_INDEX.md) | JS/CSS package registry and asset utilities. PHP classes: `TJavaScript`, `TJavaScriptAsset`, `TJavaScriptLiteral`, `TJavaScriptString`. Package manifests: `packages.php` (JS) and `css-packages.php` (CSS) consumed by `TClientScriptManager`. Source packages include: prado core, ajax/callbacks, validator, datepicker, colorpicker, ratings, accordion, tabpanel, slider, keyboard, htmlarea (v3/v4 and v5+), activefileupload. External packages: jQuery, jQuery UI, TinyMCE, HighlightJS, Clipboard. Always register packages via `TClientScriptManager::registerPackage()` — never reference JS files directly. Use `TJavaScriptLiteral` for raw JS expressions and `TJavaScript::quoteJsLiteral()` for safe PHP→JS encoding. |
| [`Services/`](WEB_SERVICES_INDEX.md) | `TPageService`, `TJsonService`, `TRpcService`, `TSoapService`, `TFeedService` |
| [`UI/`](WEB_UI_INDEX.md) | All UI controls: `TControl`, `TPage`, `TTemplateControl`, `TTemplate`, theming/skins, client scripts |

## Purpose

HTTP layer, URL routing, asset management, session handling, and all web UI components for the Prado framework.

## Top-Level Classes

- **`THttpRequest`** — Encapsulates the incoming HTTP request. Implements `ArrayAccess`/`IteratorAggregate` for unified GET+POST access. Manages URL parsing, path-info extraction, service routing, and cookie access. Lazily loads `TUrlManager`. Properties: `RequestType`, `Url`, `ServerPort`, `IsSecureConnection`, `PathInfo`.

- **`THttpResponse`** — HTTP response output. Manages status codes, headers, cookies, content type, charset, output buffering, file downloads (`writeFile()`), and redirects. Extended by `THttpResponseAdapter` for callback/AJAX responses.

- **`THttpSession`** — PHP session wrapper implementing `ArrayAccess`. Properties: `AutoStart`, `SessionName`, `CookieMode`, `GCProbability`. Supports custom storage via `THttpSessionHandler`. Cookie attributes: `HttpOnly`, `SameSite`.

- **`TAssetManager`** — Publishes private `framework/` and application assets to a web-accessible `assets/` directory. Uses timestamp-based caching. Handles recursive directory publishing and TAR extraction. Access via `$app->getAssetManager()`.

- **`TUrlManager`** — Base URL manager. Constructs URLs in `Get`, `Path`, and `HiddenPath` formats. Parses incoming URLs into GET variables.

- **`TUrlMapping`** — Advanced SEF (Search Engine Friendly) URL routing. Defines regex-based patterns (`TUrlMappingPattern`) with named parameter extraction and optional secure-connection enforcement.

- **`TUri`** — URI parsing and construction (scheme, host, port, path, query string).

- **`THttpCookie`** / **`THttpCookieCollection`** — Cookie objects with `Domain`, `Path`, `ExpireTime`, `HttpOnly`, `SameSite` attributes.


## Key Patterns

- **Lazy `TUrlManager` init** — URL manager is loaded on first URL construction or request parse; don't assume it's available before `TApplication::onBeginRequest`.
- **`TAssetManager` path publishing** — Publish a directory once; the manager caches decisions by timestamp. Call `$assetMgr->getPublishedPath($dir)` to get the public URL.
- **`THttpResponse` buffering** — Output is buffered; call `flush()` to send to client. The page lifecycle does this automatically.
- **Session lazy start** — `THttpSession` does not start PHP's session until `open()` is called or a value is accessed. Set `AutoStart=true` in config to start eagerly.
