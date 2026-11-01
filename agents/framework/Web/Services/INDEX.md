# Web/Services/INDEX.md

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / **`Services`**

| Directory | Purpose |
|---|---|
| [ServerEvents/](ServerEvents/INDEX.md) | Server push: [`TServerEventService`](ServerEvents/TServerEventService.md), the [`IServerEventBroker`](ServerEvents/IServerEventBroker.md) modules, [`TServerEventStreamer`](ServerEvents/TServerEventStreamer.md) |

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

- **[TServerEventService](ServerEvents/TServerEventService.md)** — Server-sent events: streams one channel as `text/event-stream` to a browser `EventSource`, with a channel token bound to the user, `retry:`, heartbeats, `Last-Event-ID` resume, a released session, and a bounded `MaxLifetime`. Events come from an [IServerEventBroker](ServerEvents/IServerEventBroker.md) module: [TServerEventCacheBroker](ServerEvents/TServerEventCacheBroker.md) (polls an `ICache`, across processes) or [TServerEventMemoryBroker](ServerEvents/TServerEventMemoryBroker.md) (wakes fibers on the loop, one process). [TServerEventStreamer](ServerEvents/TServerEventStreamer.md) writes the stream to an [IServerEventSink](ServerEvents/IServerEventSink.md), so an event loop server, such as an HTTP/2 server, can host it. [TServerEventTriggeredCallback](../UI/ActiveControls/TServerEventTriggeredCallback.md) is the page side. The classes are in [ServerEvents/](ServerEvents/INDEX.md) (namespace `Prado\Web\Services\ServerEvents`).

- **[TPageConfiguration](TPageConfiguration.md)** — Parses per-directory `config.xml`/`config.php` files for page-level module loading, authorization rules, and default property values.

## Conventions

- **Service activation** — [THttpRequest](../THttpRequest.md) determines which service to activate based on URL parameters or path patterns. The service ID matches the `id` attribute in `application.xml`.
- **TPageService is the default** — Most applications use only `TPageService`; other services are opt-in.
- **CSRF** — [TPage](../UI/TPage.md) `EnableCsrfValidation` (on by default) checks every postback and callback. `TJsonService`, `TSoapService`, and `TFeedService` check POST requests through `TService::ensureCsrfToken()` when `EnableCsrfValidation` is set on the service (off by default). `TCspReportingService` takes browser reports, which carry no token, and does not check. `TServerEventService` streams on GET and checks its channel token instead.
- **SOAP `@soapmethod`** — PHP methods on the provider class must have this annotation for WSDL auto-generation. Strict PHPDoc (`@param`, `@return` with types) is required for accurate WSDL.
- **TRpcApiProvider methods** — Must be public; exposed automatically to the RPC layer.
