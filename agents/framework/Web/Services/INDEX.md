# Web/Services/INDEX.md

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / **`Services`**

## Purpose

Application services for the Prado framework. Each service handles a different type of response. Services are registered in `application.xml` and activated by [TApplication](../../TApplication.md) based on the incoming request.

## Classes

- **[TPageService](TPageService.md)** — Primary service serving `.page` template-based web pages. Discovers page classes from `BasePath`, lazily loads them, handles directory-level `config.xml` authorization rules, and runs the full [TPage](../UI/TPage.md) lifecycle. Requires [TTemplateManager](../UI/TTemplateManager.md) and [TThemeManager](../UI/TThemeManager.md) modules. `onPreRunPage` gives modules access to the `TPage` lifecycle before it starts.

- **[TJsonService](TJsonService.md)** — Returns JSON-formatted responses. Configures named JSON response handlers; each handler is a class that produces the JSON payload.

- **[TSoapService](TSoapService.md)** — SOAP/WSDL service. Auto-generates WSDL from provider classes using reflection on `@soapmethod` PHPDoc annotations. Requires PHP's `soap` extension. Supports session persistence.

- **[TFeedService](TFeedService.md)** — RSS/Atom feed generation. Delegates to content provider classes.

- **[TPageConfiguration](TPageConfiguration.md)** — Parses per-directory `config.xml`/`config.php` files for page-level module loading, authorization rules, and default property values.

## RPC: the `pradosoft/prado-rpc` package

JSON-RPC and XML-RPC left the framework in 4.4.0 (#1210) for the [`pradosoft/prado-rpc`](https://github.com/pradosoft/prado-rpc) package. Nothing RPC remains under `framework/`.

| Side | Namespace | Classes |
|---|---|---|
| Server | `Prado\Web\Services` (unchanged) | `TRpcService`, `TRpcServer`, `TRpcProtocol`, `TJsonRpcProtocol`, `TXmlRpcProtocol`, `TRpcApiProvider`, `TRpcException` |
| Client | `Prado\IO\Rpc` (was `Prado\Util`) | `TRpcClient`, `TJsonRpcClient`, `TXmlRpcClient`, `TXmlRpcCodec`, `TXmlRpcBase64`, `TRpcClientRequestException`, `TRpcClientResponseException` |

How it integrates:

- `composer.json` is `type: prado4-extension` with PSR-4 `Prado\` → `src/`, so the package adds classes to the framework's own namespaces. Its `extra.prado.class-map` (`config/classes.json`, the short names) and `extra.prado.error-messages` (`config/messages.txt`, the `rpc*` keys) are read by [`TApplicationConfiguration`](../../TApplicationConfiguration.md) from every installed extension; there is no bootstrap module and no configuration change. `<service id="rpc" class="TRpcService">` with `<rpcapi id class>` children works as before.
- Request flow: `TRpcService` → `TRpcServer` → protocol chosen by request `Content-Type` (`application/json` → `TJsonRpcProtocol`, 1.0 and 2.0; `text/xml` → `TXmlRpcProtocol`) → `TRpcApiProvider::registerMethods()`. A fault is a `TRpcException` with a protocol fault code.
- Client flow: `TRpcClient::create('json'|'xml', $url)`; `__call` encodes the method and POSTs through [`THttpClient`](../../IO/HttpClient/INDEX.md) (`setDownloader()` swaps the transport); transport failures throw `TRpcClientRequestException`, faults `TRpcClientResponseException`.
- `TXmlRpcCodec` is a pure-PHP XML-RPC codec on `ext-dom` and `ext-simplexml`; the PECL `xmlrpc` extension is not needed. `TRpcClientTypesEnumerable` was not carried over.

What this repository owes it. The package runs its CI against `pradosoft/prado` `dev-master`, so these framework APIs are its contract: `TService` and `ensureCsrfToken()`, `THttpRequest::getContentType()`, `THttpHeaderName`, `TMediaType`, `TXmlElement`, `TMap`, `TConfigurationException`, `THttpException`, `Prado::createComponent()`, `TApplicationComponent`, `TPropertyValue`, `THttpClient` and `THttpClientException`, and the `extra.prado` loading in `TApplicationConfiguration`. A change to one of them is a change to `prado-rpc`.

Gauging a proposal against it: a method-call API on one POST endpoint (JSON-RPC, XML-RPC) belongs in `prado-rpc`, and a new wire format is a `TRpcProtocol` subclass there. A resource-and-verb HTTP API, body parsing, CORS, or authorization is a framework concern. The package's own `CLAUDE.md`, `agents/INDEX.md`, and `docs/` (`server.md`, `client.md`, `xml-rpc-codec.md`, `migration.md`) follow this repository's conventions and are the reference for its internals. It is versioned and released on its own.

## Conventions

- **Service activation** — [THttpRequest](../THttpRequest.md) determines which service to activate based on URL parameters or path patterns. The service ID matches the `id` attribute in `application.xml`.
- **TPageService is the default** — Most applications use only `TPageService`; other services are opt-in.
- **CSRF** — [TPage](../UI/TPage.md) `EnableCsrfValidation` (on by default) checks every postback and callback. `TJsonService`, `TSoapService`, and `TFeedService` check POST requests through `TService::ensureCsrfToken()` when `EnableCsrfValidation` is set on the service (off by default). `TCspReportingService` takes browser reports, which carry no token, and does not check.
- **SOAP `@soapmethod`** — PHP methods on the provider class must have this annotation for WSDL auto-generation. Strict PHPDoc (`@param`, `@return` with types) is required for accurate WSDL.
