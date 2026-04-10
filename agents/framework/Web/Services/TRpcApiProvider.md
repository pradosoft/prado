# Web/Services/TRpcApiProvider

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / [Services](./INDEX.md) / **`TRpcApiProvider`**

## Class Info
**Location:** `framework/Web/Services/TRpcApiProvider.php`
**Namespace:** `Prado\Web\Services`

## Overview
TRpcApiProvider is an abstract class for implementing RPC API endpoints. Subclasses must implement `registerMethods()` to declare available RPC methods and their callbacks. The constructor automatically registers methods with the RPC server. Methods can be object methods or static class methods.

## Key Properties/Methods

- **`$rpcServer`** - The TRpcServer instance.
- **`registerMethods()`** - Abstract method that must return an array mapping method names to handler details.
- **`__construct(TRpcServer $rpcServer)`** - Initializes and registers all methods with the RPC server.
- **`processRequest()`** - Delegates request processing to the RPC server.
- **`getRpcServer()`** - Returns the RPC server instance.

## See Also

- [TRpcServer](./TRpcServer.md) - Server middleware
- [TRpcService](../TRpcService.md) - The RPC service
