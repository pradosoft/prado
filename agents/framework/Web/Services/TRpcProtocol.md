# Web/Services/TRpcProtocol

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [Services](./INDEX.md) / **`TRpcProtocol`**

## Class Info
**Location:** `framework/Web/Services/TRpcProtocol.php`
**Namespace:** `Prado\Web\Services`

## Overview
TRpcProtocol is an abstract base class for implementing RPC protocols in TRpcService. It provides the foundation for different RPC formats (XML-RPC, JSON-RPC, etc.) by defining abstract methods for encoding/decoding requests and responses, and manages the registry of available RPC methods.

## Key Properties/Methods

- **`$rpcMethods`** - Protected array mapping RPC method names to their handler details.
- **`addMethod($methodName, $handlerDetails)`** - Registers a new RPC method and its callback handler.
- **`callApiMethod($methodName, $parameters)`** - Invokes the callback handler for a given RPC method, throwing TRpcException if method not found.
- **`callMethod($requestPayload)`** - Abstract method to process RPC request and return response (implemented by subclasses).
- **`createErrorResponse(TRpcException $exception)`** - Abstract method to create error response from exception (implemented by subclasses).
- **`createResponseHeaders($response)`** - Abstract method to set response headers (implemented by subclasses).
- **`encode($data)`** - Abstract method to encode response data (implemented by subclasses).
- **`decode($data)`** - Abstract method to decode request payload (implemented by subclasses).

## See Also

- [TXmlRpcProtocol](./TXmlRpcProtocol.md) - XML-RPC implementation
- [TJsonRpcProtocol](./TJsonRpcProtocol.md) - JSON-RPC implementation
- [TRpcServer](./TRpcServer.md) - Server middleware layer
