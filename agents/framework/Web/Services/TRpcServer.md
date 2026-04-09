# TRpcServer

### Directories

[./](../INDEX.md) > [Web](../INDEX.md) > [Services](./INDEX.md) > [TRpcServer](./TRpcServer.md)

**Location:** `framework/Web/Services/TRpcServer.php`
**Namespace:** `Prado\Web\Services`

## Overview

TRpcServer is middleware layer that sits between TRpcService and the protocol handler. It retrieves the raw request payload from `php://input`, passes it to the protocol handler for processing, and returns the formatted response. Can be subclassed for logging, debugging, or request/response filtering.

## Key Properties/Methods

- **`$handler`** - The TRpcProtocol instance handling the request format.
- **`__construct(TRpcProtocol $protocolHandler)`** - Initializes the server with a protocol handler instance.
- **`addRpcMethod($methodName, $methodDetails)`** - Delegates method registration to the protocol handler.
- **`getPayload()`** - Reads and returns the raw request payload from `php://input`.
- **`processRequest()`** - Passes the request payload to the protocol handler and returns the RPC response, catching TRpcException errors.

## See Also

- [TRpcProtocol](./TRpcProtocol.md) - Protocol base class
- [TRpcApiProvider](./TRpcApiProvider.md) - API implementation
- [TRpcService](../TRpcService.md) - The RPC service
