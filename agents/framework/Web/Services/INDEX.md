# Web/Services/INDEX.md

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / **`Services`**

## Purpose

Application services for the Prado framework. Each service handles a different type of response. Services are registered in `application.xml` and activated by [TApplication](../../TApplication.md) based on the incoming request.

## Classes

- **[TPageService](TPageService.md)** — Primary service serving `.page` template-based web pages. Discovers page classes from `BasePath`, lazily loads them, handles directory-level `config.xml` authorization rules, and runs the full [TPage](../UI/TPage.md) lifecycle. Requires [TTemplateManager](../UI/TTemplateManager.md) and [TThemeManager](../UI/TThemeManager.md) modules. `onPreRunPage` gives modules access to the `TPage` lifecycle before it starts.

- **[TJsonService](TJsonService.md)** — Returns JSON-formatted responses. Configures named JSON response handlers; each handler is a class that produces the JSON payload.

- **[TRpcService](TRpcService.md)** — Generic RPC framework supporting multiple protocols. Architecture:
  - [TRpcProtocol](TRpcProtocol.md) — marshals the request format (JSON-RPC, XML-RPC, etc.)
  - [TRpcServer](TRpcServer.md) — middleware layer
  - [TRpcApiProvider](TRpcApiProvider.md) — implements the actual API methods
  - Supports WSDL generation for discovery.

- **[TSoapService](TSoapService.md)** — SOAP/WSDL service. Auto-generates WSDL from provider classes using reflection on `@soapmethod` PHPDoc annotations. Requires PHP's `soap` extension. Supports session persistence.

- **[TFeedService](TFeedService.md)** — RSS/Atom feed generation. Delegates to content provider classes.

- **[TPageConfiguration](TPageConfiguration.md)** — Parses per-directory `config.xml`/`config.php` files for page-level module loading, authorization rules, and default property values.

## Conventions

- **Service activation** — [THttpRequest](../THttpRequest.md) determines which service to activate based on URL parameters or path patterns. The service ID matches the `id` attribute in `application.xml`.
- **TPageService is the default** — Most applications use only `TPageService`; other services are opt-in.
- **SOAP `@soapmethod`** — PHP methods on the provider class must have this annotation for WSDL auto-generation. Strict PHPDoc (`@param`, `@return` with types) is required for accurate WSDL.
- **TRpcApiProvider methods** — Must be public; exposed automatically to the RPC layer.
