# Web/Services/INDEX.md - WEB_SERVICES_INDEX.md

This file provides guidance to Agents when working with code in this repository.

## Purpose

Application services for the Prado framework. Each service handles a different type of response. Services are registered in `application.xml` and activated by `TApplication` based on the incoming request.

## Classes

- **`TPageService`** — Primary service serving `.page` template-based web pages. Discovers page classes from `BasePath`, lazily loads them, handles directory-level `config.xml` authorization rules, and runs the full `TPage` lifecycle. Requires `TTemplateManager` and `TThemeManager` modules. `onPreRunPage` gives modules access to the `TPage` lifecycle before it starts.

- **`TJsonService`** — Returns JSON-formatted responses. Configures named JSON response handlers; each handler is a class that produces the JSON payload.

- **`TRpcService`** — Generic RPC framework supporting multiple protocols. Architecture:
  - `TRpcProtocol` — marshals the request format (JSON-RPC, XML-RPC, etc.)
  - `TRpcServer` — middleware layer
  - `TRpcApiProvider` — implements the actual API methods
  - Supports WSDL generation for discovery.

- **`TSoapService`** — SOAP/WSDL service. Auto-generates WSDL from provider classes using reflection on `@soapmethod` PHPDoc annotations. Requires PHP's `soap` extension. Supports session persistence.

- **`TFeedService`** — RSS/Atom feed generation. Delegates to content provider classes.

- **`TPageConfiguration`** — Parses per-directory `config.xml`/`config.php` files for page-level module loading, authorization rules, and default property values.

## Conventions

- **Service activation** — `THttpRequest` determines which service to activate based on URL parameters or path patterns. The service ID matches the `id` attribute in `application.xml`.
- **`TPageService` is the default** — Most applications use only `TPageService`; other services are opt-in.
- **SOAP `@soapmethod`** — PHP methods on the provider class must have this annotation for WSDL auto-generation. Strict PHPDoc (`@param`, `@return` with types) is required for accurate WSDL.
- **`TRpcApiProvider` methods** — Must be public; exposed automatically to the RPC layer.
