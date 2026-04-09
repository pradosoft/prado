# TJsonRpcProtocol

### Directories

[./](../INDEX.md) > [Web](../INDEX.md) > [Services](./INDEX.md) > [TJsonRpcProtocol](./TJsonRpcProtocol.md)

**Location:** `framework/Web/Services/TJsonRpcProtocol.php`
**Namespace:** `Prado\Web\Services`

## Overview

TJsonRpcProtocol implements the JSON-RPC protocol for TRpcService, supporting both version 1.0 and 2.0 of the specification. The server automatically responds using the same protocol version as the requesting client. Handles method calls, notifications, and proper error responses.

## Key Properties/Methods

- **`$_specificationVersion`** - Tracks the JSON-RPC specification version (1.0 or 2.0).
- **`$_id`** - Stores the request ID for matching responses.
- **`callMethod($requestPayload)`** - Processes JSON-RPC request, validates protocol version, extracts method/params, returns response.
- **`callApiMethod($methodName, $parameters)`** - Overrides parent to use JSON-RPC error codes.
- **`createErrorResponse(TRpcException $exception)`** - Creates JSON-RPC error response with proper format for version 1.0 or 2.0.
- **`createResponseHeaders($response)`** - Sets response headers to `application/json` with UTF-8 charset.
- **`decode($data)`** - Decodes JSON data using `TJavaScript::jsonDecode()`.
- **`encode($data)`** - Encodes data as JSON using `TJavaScript::jsonEncode()`.

## See Also

- [TRpcProtocol](./TRpcProtocol.md) - Base protocol class
- [TXmlRpcProtocol](./TXmlRpcProtocol.md) - XML-RPC implementation
- [TRpcService](../TRpcService.md) - The RPC service
