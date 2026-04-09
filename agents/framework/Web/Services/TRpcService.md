# TRpcService

### Directories

[./](../INDEX.md) > [Web](../INDEX.md) > [Services](./INDEX.md) > [TRpcService](./TRpcService.md)

**Location:** `framework/Web/Services/TRpcService.php`
**Namespace:** `Prado\Web\Services`
**Related:** `TRpcServer.php`, `TRpcApiProvider.php` (same namespace)

## Overview

Generic RPC (Remote Procedure Call) service supporting multiple wire protocols. Registered as a `TService` in `application.xml`; activated by `TApplication` for matching requests.

The architecture is a four-layer pipeline:

```
HTTP Request
    ↓
TRpcService          – routes to the correct API provider
    ↓
TRpcServer           – middleware: reads payload, wraps protocol calls
    ↓
TRpcProtocol         – marshals/unmarshals the wire format (JSON-RPC, XML-RPC)
    ↓
TRpcApiProvider      – implements the actual API methods
    ↓
HTTP Response
```

Two built-in protocols are registered by default: `TJsonRpcProtocol` (for `application/json`) and `TXmlRpcProtocol` (for `text/xml`).

### Application Configuration

```xml
<service id="rpc" class="Prado\Web\Services\TRpcService">
    <rpcapi id="customers" class="Application.Api.CustomersApi" />
    <modules>
        <!-- optional modules for this service -->
    </modules>
</service>
```

The `id` on each `<rpcapi>` is the service parameter that the client must supply in the request URL. The `class` is namespace-format.

## Key Constants

| Constant | Value | Description |
|---|---|---|
| `BASE_API_PROVIDER` | `TRpcApiProvider::class` | All API providers must be subclasses of this |
| `BASE_RPC_SERVER` | `TRpcServer::class` | Default server implementation |

## Key Properties

| Property | Type | Description |
|---|---|---|
| `$protocolHandlers` | `array` | MIME type → protocol class name map. Default: `application/json` → `TJsonRpcProtocol`, `text/xml` → `TXmlRpcProtocol` |
| `$apiProviders` | `array` | Registered provider configs keyed by provider ID |

## Key Methods

- `init($config)` — Required `TService` lifecycle method. Calls `loadConfig()`.
- `loadConfig(TXmlElement $xml)` — Parses `<rpcapi>` elements from service XML config. Stores provider attribute maps keyed by their `id`. Throws `TConfigurationException` on missing/duplicate IDs.
- `run()` — Main service handler. Validates that:
  1. A provider ID is present in the request (`getServiceParameter()`).
  2. Request method is `POST` (throws HTTP 405 otherwise).
  3. `Content-Type` header is present (throws HTTP 406 otherwise).
  4. `Content-Type` maps to a known protocol handler (throws HTTP 406 otherwise).
  Then instantiates the protocol handler, creates the API provider, calls `processRequest()`, and writes the result.
- `createApiProvider(TRpcProtocol $protocolHandler, string $providerId)` — Instantiates and configures the provider:
  - Resolves and validates that the class is a subclass of `BASE_API_PROVIDER`.
  - Resolves and validates the optional `server` property (defaults to `BASE_RPC_SERVER`).
  - Constructs `new ProviderClass(new ServerClass($protocolHandler))`.
  - Applies any remaining XML attributes via `setSubProperty()`.

## TRpcServer

**Location:** `framework/Web/Services/TRpcServer.php`

Minimal middleware extending `TModule`. Holds a `TRpcProtocol` instance. Responsibilities:
- `addRpcMethod($name, $details)` — Delegates to `$this->handler->addMethod()`.
- `getPayload()` — Reads raw request body from `php://input`.
- `processRequest()` — Calls `$this->handler->callMethod($payload)`; catches `TRpcException` and converts to an error response via `$this->handler->createErrorResponse()`.

Subclass `TRpcServer` for request/response logging, filtering, or modification.

## TRpcApiProvider

**Location:** `framework/Web/Services/TRpcApiProvider.php`

Abstract `TModule` subclass. Every API must extend this class.

- `abstract registerMethods(): array` — Must return an array mapping RPC method names to handler details:
  ```php
  return [
      'apiMethod1' => ['method' => [$this, 'myMethod1']],
      'apiMethod2' => ['method' => ['ClassName', 'staticMethod']],
  ];
  ```
  Registered automatically in the constructor via `$this->rpcServer->addRpcMethod()`.
- `processRequest()` — Delegates to `$this->rpcServer->processRequest()`.
- `getRpcServer()` — Returns the bound `TRpcServer` instance.

Handler methods receive request parameters directly. Use `func_get_args()` since parameter count is externally supplied.

## Patterns & Gotchas

- **Only POST requests are accepted** — `run()` enforces `RequestType === 'POST'`; all other methods receive HTTP 405.
- **Content-Type determines the protocol** — the client must send `application/json` or `text/xml`; missing or unrecognized types get HTTP 406.
- **Provider ID comes from the URL service parameter** — typically a path segment or query parameter depending on URL manager configuration.
- **`server` attribute in `<rpcapi>`** — optional; allows a custom `TRpcServer` subclass per provider. Must be a subclass of `TRpcServer` or exactly `TRpcServer` itself.
- **Subclass `TRpcServer` for cross-cutting concerns** — logging, rate limiting, request/response transformation all belong in a `TRpcServer` subclass, not in the provider.
- **Method parameters are externally supplied** — always validate with `func_get_args()` in handler methods; never trust argument count.
