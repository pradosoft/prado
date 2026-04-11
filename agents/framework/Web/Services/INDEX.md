# Web/Services/INDEX.md

### Directories
[framework](./INDEX.md) / [Web](./Web/INDEX.md) / **`Services/INDEX.md`**

## Purpose

Application services for the Prado framework. Each service handles a different type of response. Services are registered in `application.xml` and activated by [TApplication](framework/TApplication.md) based on the incoming request.

## Classes

- **[TPageService](Web/Services/TPageService.md)** — Primary service serving `.page` template-based web pages. Discovers page classes from `BasePath`, lazily loads them, handles directory-level `config.xml` authorization rules, and runs the full [TPage](Web/UI/TPage.md) lifecycle. Requires [TTemplateManager](Web/UI/TTemplateManager.md) and [TThemeManager](Web/UI/TThemeManager.md) modules. `onPreRunPage` gives modules access to the `TPage` lifecycle before it starts.

- **[TJsonService](Web/Services/TJsonService.md)** — Returns JSON-formatted responses. Configures named JSON response handlers; each handler is a class that produces the JSON payload.

- **[TRpcService](Web/Services/TRpcService.md)** — Generic RPC framework supporting multiple protocols. Architecture:
  - [TRpcProtocol](Web/Services/TRpcProtocol.md) — marshals the request format (JSON-RPC, XML-RPC, etc.)
  - [TRpcServer](Web/Services/TRpcServer.md) — middleware layer
  - [TRpcApiProvider](Web/Services/TRpcApiProvider.md) — implements the actual API methods
  - Supports WSDL generation for discovery.

- **[TSoapService](Web/Services/TSoapService.md)** — SOAP/WSDL service. Auto-generates WSDL from provider classes using reflection on `@soapmethod` PHPDoc annotations. Requires PHP's `soap` extension. Supports session persistence.

- **[TFeedService](Web/Services/TFeedService.md)** — RSS/Atom feed generation. Delegates to content provider classes.

- **[TPageConfiguration](Web/Services/TPageConfiguration.md)** — Parses per-directory `config.xml`/`config.php` files for page-level module loading, authorization rules, and default property values.

## Conventions

- **Service activation** — [THttpRequest](Web/THttpRequest.md) determines which service to activate based on URL parameters or path patterns. The service ID matches the `id` attribute in `application.xml`.
- **TPageService is the default** — Most applications use only `TPageService`; other services are opt-in.
- **SOAP `@soapmethod`** — PHP methods on the provider class must have this annotation for WSDL auto-generation. Strict PHPDoc (`@param`, `@return` with types) is required for accurate WSDL.
- **TRpcApiProvider methods** — Must be public; exposed automatically to the RPC layer.
