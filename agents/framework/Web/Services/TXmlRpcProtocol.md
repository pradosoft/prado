# TXmlRpcProtocol

### Directories

[./](../INDEX.md) > [Web](../INDEX.md) > [Services](./INDEX.md) > [TXmlRpcProtocol](./TXmlRpcProtocol.md)

**Location:** `framework/Web/Services/TXmlRpcProtocol.php`
**Namespace:** `Prado\Web\Services`

## Overview

TXmlRpcProtocol implements the XML-RPC protocol for TRpcService. It wraps PHP's native `xmlrpc_server_*` functions to handle XML-encoded RPC requests and responses. The class processes incoming XML-RPC requests and returns XML-encoded responses.

## Key Properties/Methods

- **`callMethod($requestPayload)`** - Handles the RPC request by decoding the XML payload, calling the appropriate method, and returning an XML-encoded response.
- **`addMethod($methodName, $methodDetails)`** - Registers a new RPC method and handler details with the protocol.
- **`createErrorResponse(TRpcException $exception)`** - Converts a TRpcException into an XML-RPC fault response.
- **`createResponseHeaders($response)`** - Sets response headers to `text/xml` with UTF-8 charset.
- **`decode($data)`** - Decodes XML-encoded data into PHP data using `xmlrpc_decode()`.
- **`encode($data)`** - Encodes PHP data into XML format using `xmlrpc_encode()`.

## See Also

- [TRpcProtocol](./TRpcProtocol.md) - Base protocol class
- [TJsonRpcProtocol](./TJsonRpcProtocol.md) - JSON-RPC protocol implementation
- [TRpcService](../TRpcService.md) - The RPC service that uses these protocols
